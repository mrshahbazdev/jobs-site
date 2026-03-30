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
            ->oldest()
            ->first();

        if (!$image) {
            return response()->json(null);
        }

        return response()->json([
            'id'         => $image->id,
            'title'      => $image->title,
            'image_url'  => asset('storage/' . $image->local_image_path),
            'source_url' => $image->source_page_url,
        ]);
    }

    public function internalLinks(Request $request)
    {
        $categoryId = $request->query('category_id');

        $jobsQuery   = \App\Models\JobListing::where('is_active', true)->latest();
        $relatedJobs = [];

        if ($categoryId) {
            $relatedJobs = (clone $jobsQuery)->where('category_id', $categoryId)->limit(20)->get(['id', 'title', 'slug', 'category_id']);
        }

        $generalJobs = $jobsQuery->limit(50)->get(['id', 'title', 'slug', 'category_id']);

        return response()->json([
            'related_jobs' => $relatedJobs,
            'general_jobs' => $generalJobs,
            'categories'   => \App\Models\Category::all(['id', 'name', 'slug']),
            'cities'       => \App\Models\City::all(['id', 'name', 'slug']),
            'base_url'     => url('/'),
        ]);
    }

    public function jobsList()
    {
        return response()->json(JobListing::latest()->limit(50)->get());
    }

    public function skipImage(Request $request)
    {
        $request->validate(['id' => 'required|exists:job_source_images,id']);

        JobSourceImage::where('id', $request->id)->update(['is_processed' => true]);

        return response()->json(['message' => 'Image marked as skipped.']);
    }

    public function submitArticle(Request $request)
    {
        $request->validate([
            'job_source_image_id' => 'required|exists:job_source_images,id',
            'title'               => 'required|string',
            'description'         => 'required|string',
            'category_id'         => 'required|exists:categories,id',
            'city_id'             => 'required|exists:cities,id',
            'slug'                => 'nullable|string',
            'is_active'           => 'nullable|boolean',
            'is_premium'          => 'nullable|boolean',
            'schema_json'         => 'nullable|string',
            'meta_description'    => 'nullable|string|max:160',
            'meta_keywords'       => 'nullable|string',
            'experience'          => 'nullable|string',
            'job_type'            => 'nullable|string',
        ]);

        $slug = $request->slug ?: Str::slug($request->title);

        $job = JobListing::create([
            'title'               => $request->title,
            'slug'                => $slug,
            'description_html'    => $request->description,
            'category_id'         => $request->category_id,
            'city_id'             => $request->city_id,
            'job_source_image_id' => $request->job_source_image_id,
            'schema_json'         => $request->schema_json,
            'is_active'           => $request->has('is_active') ? $request->is_active : false,
            'is_premium'          => $request->has('is_premium') ? $request->is_premium : false,
            'meta_description'    => $request->meta_description,
            'meta_keywords'       => $request->meta_keywords,
            'experience'          => $request->experience,
            'job_type'            => $request->job_type,
        ]);

        JobSourceImage::where('id', $request->job_source_image_id)->update(['is_processed' => true]);

        if ($job->is_active) {
            \App\Services\GoogleIndexingService::notify(url('/jobs/' . $job->slug));
        }

        return response()->json(['message' => 'Article submitted and synced successfully!', 'job_id' => $job->id]);
    }

    /**
     * Post a job directly from test.html — all admin panel fields supported.
     * No job_source_image_id required. AI fills everything from OCR.
     */
    public function postJob(Request $request)
    {
        $request->validate([
            // ── Core ────────────────────────────────────────────────────────
            'title'                => 'required|string|max:500',
            'description'          => 'required|string',
            'category_id'          => 'required|exists:categories,id',
            'city_id'              => 'required|exists:cities,id',
            'slug'                 => 'nullable|string|max:600',
            'schema_json'          => 'nullable|string',
            // ── Status ───────────────────────────────────────────────────────
            'is_active'            => 'nullable|boolean',
            'is_featured'          => 'nullable|boolean',
            'is_premium'           => 'nullable|boolean',
            // ── Job Details ──────────────────────────────────────────────────
            'deadline'             => 'nullable|string|max:100',
            'department'           => 'nullable|string|max:300',
            'company_name'         => 'nullable|string|max:300',
            'whatsapp_number'      => 'nullable|string|max:20',
            'salary_min'           => 'nullable|numeric',
            'salary_max'           => 'nullable|numeric',
            'salary_range'         => 'nullable|string|max:100',
            'experience'           => 'nullable|string|max:100',
            'job_type'             => 'nullable|string|max:100',
            'contract_type'        => 'nullable|string|max:100',
            'job_role'             => 'nullable|string|max:300',
            'skills'               => 'nullable|string|max:500',
            // ── Classification ───────────────────────────────────────────────
            'education'            => 'nullable|string|max:100',
            'qualification_degree' => 'nullable|string|max:200',
            'newspaper'            => 'nullable|string|max:200',
            'province'             => 'nullable|string|max:100',
            'gender'               => 'nullable|string|max:50',
            'bps_scale'            => 'nullable|string|max:20',
            'testing_service'      => 'nullable|string|max:100',
            'sector'               => 'nullable|string|max:100',
            'sub_sector'           => 'nullable|string|max:200',
            'registration_council' => 'nullable|string|max:200',
            // ── Boolean Flags ────────────────────────────────────────────────
            'is_overseas'           => 'nullable|boolean',
            'is_remote'             => 'nullable|boolean',
            'has_walkin_interview'  => 'nullable|boolean',
            'is_whatsapp_apply'     => 'nullable|boolean',
            'is_retired_army'       => 'nullable|boolean',
            'is_student_friendly'   => 'nullable|boolean',
            'has_accommodation'     => 'nullable|boolean',
            'has_transport'         => 'nullable|boolean',
            'has_medical_insurance' => 'nullable|boolean',
            'is_special_quota'      => 'nullable|boolean',
            'is_minority_quota'     => 'nullable|boolean',
            // ── SEO ──────────────────────────────────────────────────────────
            'meta_description'     => 'nullable|string|max:160',
            'meta_keywords'        => 'nullable|string',
            'thumbnail_base64'     => 'nullable|string',
        ]);

        // ── Unique slug ──────────────────────────────────────────────────────
        $baseSlug = $request->slug ? Str::slug($request->slug) : Str::slug($request->title);
        $slug     = $baseSlug;
        $counter  = 1;
        while (JobListing::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        // ── Parse deadline ───────────────────────────────────────────────────
        $deadline = null;
        if ($request->deadline) {
            try { $deadline = \Carbon\Carbon::parse($request->deadline)->format('Y-m-d'); }
            catch (\Exception $e) { $deadline = null; }
        }

        // ── Boolean helper ───────────────────────────────────────────────────
        $b = fn ($f, $default = false) => $request->has($f) ? (bool) $request->$f : $default;

        $job = JobListing::create([
            // Core
            'title'                => $request->title,
            'slug'                 => $slug,
            'description_html'     => $request->description,
            'category_id'          => $request->category_id,
            'city_id'              => $request->city_id,
            'schema_json'          => $request->schema_json,
            // Status
            'is_active'            => $b('is_active', true),
            'is_featured'          => $b('is_featured'),
            'is_premium'           => $b('is_premium'),
            // Job Details
            'deadline'             => $deadline,
            'department'           => $request->department,
            'company_name'         => $request->company_name,
            'whatsapp_number'      => $request->whatsapp_number,
            'salary_min'           => $request->salary_min,
            'salary_max'           => $request->salary_max,
            'salary_range'         => $request->salary_range,
            'experience'           => $request->experience,
            'job_type'             => $request->job_type ?: 'FULL_TIME',
            'contract_type'        => $request->contract_type,
            'job_role'             => $request->job_role,
            'skills'               => $request->skills,
            // Classification
            'education'            => $request->education,
            'qualification_degree' => $request->qualification_degree,
            'newspaper'            => $request->newspaper,
            'province'             => $request->province,
            'gender'               => $request->gender,
            'bps_scale'            => $request->bps_scale,
            'testing_service'      => $request->testing_service,
            'sector'               => $request->sector,
            'sub_sector'           => $request->sub_sector,
            'registration_council' => $request->registration_council,
            // Boolean Flags
            'is_overseas'          => $b('is_overseas'),
            'is_remote'            => $b('is_remote'),
            'has_walkin_interview' => $b('has_walkin_interview'),
            'is_whatsapp_apply'    => $b('is_whatsapp_apply'),
            'is_retired_army'      => $b('is_retired_army'),
            'is_student_friendly'  => $b('is_student_friendly'),
            'has_accommodation'    => $b('has_accommodation'),
            'has_transport'        => $b('has_transport'),
            'has_medical_insurance'=> $b('has_medical_insurance'),
            'is_special_quota'     => $b('is_special_quota'),
            'is_minority_quota'    => $b('is_minority_quota'),
            // SEO
            'meta_description'     => $request->meta_description,
            'meta_keywords'        => $request->meta_keywords,
        ]);

        if ($request->filled('thumbnail_base64')) {
            try {
                $base64Image = $request->thumbnail_base64;
                if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                    $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                    $ext = strtolower($type[1]);
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                        $ext = 'webp';
                    }
                } else {
                    $ext = 'webp';
                }
                
                $imgData = base64_decode($base64Image);
                $filename = time() . '_' . $slug . '.' . $ext;
                $savePath = 'job-listings/' . $filename;
                
                if (\Illuminate\Support\Facades\Storage::disk('public')->put($savePath, $imgData)) {
                    $job->image_path = $savePath;
                    $job->save();
                }
            } catch (\Exception $e) {
                // Ignore image save error
            }
        }

        if ($job->is_active) {
            \App\Services\GoogleIndexingService::notify(url('/jobs/' . $job->slug));
        }

        return response()->json([
            'message' => 'Job posted successfully!',
            'job_id'  => $job->id,
            'job_url' => url('/jobs/' . $job->slug),
            'slug'    => $job->slug,
        ], 201);
    }
}
