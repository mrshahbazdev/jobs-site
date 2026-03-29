<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
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
    protected $signature = 'scrape:pakistan-jobs {--only-links} {--image-id=}';
    protected $description = 'Scrape latest jobs from PakistanJobsBank.com (Listing or Deep Scrape)';

    public function handle()
    {
        $imageId = $this->option('image-id');
        $onlyLinks = $this->option('only-links');

        if ($imageId) {
            return $this->processSingleImage($imageId);
        }

        return $this->fetchListing($onlyLinks);
    }

    private function fetchListing($onlyLinks = false)
    {
        $url = 'https://www.pakistanjobsbank.com/';
        $this->info("Fetching job list from {$url}...");

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36'
            ])->get($url);
            
            if (!$response->successful()) {
                $msg = "Failed to fetch the page. Status: " . $response->status();
                Cache::put('scraper_progress', ['current' => 0, 'total' => 0, 'status' => 'error', 'message' => $msg], 600);
                if ($this->output) $this->error($msg);
                return 1;
            }

            $html = $response->body();
            if (empty($html)) {
                Cache::put('scraper_progress', ['current' => 0, 'total' => 0, 'status' => 'error', 'message' => 'Empty HTML response'], 600);
                return 1;
            }

            $dom = new \DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new \DOMXPath($dom);

            // Robust query: Look for rows that have class 'job-ad' OR container table with specific header
            $rows = $xpath->query("//tr[contains(@class, 'job-ad')]");
            $total = $rows->length;
            
            if ($total === 0) {
                // Fallback: search for any link inside a table row that looks like a job link
                $rows = $xpath->query("//tr[td/strong/a[contains(@href, '/Jobs/')]]");
                $total = $rows->length;
            }

            $this->info("Found " . $total . " potential job links.");
            Cache::put('scraper_progress', [
                'current' => 0, 
                'total' => $total, 
                'status' => 'running',
                'latest_findings' => []
            ], 600);

            $count = 0;
            foreach ($rows as $row) {
                $tds = $xpath->query("td", $row);
                if ($tds->length < 2) continue;

                $td1 = $tds->item(0);
                $titleNode = $xpath->query(".//strong/a", $td1)->item(0);
                if (!$titleNode) continue;

                $title = trim($titleNode->textContent);
                $relativeLink = $titleNode->getAttribute('href');
                $fullJobUrl = "https://www.pakistanjobsbank.com" . $relativeLink;

                // Create or find Source Image Record
                $sourceRecord = JobSourceImage::firstOrCreate(
                    ['source_page_url' => $fullJobUrl],
                    ['title' => $title, 'is_processed' => false]
                );

                if (!$onlyLinks && !$sourceRecord->local_image_path) {
                    $this->processSingleImage($sourceRecord->id);
                }

                $count++;
                
                // Track latest findings for front-end real-time feedback
                $progress = Cache::get('scraper_progress', []);
                $findings = $progress['latest_findings'] ?? [];
                array_unshift($findings, $title);
                $findings = array_slice($findings, 0, 5); // Keep last 5
                
                Cache::put('scraper_progress', [
                    'current' => $count, 
                    'total' => $total, 
                    'status' => 'running',
                    'latest_findings' => $findings
                ], 600);
            }

            $progress = Cache::get('scraper_progress', []);
            $progress['status'] = 'completed';
            $progress['current'] = $count;
            Cache::put('scraper_progress', $progress, 600);
            $this->info("Successfully updated {$count} job sources.");
        } catch (\Exception $e) {
            Cache::put('scraper_progress', ['current' => 0, 'total' => 0, 'status' => 'error', 'message' => $e->getMessage()], 600);
            if ($this->output) $this->error("Error: " . $e->getMessage());
        }

        return 0;
    }

    public function processSingleImage($id)
    {
        $source = JobSourceImage::find($id);
        if (!$source) {
            if ($this->output) $this->error("Source image record not found.");
            return 1;
        }

        $this->info("Processing: " . $source->title);
        
        // Deep Scrape for Image
        $result = $this->deepScrapeImage($source->source_page_url, $source->title);
        
        if ($result) {
            $source->update([
                'local_image_path' => $result['path'],
                'source_image_url' => $result['url'],
            ]);
            if ($this->output) $this->info("  -> Image saved: " . $result['path']);
            return 0;
        }

        if ($this->output) $this->error("  -> Failed to fetch image.");
        return 1;
    }

    private function deepScrapeImage($url, $title)
    {
        try {
            $response = Http::get($url);
            if (!$response->successful()) return null;

            $dom = new \DOMDocument();
            @$dom->loadHTML($response->body());
            $xpath = new \DOMXPath($dom);

            $imgNode = $xpath->query("//img[@id='Contents_AdImage']")->item(0);
            if (!$imgNode) return null;

            $imgSrc = $imgNode->getAttribute('src');
            $fullImgUrl = $imgSrc;
            if (Str::startsWith($imgSrc, '/')) {
                $fullImgUrl = "https://www.pakistanjobsbank.com" . $imgSrc;
            }

            Cache::put('last_scraped_img_url', $fullImgUrl, 60);

            $imgResponse = Http::get($fullImgUrl);
            if (!$imgResponse->successful()) return null;
            
            $imgData = $imgResponse->body();
            $filename = 'job-sources/' . Str::slug($title) . '-' . time() . '.gif';
            Storage::disk('public')->put($filename, $imgData);

            return [
                'path' => $filename,
                'url' => $fullImgUrl,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
}
