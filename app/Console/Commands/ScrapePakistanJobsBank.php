<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\JobListing;
use App\Models\Category;
use App\Models\City;
use App\Models\JobSourceImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ScrapePakistanJobsBank extends Command
{
    protected $signature = 'scrape:pakistan-jobs {--only-links} {--image-id=} {--limit=}';
    protected $description = 'Scrape latest jobs from PakistanJobsBank.com (Listing or Deep Scrape)';

    /**
     * Realistic browser user-agent. Some sites (Cloudflare-fronted) will
     * return 403 or empty bodies without this.
     */
    private const USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36';

    private const BASE_URL = 'https://www.pakistanjobsbank.com';

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
        $progress = Cache::get('scraper_progress', []);
        Cache::put('scraper_progress', array_merge($progress, $patch), 600);
    }

    private function fetchListing(bool $onlyLinks = false, ?int $limit = null)
    {
        $url = self::BASE_URL . '/';
        $this->info("Fetching job list from {$url}...");

        try {
            $response = $this->httpClient()->get($url);

            if (!$response->successful()) {
                $msg = "Failed to fetch the page. Status: " . $response->status();
                Cache::put('scraper_progress', ['current' => 0, 'total' => 0, 'status' => 'error', 'message' => $msg], 600);
                Log::warning('[Scraper] listing fetch failed', ['status' => $response->status()]);
                if ($this->output) {
                    $this->error($msg);
                }
                return 1;
            }

            $html = $response->body();
            if (empty($html)) {
                Cache::put('scraper_progress', ['current' => 0, 'total' => 0, 'status' => 'error', 'message' => 'Empty HTML response'], 600);
                return 1;
            }

            $dom = new \DOMDocument();
            // libxml_use_internal_errors silences warnings from malformed HTML,
            // which the source frequently contains.
            libxml_use_internal_errors(true);
            $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);

            // Actual job rows use exactly class="job-ad". Using contains() here
            // was matching the date header rows ("job-ads-list-header") too,
            // which inflated the "total" counter and caused misleading progress.
            $rows = $xpath->query("//tr[@class='job-ad']");
            $total = $rows->length;

            if ($total === 0) {
                // Fallback: loose match with explicit class separator.
                $rows = $xpath->query("//tr[contains(concat(' ', normalize-space(@class), ' '), ' job-ad ')]");
                $total = $rows->length;
            }

            if ($total === 0) {
                // Final fallback: any table row linking into /Jobs/.
                $rows = $xpath->query("//tr[td/strong/a[contains(@href, '/Jobs/')]]");
                $total = $rows->length;
            }

            if ($limit !== null && $limit > 0) {
                $total = min($total, $limit);
            }

            $this->info("Found " . $total . " potential job links.");
            Cache::put('scraper_progress', [
                'current' => 0,
                'total' => $total,
                'status' => 'running',
                'errors' => 0,
                'latest_findings' => [],
            ], 600);

            $count = 0;
            $errors = 0;

            foreach ($rows as $row) {
                if ($limit !== null && $count >= $limit) {
                    break;
                }

                try {
                    $tds = $xpath->query("td", $row);
                    if ($tds->length < 2) {
                        continue;
                    }

                    $td1 = $tds->item(0);
                    $titleNode = $xpath->query(".//strong/a", $td1)->item(0);
                    if (!$titleNode) {
                        continue;
                    }

                    $title = trim($titleNode->textContent);
                    $relativeLink = $titleNode->getAttribute('href');
                    $fullJobUrl = self::BASE_URL . $relativeLink;

                    $sourceRecord = JobSourceImage::firstOrCreate(
                        ['source_page_url' => $fullJobUrl],
                        ['title' => $title, 'is_processed' => false]
                    );

                    if (!$onlyLinks && !$sourceRecord->local_image_path) {
                        $this->processSingleImage($sourceRecord->id);
                    }

                    $count++;

                    $progress = Cache::get('scraper_progress', []);
                    $findings = $progress['latest_findings'] ?? [];
                    array_unshift($findings, $title);
                    $findings = array_slice($findings, 0, 5);

                    Cache::put('scraper_progress', array_merge($progress, [
                        'current' => $count,
                        'total' => $total,
                        'status' => 'running',
                        'latest_findings' => $findings,
                    ]), 600);
                } catch (\Throwable $e) {
                    $errors++;
                    Log::error('[Scraper] row processing failed', ['error' => $e->getMessage()]);
                    $this->updateProgress(['errors' => $errors]);
                }
            }

            $this->updateProgress([
                'status' => 'completed',
                'current' => $count,
                'errors' => $errors,
            ]);
            $this->info("Successfully updated {$count} job sources. Errors: {$errors}.");
        } catch (\Throwable $e) {
            Cache::put('scraper_progress', ['current' => 0, 'total' => 0, 'status' => 'error', 'message' => $e->getMessage()], 600);
            Log::error('[Scraper] listing fatal error', ['error' => $e->getMessage()]);
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
            Log::error('[Scraper] image fetch failed', ['url' => $source->source_page_url, 'error' => $e->getMessage()]);
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
            Log::warning('[Scraper] detail page fetch failed', ['url' => $url, 'status' => $response->status()]);
            return null;
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($response->body(), 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        $imgNode = $xpath->query("//img[@id='Contents_AdImage']")->item(0);
        if (!$imgNode) {
            Log::info('[Scraper] ad image not present on detail page', ['url' => $url]);
            return null;
        }

        $imgSrc = $imgNode->getAttribute('src');
        $fullImgUrl = $imgSrc;
        if (Str::startsWith($imgSrc, '/')) {
            $fullImgUrl = self::BASE_URL . $imgSrc;
        } elseif (!Str::startsWith($imgSrc, ['http://', 'https://'])) {
            $fullImgUrl = self::BASE_URL . '/' . ltrim($imgSrc, '/');
        }

        Cache::put('last_scraped_img_url', $fullImgUrl, 60);

        $imgResponse = $this->httpClient()->get($fullImgUrl);
        if (!$imgResponse->successful()) {
            Log::warning('[Scraper] image fetch failed', ['url' => $fullImgUrl, 'status' => $imgResponse->status()]);
            return null;
        }

        $imgData = $imgResponse->body();
        if ($imgData === '' || $imgData === null) {
            return null;
        }

        // Derive extension from URL/content-type rather than hardcoding .gif
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
