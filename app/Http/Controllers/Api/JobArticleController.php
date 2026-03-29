<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobSourceImage;
use App\Models\JobListing;
use App\Models\Category;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class JobArticleController extends Controller
{
    public function pendingImages()
    {
        $image = JobSourceImage::where('is_processed', false)
            ->oldest() // We use oldest() so they are processed in order of arrival
            ->first();

        if (!$image) {
            return response()->json(null);
        }

        return response()->json([
            'id' => $image->id,
            'title' => $image->title,
            'image_url' => asset('storage/' . $image->local_image_path),
            'source_url' => $image->source_page_url,
        ]);
    }

    public function internalLinks(Request $request)
    {
        $categoryId = $request->query('category_id');
        
        $jobsQuery = \App\Models\JobListing::where('is_active', true)->latest();
        
        $relatedJobs = [];
        if ($categoryId) {
            $relatedJobs = (clone $jobsQuery)->where('category_id', $categoryId)->limit(20)->get(['id', 'title', 'slug', 'category_id']);
        }

        $generalJobs = $jobsQuery->limit(50)->get(['id', 'title', 'slug', 'category_id']);

        return response()->json([
            'related_jobs' => $relatedJobs,
            'general_jobs' => $generalJobs,
            'categories' => \App\Models\Category::all(['id', 'name', 'slug']),
            'cities' => \App\Models\City::all(['id', 'name', 'slug']),
            'base_url' => url('/')
        ]);
    }

    public function jobsList()
    {
        return response()->json(JobListing::latest()->limit(50)->get());
    }

    public function skipImage(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:job_source_images,id'
        ]);

        JobSourceImage::where('id', $request->id)->update([
            'is_processed' => true,
        ]);

        return response()->json(['message' => 'Image marked as skipped.']);
    }

    public function submitArticle(Request $request)
    {
        $request->validate([
            'job_source_image_id' => 'required|exists:job_source_images,id',
            'title' => 'required|string',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'city_id' => 'required|exists:cities,id',
            'slug' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'is_premium' => 'nullable|boolean',
            'schema_json' => 'nullable|string',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string',
            'experience' => 'nullable|string',
            'job_type' => 'nullable|string'
        ]);

        $slug = $request->slug ?: Str::slug($request->title);

        $job = JobListing::create([
            'title' => $request->title,
            'slug' => $slug,
            'description_html' => $request->description,
            'category_id' => $request->category_id,
            'city_id' => $request->city_id,
            'job_source_image_id' => $request->job_source_image_id,
            'schema_json' => $request->schema_json,
            'is_active' => $request->has('is_active') ? $request->is_active : false,
            'is_premium' => $request->has('is_premium') ? $request->is_premium : false,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
            'experience' => $request->experience,
            'job_type' => $request->job_type,
        ]);

        // Mark the source image as processed
        JobSourceImage::where('id', $request->job_source_image_id)->update([
            'is_processed' => true,
        ]);

        // SEO PING: Notify Google Indexing API
        if ($job->is_active) {
            \App\Services\GoogleIndexingService::notify(url('/jobs/' . $job->slug));
        }

        return response()->json(['message' => 'Article submitted and synced successfully!', 'job_id' => $job->id]);
    }
}
