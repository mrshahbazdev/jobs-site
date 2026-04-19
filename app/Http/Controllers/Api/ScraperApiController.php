<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ScrapePakistanJobs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class ScraperApiController extends Controller
{
    public function status()
    {
        $progress = Cache::get('scraper_progress', [
            'current' => 0,
            'total' => 0,
            'status' => 'idle',
            'message' => 'Scraper is not running.',
        ]);

        return response()->json($progress);
    }

    public function trigger(Request $request)
    {
        $mode = $request->input('mode', 'links');
        $onlyLinks = $mode !== 'all';

        Cache::put('scraper_progress', [
            'current' => 0,
            'total' => 0,
            'status' => 'starting',
            'latest_findings' => [],
        ], 600);

        ScrapePakistanJobs::dispatch($onlyLinks);

        return response()->json([
            'message' => 'Scraping task has been queued.',
            'mode' => $mode,
        ], 202);
    }

    public function scrapeImage(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:job_source_images,id',
        ]);

        $exitCode = Artisan::call('scrape:pakistan-jobs', [
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
