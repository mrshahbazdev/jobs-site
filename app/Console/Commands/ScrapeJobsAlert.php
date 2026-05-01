<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\JobSourceImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ScrapeJobsAlert extends Command
{
    protected $signature = 'scrape:jobsalert {--only-links} {--image-id=} {--limit=}';
    protected $description = 'Scrape latest jobs from JobsAlert.pk (Listing or Deep Scrape)';

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';

    private const BASE_URL = 'https://jobsalert.pk';

    public function handle()
    {
        $imageId = $this->option('image-id');
        $onlyLinks = (bool) $this->option('only-links');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        if ($imageId) {
            return $this->processSingleImage($imageId);
        }

        return $this->fetchListing($onlyLinks, $limit);
    }

    private function httpClient()
    {
        return Http::withHeaders([
            'User-Agent' => self::USER_AGENT,
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9',
        ])->timeout(30)->connectTimeout(10)->retry(3, 1000, throw: false);
    }

    private function updateProgress(array $patch): void
    {
        $key = 'scraper_progress_jobsalert';
        $progress = Cache::get($key, []);
        Cache::put($key, array_merge($progress, $patch), 600);
    }

    private function fetchListing(bool $onlyLinks = false, ?int $limit = null)
    {
        $url = self::BASE_URL . '/';
        $this->info("Fetching job list from {$url}...");

        $cacheKey = 'scraper_progress_jobsalert';

        try {
            $response = $this->httpClient()->get($url);

            if (!$response->successful()) {
                $msg = "Failed to fetch the page. Status: " . $response->status();
                Cache::put($cacheKey, ['current' => 0, 'total' => 0, 'status' => 'error', 'message' => $msg], 600);
                Log::warning('[ScrapeJobsAlert] listing fetch failed', ['status' => $response->status()]);
                if ($this->output) {
                    $this->error($msg);
                }
                return 1;
            }

            $html = $response->body();
            if (empty($html)) {
                Cache::put($cacheKey, ['current' => 0, 'total' => 0, 'status' => 'error', 'message' => 'Empty HTML response'], 600);
                return 1;
            }

            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);

            // JobsAlert.pk uses a <table class="table table-striped table-hover">
            // Each job row is a <tr> inside <tbody> with <td> cells:
            //   td[0] = posted date, td[1] = link, td[2] = newspaper, td[3] = last date
            $table = $xpath->query("//table[contains(@class, 'table-striped')]//tbody//tr");
            $total = $table->length;
            $useRegex = false;
            $regexRows = [];

            $this->info("XPath found {$total} rows in table-striped tbody.");

            if ($total === 0) {
                // Fallback: any table row with an anchor that links to jobsalert.pk
                $table = $xpath->query("//table[contains(@class, 'table-striped')]//tr[td/a[contains(@href, 'jobsalert.pk')]]");
                $total = $table->length;
            }

            if ($total === 0) {
                // Regex fallback: extract links directly from HTML
                preg_match_all('/<a[^>]*href="(https?:\/\/jobsalert\.pk\/[^"]+\/\d+)"[^>]*>([^<]+)<\/a>/i', $html, $matches, PREG_SET_ORDER);
                if (!empty($matches)) {
                    $this->info("Regex fallback found " . count($matches) . " job links.");
                    $seen = [];
                    foreach ($matches as $m) {
                        $mUrl = trim($m[1]);
                        if (isset($seen[$mUrl])) continue;
                        $seen[$mUrl] = true;
                        $regexRows[] = ['url' => $mUrl, 'title' => trim($m[2])];
                    }
                    $total = count($regexRows);
                    $useRegex = true;
                }
            }

            if ($limit !== null && $limit > 0) {
                $total = min($total, $limit);
            }

            $this->info("Found " . $total . " potential job links.");
            Cache::put($cacheKey, [
                'current' => 0,
                'total' => $total,
                'status' => 'running',
                'errors' => 0,
                'latest_findings' => [],
            ], 600);

            $count = 0;
            $errors = 0;
            $skipped = 0;

            $iterableRows = $useRegex ? $regexRows : $table;
            foreach ($iterableRows as $row) {
                if ($limit !== null && $count >= $limit) {
                    break;
                }

                try {
                    // Handle both DOMElement (XPath result) and array (regex fallback)
                    if (is_array($row)) {
                        $title = $row['title'];
                        $fullJobUrl = $row['url'];
                    } else {
                        $tds = $xpath->query("td", $row);
                        if ($tds->length < 2) {
                            continue;
                        }

                        // The job link is inside the second <td>
                        $linkTd = $tds->item(1);
                        $anchor = $xpath->query(".//a", $linkTd)->item(0);
                        if (!$anchor) {
                            continue;
                        }

                        $title = trim($anchor->textContent);
                        $fullJobUrl = trim($anchor->getAttribute('href'));
                    }

                    // Ensure absolute URL
                    if (Str::startsWith($fullJobUrl, '/')) {
                        $fullJobUrl = self::BASE_URL . $fullJobUrl;
                    }

                    if (empty($title) || empty($fullJobUrl)) {
                        continue;
                    }

                    $existing = JobSourceImage::where('source_page_url', $fullJobUrl)->first();
                    if ($existing) {
                        $skipped++;
                        $processed = $count + $skipped;
                        $this->updateProgress([
                            'current' => $processed,
                            'total' => $total,
                            'status' => 'running',
                            'new' => $count,
                            'skipped' => $skipped,
                        ]);
                        continue;
                    }

                    $sourceRecord = JobSourceImage::create([
                        'title' => $title,
                        'source_page_url' => $fullJobUrl,
                        'is_processed' => false,
                    ]);

                    if (!$onlyLinks) {
                        $this->processSingleImage($sourceRecord->id);
                    }

                    $count++;
                    $processed = $count + $skipped;

                    $progress = Cache::get($cacheKey, []);
                    $findings = $progress['latest_findings'] ?? [];
                    array_unshift($findings, $title);
                    $findings = array_slice($findings, 0, 5);

                    Cache::put($cacheKey, array_merge($progress, [
                        'current' => $processed,
                        'total' => $total,
                        'status' => 'running',
                        'new' => $count,
                        'skipped' => $skipped,
                        'latest_findings' => $findings,
                    ]), 600);
                } catch (\Throwable $e) {
                    $errors++;
                    Log::error('[ScrapeJobsAlert] row processing failed', ['error' => $e->getMessage()]);
                    $this->updateProgress(['errors' => $errors]);
                }
            }

            $this->updateProgress([
                'status' => 'completed',
                'current' => $count,
                'errors' => $errors,
                'skipped' => $skipped,
            ]);
            $this->info("New: {$count}, Skipped (duplicate): {$skipped}, Errors: {$errors}.");
        } catch (\Throwable $e) {
            Cache::put($cacheKey, ['current' => 0, 'total' => 0, 'status' => 'error', 'message' => $e->getMessage()], 600);
            Log::error('[ScrapeJobsAlert] listing fatal error', ['error' => $e->getMessage()]);
            if ($this->output) {
                $this->error("Error: " . $e->getMessage());
            }
            return 1;
        }

        return 0;
    }

    public function processSingleImage($id)
    {
        $source = JobSourceImage::find($id);
        if (!$source) {
            if ($this->output) {
                $this->error("Source image record not found.");
            }
            return 1;
        }

        if ($this->output) {
            $this->info("Processing: " . $source->title);
        }

        try {
            $result = $this->deepScrapeImage($source->source_page_url, $source->title);
        } catch (\Throwable $e) {
            Log::error('[ScrapeJobsAlert] image fetch failed', ['url' => $source->source_page_url, 'error' => $e->getMessage()]);
            $result = null;
        }

        if ($result) {
            $source->update([
                'local_image_path' => $result['path'],
                'source_image_url' => $result['url'],
                'is_processed' => true,
            ]);
            if ($this->output) {
                $this->info("  -> Image saved: " . $result['path']);
            }
            return 0;
        }

        if ($this->output) {
            $this->error("  -> Failed to fetch image.");
        }
        return 1;
    }

    private function deepScrapeImage($url, $title)
    {
        $response = $this->httpClient()->get($url);
        if (!$response->successful()) {
            Log::warning('[ScrapeJobsAlert] detail page fetch failed', ['url' => $url, 'status' => $response->status()]);
            return null;
        }

        $html = $response->body();

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // JobsAlert.pk stores the main job image in the og:image meta tag
        // which points to wp-content/uploads/...
        $ogImage = $xpath->query("//meta[@property='og:image']/@content")->item(0);
        $imgSrc = $ogImage ? $ogImage->nodeValue : null;

        // Skip generic/favicon images
        if ($imgSrc && (str_contains($imgSrc, 'favicon') || str_contains($imgSrc, 'logo'))) {
            $imgSrc = null;
        }

        if (!$imgSrc) {
            // Fallback: look for wp-content/uploads images in the article body
            $bodyImgs = $xpath->query("//img[contains(@src, 'wp-content/uploads')]");
            foreach ($bodyImgs as $img) {
                $src = $img->getAttribute('src');
                if (!str_contains($src, 'favicon') && !str_contains($src, '150x150')) {
                    $imgSrc = $src;
                    break;
                }
            }
        }

        if (!$imgSrc) {
            Log::info('[ScrapeJobsAlert] no image found on detail page', ['url' => $url]);
            return null;
        }

        // Ensure absolute URL
        $fullImgUrl = $imgSrc;
        if (Str::startsWith($imgSrc, '/')) {
            $fullImgUrl = self::BASE_URL . $imgSrc;
        } elseif (!Str::startsWith($imgSrc, ['http://', 'https://'])) {
            $fullImgUrl = self::BASE_URL . '/' . ltrim($imgSrc, '/');
        }

        Cache::put('last_scraped_img_url', $fullImgUrl, 60);

        $imgResponse = $this->httpClient()->get($fullImgUrl);
        if (!$imgResponse->successful()) {
            Log::warning('[ScrapeJobsAlert] image fetch failed', ['url' => $fullImgUrl, 'status' => $imgResponse->status()]);
            return null;
        }

        $imgData = $imgResponse->body();
        if ($imgData === '' || $imgData === null) {
            return null;
        }

        $ext = strtolower(pathinfo(parse_url($fullImgUrl, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, ['gif', 'jpg', 'jpeg', 'png', 'webp'], true)) {
            $contentType = $imgResponse->header('Content-Type') ?? '';
            $ext = match (true) {
                str_contains($contentType, 'jpeg') => 'jpg',
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'gif') => 'gif',
                default => 'jpg',
            };
        }

        $filename = 'job-sources/' . Str::slug($title) . '-' . time() . '.' . $ext;
        Storage::disk('public')->put($filename, $imgData, 'public');

        return [
            'path' => $filename,
            'url' => $fullImgUrl,
        ];
    }
}
