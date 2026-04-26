<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobSourceImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ScraperQueueController extends Controller
{
    public function stats()
    {
        return response()->json([
            'total' => JobSourceImage::count(),
            'pending' => JobSourceImage::pending()->count(),
            'pending_with_image' => JobSourceImage::pending()->withImage()->count(),
            'published' => JobSourceImage::published()->count(),
            'skipped' => JobSourceImage::where('publish_status', 'skipped')->count(),
            'failed' => JobSourceImage::where('publish_status', 'failed')->count(),
        ]);
    }

    public function pending(Request $request)
    {
        $query = JobSourceImage::pending()
            ->withImage()
            ->orderBy('id', 'asc');

        $perPage = min((int) ($request->per_page ?? 20), 100);

        return response()->json($query->paginate($perPage));
    }

    public function next()
    {
        $image = JobSourceImage::pending()
            ->withImage()
            ->orderBy('id', 'asc')
            ->first();

        if (!$image) {
            return response()->json([
                'message' => 'No pending images in queue.',
                'data' => null,
            ]);
        }

        $imageUrl = $image->source_image_url;
        if ($image->local_image_path) {
            $imageUrl = asset('storage/' . $image->local_image_path);
        }

        return response()->json([
            'data' => [
                'id' => $image->id,
                'title' => $image->title,
                'source_page_url' => $image->source_page_url,
                'source_image_url' => $image->source_image_url,
                'local_image_url' => $image->local_image_path
                    ? asset('storage/' . $image->local_image_path)
                    : null,
                'image_url' => $imageUrl,
                'proxy_image_url' => $image->local_image_path
                    ? url('/api/v2/scraper-queue/' . $image->id . '/image')
                    : null,
                'publish_status' => $image->publish_status,
                'created_at' => $image->created_at,
            ],
        ]);
    }

    public function imageProxy($id)
    {
        $image = JobSourceImage::findOrFail($id);

        if (!$image->local_image_path || !Storage::disk('public')->exists($image->local_image_path)) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        $file = Storage::disk('public')->get($image->local_image_path);
        $mime = Storage::disk('public')->mimeType($image->local_image_path);

        return response($file, 200)
            ->header('Content-Type', $mime)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Cache-Control', 'public, max-age=86400');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,published,skipped,failed',
            'published_job_id' => 'nullable|integer',
        ]);

        $image = JobSourceImage::findOrFail($id);

        $data = ['publish_status' => $request->status];

        if ($request->status === 'published') {
            $data['published_at'] = now();
            if ($request->published_job_id) {
                $data['published_job_id'] = $request->published_job_id;
            }
        }

        $image->update($data);

        return response()->json([
            'message' => 'Status updated.',
            'data' => $image->fresh(),
        ]);
    }

    public function skip($id)
    {
        $image = JobSourceImage::findOrFail($id);
        $image->update(['publish_status' => 'skipped']);

        return response()->json([
            'message' => 'Image skipped.',
            'data' => $image->fresh(),
        ]);
    }

    public function resetStatus($id)
    {
        $image = JobSourceImage::findOrFail($id);
        $image->update([
            'publish_status' => 'pending',
            'published_job_id' => null,
            'published_at' => null,
        ]);

        return response()->json([
            'message' => 'Reset to pending.',
            'data' => $image->fresh(),
        ]);
    }
}
