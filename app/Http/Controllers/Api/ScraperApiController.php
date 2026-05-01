<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ScrapePakistanJobs;
use App\Jobs\ScrapeJobsAlertJobs;
use App\Jobs\ScrapeJobzPkJobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ScraperApiController extends Controller
{
    private const SOURCES = [
        'pakistan-jobs' => [
            'cache_key' => 'scraper_progress',
            'job_class' => ScrapePakistanJobs::class,
            'artisan_cmd' => 'scrape:pakistan-jobs',
        ],
        'jobsalert' => [
            'cache_key' => 'scraper_progress_jobsalert',
            'job_class' => ScrapeJobsAlertJobs::class,
            'artisan_cmd' => 'scrape:jobsalert',
        ],
        'jobz-pk' => [
            'cache_key' => 'scraper_progress_jobz',
            'job_class' => ScrapeJobzPkJobs::class,
            'artisan_cmd' => 'scrape:jobz-pk',
        ],
    ];

    public function status(Request $request)
    {
        $source = $request->input('source', 'pakistan-jobs');
        $cacheKey = self::SOURCES[$source]['cache_key'] ?? 'scraper_progress';

        $progress = Cache::get($cacheKey, [
            'current' => 0,
            'total' => 0,
            'status' => 'idle',
            'message' => 'Scraper is not running.',
        ]);

        return response()->json($progress);
    }

    public function statusAll()
    {
        $statuses = [];
        foreach (self::SOURCES as $key => $config) {
            $statuses[$key] = Cache::get($config['cache_key'], [
                'current' => 0,
                'total' => 0,
                'status' => 'idle',
                'message' => 'Scraper is not running.',
            ]);
        }

        return response()->json($statuses);
    }

    public function trigger(Request $request)
    {
        $source = $request->input('source', 'pakistan-jobs');
        $mode = $request->input('mode', 'links');
        $onlyLinks = $mode !== 'all';

        $config = self::SOURCES[$source] ?? null;
        if (!$config) {
            return response()->json([
                'message' => 'Unknown source. Valid: ' . implode(', ', array_keys(self::SOURCES)),
            ], 422);
        }

        Cache::put($config['cache_key'], [
            'current' => 0,
            'total' => 0,
            'status' => 'starting',
            'latest_findings' => [],
        ], 600);

        $jobClass = $config['job_class'];
        $jobClass::dispatch($onlyLinks);

        return response()->json([
            'message' => "Scraping task for '{$source}' has been queued.",
            'source' => $source,
            'mode' => $mode,
        ], 202);
    }

    public function scrapeImage(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:job_source_images,id',
        ]);

        $source = $request->input('source', 'pakistan-jobs');
        $config = self::SOURCES[$source] ?? self::SOURCES['pakistan-jobs'];

        $exitCode = Artisan::call($config['artisan_cmd'], [
            '--image-id' => $request->id,
        ]);

        if ($exitCode === 0) {
            $image = \App\Models\JobSourceImage::find($request->id);
            return response()->json([
                'status' => 'success',
                'image_url' => $image && $image->local_image_path
                    ? asset('storage/' . $image->local_image_path)
                    : null,
                'data' => $image,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to scrape image.',
        ], 500);
    }
}
