<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreJobRequest;
use App\Http\Requests\Api\UpdateJobRequest;
use App\Http\Requests\Api\BulkStoreJobRequest;
use App\Http\Resources\JobListingResource;
use App\Http\Resources\JobListingCollection;
use App\Models\JobListing;
use App\Models\Category;
use App\Models\City;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class JobApiController extends Controller
{
    // ─── ADVANCED JOB LISTING (with pagination, filters, sorting, search) ────

    public function index(Request $request): JsonResponse
    {
        $query = JobListing::query()->with(['category', 'city']);

        // ── Search ───────────────────────────────────────────────────────────
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description_html', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('department', 'LIKE', "%{$search}%")
                  ->orWhere('job_role', 'LIKE', "%{$search}%")
                  ->orWhere('skills', 'LIKE', "%{$search}%");
            });
        }

        // ── Filters ──────────────────────────────────────────────────────────
        if ($request->filled('category_id'))    $query->where('category_id', $request->category_id);
        if ($request->filled('city_id'))         $query->where('city_id', $request->city_id);
        if ($request->filled('province'))        $query->where('province', $request->province);
        if ($request->filled('job_type'))         $query->where('job_type', $request->job_type);
        if ($request->filled('contract_type'))   $query->where('contract_type', $request->contract_type);
        if ($request->filled('experience'))      $query->where('experience', $request->experience);
        if ($request->filled('education'))       $query->where('education', $request->education);
        if ($request->filled('sector'))          $query->where('sector', $request->sector);
        if ($request->filled('sub_sector'))      $query->where('sub_sector', $request->sub_sector);
        if ($request->filled('gender'))          $query->where('gender', $request->gender);
        if ($request->filled('bps_scale'))       $query->where('bps_scale', $request->bps_scale);
        if ($request->filled('newspaper'))       $query->where('newspaper', $request->newspaper);
        if ($request->filled('testing_service')) $query->where('testing_service', $request->testing_service);
        if ($request->filled('company_name'))    $query->where('company_name', 'LIKE', "%{$request->company_name}%");
        if ($request->filled('country'))         $query->where('country', $request->country);
        if ($request->filled('department'))      $query->where('department', 'LIKE', "%{$request->department}%");

        // ── Salary range filter ──────────────────────────────────────────────
        if ($request->filled('salary_min'))      $query->where('salary_min', '>=', $request->salary_min);
        if ($request->filled('salary_max'))      $query->where('salary_max', '<=', $request->salary_max);

        // ── Boolean flag filters ─────────────────────────────────────────────
        $booleanFilters = [
            'is_active', 'is_featured', 'is_premium', 'is_overseas', 'is_remote',
            'has_walkin_interview', 'is_whatsapp_apply', 'is_retired_army',
            'is_student_friendly', 'has_accommodation', 'has_transport',
            'has_medical_insurance', 'is_special_quota', 'is_minority_quota',
        ];
        foreach ($booleanFilters as $flag) {
            if ($request->has($flag) && $request->$flag !== null) {
                $query->where($flag, filter_var($request->$flag, FILTER_VALIDATE_BOOLEAN));
            }
        }

        // ── Date range filter ────────────────────────────────────────────────
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // ── Deadline filter ──────────────────────────────────────────────────
        if ($request->filled('deadline_from')) {
            $query->whereDate('deadline', '>=', $request->deadline_from);
        }
        if ($request->filled('deadline_to')) {
            $query->whereDate('deadline', '<=', $request->deadline_to);
        }
        if ($request->boolean('expired', false)) {
            $query->whereNotNull('deadline')->whereDate('deadline', '<', now());
        }

        // ── Sorting ──────────────────────────────────────────────────────────
        $sortBy    = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = [
            'created_at', 'updated_at', 'title', 'deadline',
            'salary_min', 'salary_max', 'company_name',
        ];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        // ── Pagination ───────────────────────────────────────────────────────
        $perPage = min((int) $request->input('per_page', 20), 100);
        $jobs    = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => JobListingResource::collection($jobs),
            'meta'    => [
                'current_page'  => $jobs->currentPage(),
                'last_page'     => $jobs->lastPage(),
                'per_page'      => $jobs->perPage(),
                'total'         => $jobs->total(),
                'from'          => $jobs->firstItem(),
                'to'            => $jobs->lastItem(),
            ],
            'links'   => [
                'first' => $jobs->url(1),
                'last'  => $jobs->url($jobs->lastPage()),
                'prev'  => $jobs->previousPageUrl(),
                'next'  => $jobs->nextPageUrl(),
            ],
        ]);
    }

    // ─── SHOW SINGLE JOB ─────────────────────────────────────────────────────

    public function show(string $idOrSlug): JsonResponse
    {
        $job = is_numeric($idOrSlug)
            ? JobListing::with(['category', 'city'])->findOrFail($idOrSlug)
            : JobListing::with(['category', 'city'])->where('slug', $idOrSlug)->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => new JobListingResource($job),
        ]);
    }

    // ─── STORE (CREATE) JOB ─────────────────────────────────────────────────

    public function store(StoreJobRequest $request): JsonResponse
    {
        $title = $request->validated()['title'];
        $duplicate = JobListing::where('title', $title)->first();
        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate job: is title ki job pehle se exist karti hai.',
                'existing_job' => new JobListingResource($duplicate->load(['category', 'city'])),
            ], 409);
        }

        $job = $this->createJobFromRequest($request);

        return response()->json([
            'success' => true,
            'message' => 'Job posted successfully!',
            'data'    => new JobListingResource($job->load(['category', 'city'])),
        ], 201);
    }

    // ─── UPDATE JOB ──────────────────────────────────────────────────────────

    public function update(UpdateJobRequest $request, int $id): JsonResponse
    {
        $job = JobListing::findOrFail($id);

        $data = $request->validated();

        // Handle slug update
        if (isset($data['slug']) || isset($data['title'])) {
            $newSlug = isset($data['slug'])
                ? Str::slug($data['slug'])
                : Str::slug($data['title']);

            if ($newSlug !== $job->slug) {
                $baseSlug = $newSlug;
                $counter  = 1;
                while (JobListing::where('slug', $newSlug)->where('id', '!=', $id)->exists()) {
                    $newSlug = $baseSlug . '-' . $counter++;
                }
                $data['slug'] = $newSlug;
            } else {
                unset($data['slug']);
            }
        }

        // Parse deadline
        if (isset($data['deadline'])) {
            try {
                $data['deadline'] = Carbon::parse($data['deadline'])->format('Y-m-d');
            } catch (\Exception $e) {
                $data['deadline'] = null;
            }
        }

        // Map description field
        if (isset($data['description'])) {
            $data['description_html'] = $data['description'];
            unset($data['description']);
        }

        // Handle thumbnail
        $thumbnailBase64 = null;
        if (isset($data['thumbnail_base64'])) {
            $thumbnailBase64 = $data['thumbnail_base64'];
            unset($data['thumbnail_base64']);
        }

        $job->update($data);

        // Save thumbnail
        if ($thumbnailBase64) {
            $this->saveThumbnail($job, $thumbnailBase64);
        }

        return response()->json([
            'success' => true,
            'message' => 'Job updated successfully!',
            'data'    => new JobListingResource($job->fresh()->load(['category', 'city'])),
        ]);
    }

    // ─── DELETE JOB ──────────────────────────────────────────────────────────

    public function destroy(int $id): JsonResponse
    {
        $job = JobListing::findOrFail($id);
        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job deleted successfully!',
        ]);
    }

    // ─── TOGGLE STATUS (quick active/featured/premium toggle) ────────────────

    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'field' => 'required|in:is_active,is_featured,is_premium',
        ]);

        $job   = JobListing::findOrFail($id);
        $field = $request->field;
        $job->$field = !$job->$field;
        $job->save();

        if ($field === 'is_active' && $job->is_active) {
            \App\Services\GoogleIndexingService::notify(url('/jobs/' . $job->slug));
        }

        return response()->json([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $field)) . ' toggled.',
            'data'    => [
                'id'    => $job->id,
                $field  => (bool) $job->$field,
            ],
        ]);
    }

    // ─── DUPLICATE JOB ───────────────────────────────────────────────────────

    public function duplicate(int $id): JsonResponse
    {
        $original = JobListing::findOrFail($id);

        $newJob = $original->replicate(['id', 'slug', 'created_at', 'updated_at']);
        $newJob->is_active = false;

        $baseSlug = $original->slug . '-copy';
        $slug     = $baseSlug;
        $counter  = 1;
        while (JobListing::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }
        $newJob->slug = $slug;
        $newJob->title = $original->title . ' (Copy)';
        $newJob->save();

        return response()->json([
            'success' => true,
            'message' => 'Job duplicated successfully!',
            'data'    => new JobListingResource($newJob->load(['category', 'city'])),
        ], 201);
    }

    // ─── BULK STORE ──────────────────────────────────────────────────────────

    public function bulkStore(BulkStoreJobRequest $request): JsonResponse
    {
        $results = ['created' => [], 'errors' => []];

        DB::beginTransaction();
        try {
            foreach ($request->jobs as $index => $jobData) {
                try {
                    $fakeRequest = new StoreJobRequest($jobData);
                    $fakeRequest->setContainer(app());
                    $fakeRequest->merge($jobData);

                    $job = $this->createJobFromRequest($fakeRequest, $jobData);
                    $results['created'][] = [
                        'index'  => $index,
                        'job_id' => $job->id,
                        'slug'   => $job->slug,
                        'url'    => url('/jobs/' . $job->slug),
                    ];
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'index'   => $index,
                        'message' => $e->getMessage(),
                    ];
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success'       => true,
            'message'       => count($results['created']) . ' job(s) created, ' . count($results['errors']) . ' error(s).',
            'created_count' => count($results['created']),
            'error_count'   => count($results['errors']),
            'results'       => $results,
        ], 201);
    }

    // ─── BULK UPDATE STATUS ──────────────────────────────────────────────────

    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:job_listings,id',
            'field' => 'required|in:is_active,is_featured,is_premium',
            'value' => 'required|boolean',
        ]);

        $updated = JobListing::whereIn('id', $request->ids)
            ->update([$request->field => $request->value]);

        return response()->json([
            'success'       => true,
            'message'       => $updated . ' job(s) updated.',
            'updated_count' => $updated,
        ]);
    }

    // ─── BULK DELETE ─────────────────────────────────────────────────────────

    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:job_listings,id',
        ]);

        $deleted = JobListing::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success'       => true,
            'message'       => $deleted . ' job(s) deleted.',
            'deleted_count' => $deleted,
        ]);
    }

    // ─── STATS / DASHBOARD ───────────────────────────────────────────────────

    public function stats(): JsonResponse
    {
        $total     = JobListing::count();
        $active    = JobListing::where('is_active', true)->count();
        $inactive  = $total - $active;
        $featured  = JobListing::where('is_featured', true)->count();
        $premium   = JobListing::where('is_premium', true)->count();
        $overseas  = JobListing::where('is_overseas', true)->count();
        $remote    = JobListing::where('is_remote', true)->count();

        $byCategory = Category::withCount(['jobs' => fn ($q) => $q->where('is_active', true)])
            ->having('jobs_count', '>', 0)
            ->orderByDesc('jobs_count')
            ->get(['id', 'name', 'slug'])
            ->map(fn ($c) => [
                'id'    => $c->id,
                'name'  => $c->name,
                'slug'  => $c->slug,
                'count' => $c->jobs_count,
            ]);

        $byCity = City::withCount(['jobs' => fn ($q) => $q->where('is_active', true)])
            ->having('jobs_count', '>', 0)
            ->orderByDesc('jobs_count')
            ->get(['id', 'name', 'slug'])
            ->map(fn ($c) => [
                'id'    => $c->id,
                'name'  => $c->name,
                'slug'  => $c->slug,
                'count' => $c->jobs_count,
            ]);

        $byProvince = JobListing::where('is_active', true)
            ->whereNotNull('province')
            ->select('province', DB::raw('COUNT(*) as count'))
            ->groupBy('province')
            ->orderByDesc('count')
            ->get();

        $byJobType = JobListing::where('is_active', true)
            ->whereNotNull('job_type')
            ->select('job_type', DB::raw('COUNT(*) as count'))
            ->groupBy('job_type')
            ->orderByDesc('count')
            ->get();

        $recentlyPosted = JobListing::where('created_at', '>=', now()->subDays(7))->count();
        $expiringSoon   = JobListing::where('is_active', true)
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [now(), now()->addDays(7)])
            ->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'overview' => [
                    'total'            => $total,
                    'active'           => $active,
                    'inactive'         => $inactive,
                    'featured'         => $featured,
                    'premium'          => $premium,
                    'overseas'         => $overseas,
                    'remote'           => $remote,
                    'posted_last_7d'   => $recentlyPosted,
                    'expiring_in_7d'   => $expiringSoon,
                ],
                'by_category' => $byCategory,
                'by_city'     => $byCity,
                'by_province' => $byProvince,
                'by_job_type' => $byJobType,
            ],
        ]);
    }

    // ─── PRIVATE HELPERS ─────────────────────────────────────────────────────

    private function createJobFromRequest($request, ?array $rawData = null): JobListing
    {
        $data = $rawData ?? $request->validated();

        // Build unique slug
        $baseSlug = !empty($data['slug'])
            ? Str::slug($data['slug'])
            : Str::slug($data['title']);
        $slug    = $baseSlug;
        $counter = 1;
        while (JobListing::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        // Parse deadline
        $deadline = null;
        if (!empty($data['deadline'])) {
            try {
                $deadline = Carbon::parse($data['deadline'])->format('Y-m-d');
            } catch (\Exception $e) {
                $deadline = null;
            }
        }

        $b = fn ($f, $default = false) => isset($data[$f]) ? (bool) $data[$f] : $default;

        $job = JobListing::create([
            'title'                => $data['title'],
            'slug'                 => $slug,
            'description_html'     => $data['description'],
            'category_id'          => $data['category_id'],
            'city_id'              => $data['city_id'],
            'schema_json'          => $data['schema_json'] ?? null,
            // Status
            'is_active'            => $b('is_active', true),
            'is_featured'          => $b('is_featured'),
            'is_premium'           => $b('is_premium'),
            // Job Details
            'deadline'             => $deadline,
            'department'           => $data['department'] ?? null,
            'company_name'         => $data['company_name'] ?? null,
            'whatsapp_number'      => $data['whatsapp_number'] ?? null,
            'salary_min'           => $data['salary_min'] ?? null,
            'salary_max'           => $data['salary_max'] ?? null,
            'salary_range'         => $data['salary_range'] ?? null,
            'experience'           => $data['experience'] ?? null,
            'job_type'             => $data['job_type'] ?? 'FULL_TIME',
            'contract_type'        => $data['contract_type'] ?? null,
            'job_role'             => $data['job_role'] ?? null,
            'skills'               => $data['skills'] ?? null,
            // Classification
            'education'            => $data['education'] ?? null,
            'qualification_degree' => $data['qualification_degree'] ?? null,
            'newspaper'            => $data['newspaper'] ?? null,
            'province'             => $data['province'] ?? null,
            'gender'               => $data['gender'] ?? null,
            'bps_scale'            => $data['bps_scale'] ?? null,
            'testing_service'      => $data['testing_service'] ?? null,
            'sector'               => $data['sector'] ?? null,
            'sub_sector'           => $data['sub_sector'] ?? null,
            'registration_council' => $data['registration_council'] ?? null,
            'country'              => $data['country'] ?? null,
            // Boolean Flags
            'is_overseas'          => $b('is_overseas'),
            'is_remote'            => $b('is_remote'),
            'has_walkin_interview'  => $b('has_walkin_interview'),
            'is_whatsapp_apply'    => $b('is_whatsapp_apply'),
            'is_retired_army'      => $b('is_retired_army'),
            'is_student_friendly'  => $b('is_student_friendly'),
            'has_accommodation'    => $b('has_accommodation'),
            'has_transport'        => $b('has_transport'),
            'has_medical_insurance' => $b('has_medical_insurance'),
            'is_special_quota'     => $b('is_special_quota'),
            'is_minority_quota'    => $b('is_minority_quota'),
            // SEO
            'meta_description'     => $data['meta_description'] ?? null,
            'meta_keywords'        => $data['meta_keywords'] ?? null,
        ]);

        // Save thumbnail if provided
        if (!empty($data['thumbnail_base64'])) {
            $this->saveThumbnail($job, $data['thumbnail_base64']);
        }

        // Notify Google if active
        if ($job->is_active) {
            \App\Services\GoogleIndexingService::notify(url('/jobs/' . $job->slug));
        }

        return $job;
    }

    private function saveThumbnail(JobListing $job, string $base64Image): void
    {
        try {
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
                $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
                $ext = strtolower($type[1]);
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $ext = 'webp';
                }
            } else {
                $ext = 'webp';
            }

            $imgData  = base64_decode($base64Image);
            $filename = time() . '_' . $job->slug . '.' . $ext;
            $savePath = 'job-listings/' . $filename;

            if (Storage::disk('public')->put($savePath, $imgData, 'public')) {
                $job->company_logo = $savePath;
                $job->save();
            }
        } catch (\Exception $e) {
            // Silently skip thumbnail errors
        }
    }
}
