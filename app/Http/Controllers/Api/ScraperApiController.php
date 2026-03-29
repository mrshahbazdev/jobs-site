<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            'message' => 'Scraper is not running.'
        ]);

        return response()->json($progress);
    }

    public function trigger(Request $request)
    {
        $mode = $request->input('mode', 'links'); // 'links' or 'all'

        if ($mode === 'all') {
            Artisan::queue('scrape:pakistan-jobs');
        } else {
            Artisan::queue('scrape:pakistan-jobs', ['--only-links' => true]);
        }

        return response()->json([
            'message' => 'Scraping task has been queued.',
            'mode' => $mode
        ]);
    }

    public function scrapeImage(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:job_source_images,id'
        ]);

        $result = Artisan::call('scrape:pakistan-jobs', [
            '--image-id' => $request->id
        ]);

        if ($result === 0) {
            $image = \App\Models\JobSourceImage::find($request->id);
            return response()->json([
                'status' => 'success',
                'image_url' => asset('storage/' . $image->local_image_path),
                'data' => $image
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Failed to scrape image.'
        ], 500);
    }
}
