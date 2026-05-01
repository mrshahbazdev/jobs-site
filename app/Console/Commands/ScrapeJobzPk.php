<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\JobSourceImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class ScrapeJobzPk extends Command
{
    protected $signature = 'scrape:jobz-pk {--only-links} {--image-id=} {--limit=}';
    protected $description = 'Scrape latest jobs from Jobz.pk (Listing or Deep Scrape)';

    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';

    private const BASE_URL = 'https://www.jobz.pk';

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
        $key = 'scraper_progress_jobz';
        $progress = Cache::get($key, []);
        Cache::put($key, array_merge($progress, $patch), 600);
    }

    private function fetchListing(bool $onlyLinks = false, ?int $limit = null)
    {
        $url = self::BASE_URL . '/';
        $this->info("Fetching job list from {$url}...");

        $cacheKey = 'scraper_progress_jobz';

        try {
            $response = $this->httpClient()->get($url);

            if (!$response->successful()) {
                $msg = "Failed to fetch the page. Status: " . $response->status();
                Cache::put($cacheKey, ['current' => 0, 'total' => 0, 'status' => 'error', 'message' => $msg], 600);
                Log::warning('[ScrapeJobzPk] listing fetch failed', ['status' => $response->status()]);
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
            $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);

            // Jobz.pk uses <div class="row_container"> for each job.
            // Inside: div.cell1 contains <a href="..._jobs-XXXX.html"> with the title,
            //         div.cell2 contains the department/industry,
            //         div.cell_three > div.inner_cell has city and date.
            $rows = $xpath->query("//div[contains(@class, 'row_container')]");
            $total = 0;

            // Count only rows that contain a job link (filter out header rows)
            $jobRows = [];
            foreach ($rows as $row) {
                $anchor = $xpath->query(".//div[contains(@class, 'cell1')]//a[contains(@href, '_jobs-')]", $row)->item(0);
                if ($anchor) {
                    $jobRows[] = $row;
                }
            }

            $total = count($jobRows);

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

            foreach ($jobRows as $row) {
                if ($limit !== null && $count >= $limit) {
                    break;
                }

                try {
                    $anchor = $xpath->query(".//div[contains(@class, 'cell1')]//a[contains(@href, '_jobs-')]", $row)->item(0);
                    if (!$anchor) {
                        continue;
                    }

                    $title = trim($anchor->textContent);
                    $fullJobUrl = trim($anchor->getAttribute('href'));

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

                    $progress = Cache::get($cacheKey, []);
                    $findings = $progress['latest_findings'] ?? [];
                    array_unshift($findings, $title);
                    $findings = array_slice($findings, 0, 5);

                    Cache::put($cacheKey, array_merge($progress, [
                        'current' => $count,
                        'total' => $total,
                        'status' => 'running',
                        'latest_findings' => $findings,
                    ]), 600);
                } catch (\Throwable $e) {
                    $errors++;
                    Log::error('[ScrapeJobzPk] row processing failed', ['error' => $e->getMessage()]);
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
            Log::error('[ScrapeJobzPk] listing fatal error', ['error' => $e->getMessage()]);
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
            Log::error('[ScrapeJobzPk] image fetch failed', ['url' => $source->source_page_url, 'error' => $e->getMessage()]);
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
            Log::warning('[ScrapeJobzPk] detail page fetch failed', ['url' => $url, 'status' => $response->status()]);
            return null;
        }

        $html = $response->body();

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // Jobz.pk stores job images at /images/jobs/YYYY-MM/ID_N.jpg
        // The main image is typically the first <img> with src containing /images/jobs/
        $imgNode = $xpath->query("//img[contains(@src, '/images/jobs/')]")->item(0);
        $imgSrc = $imgNode ? $imgNode->getAttribute('src') : null;

        if (!$imgSrc) {
            // Fallback: check og:image meta tag
            $ogImage = $xpath->query("//meta[@property='og:image']/@content")->item(0);
            $imgSrc = $ogImage ? $ogImage->nodeValue : null;
        }

        if (!$imgSrc) {
            Log::info('[ScrapeJobzPk] no image found on detail page', ['url' => $url]);
            return null;
        }

        $fullImgUrl = $imgSrc;
        if (Str::startsWith($imgSrc, '/')) {
            $fullImgUrl = self::BASE_URL . $imgSrc;
        } elseif (!Str::startsWith($imgSrc, ['http://', 'https://'])) {
            $fullImgUrl = self::BASE_URL . '/' . ltrim($imgSrc, '/');
        }

        Cache::put('last_scraped_img_url', $fullImgUrl, 60);

        $imgResponse = $this->httpClient()->get($fullImgUrl);
        if (!$imgResponse->successful()) {
            Log::warning('[ScrapeJobzPk] image fetch failed', ['url' => $fullImgUrl, 'status' => $imgResponse->status()]);
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
