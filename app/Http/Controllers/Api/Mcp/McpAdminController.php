<?php

namespace App\Http\Controllers\Api\Mcp;

use App\Http\Controllers\Controller;
use App\Mail\JobAlert;
use App\Models\Bookmark;
use App\Models\Cv;
use App\Models\JobListing;
use App\Models\JobSourceImage;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class McpAdminController extends Controller
{
    // ─── Users ──────────────────────────────────────────────────────────────

    public function showUser(int $id): JsonResponse
    {
        $user = User::withCount(['cvs', 'bookmarks', 'comments'])->findOrFail($id);
        $user->load(['cvs:id,user_id,title,template,is_public,share_uuid,views_count,updated_at']);

        return response()->json([
            'success' => true,
            'data' => $user,
            'bookmarked_jobs' => $user->bookmarkedJobs()->select('job_listings.id', 'title', 'slug', 'is_active')->limit(50)->get(),
            'profile_completion' => $user->recomputeProfileCompletion(),
        ]);
    }

    public function createUser(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'nullable|string|min:8',
            'role' => ['nullable', Rule::in([User::ROLE_SEEKER, User::ROLE_EMPLOYER, User::ROLE_ADMIN])],
            'phone' => 'nullable|string|max:30',
            'verified' => 'nullable|boolean',
        ]);

        $password = $data['password'] ?? Str::random(16);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $password,
            'role' => $data['role'] ?? User::ROLE_SEEKER,
            'phone' => $data['phone'] ?? null,
        ]);
        if ($request->boolean('verified')) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return response()->json([
            'success' => true,
            'data' => $user,
            'generated_password' => isset($data['password']) ? null : $password,
        ], 201);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'sometimes|string|min:8',
            'role' => ['sometimes', Rule::in([User::ROLE_SEEKER, User::ROLE_EMPLOYER, User::ROLE_ADMIN])],
            'phone' => 'sometimes|nullable|string|max:30',
            'verified' => 'sometimes|boolean',
        ]);

        if (array_key_exists('verified', $data)) {
            $user->email_verified_at = $data['verified'] ? ($user->email_verified_at ?? now()) : null;
            unset($data['verified']);
        }
        $user->fill($data);
        $user->profile_completion_percent = $user->recomputeProfileCompletion();
        $user->save();

        return response()->json(['success' => true, 'data' => $user->fresh()]);
    }

    public function destroyUser(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1 && ! $request->boolean('force')) {
            return response()->json(['success' => false, 'message' => 'Refusing to delete the last admin. Pass force=true to override.'], 422);
        }

        DB::transaction(function () use ($user) {
            $user->bookmarks()->delete();
            $user->comments()->delete();
            $user->cvs()->delete();
            $user->delete();
        });

        return response()->json(['success' => true, 'deleted' => $id]);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $request->validate(['password' => 'nullable|string|min:8']);
        $password = $request->input('password') ?: Str::random(16);
        $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();

        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'generated_password' => $request->filled('password') ? null : $password,
        ]);
    }

    // ─── CVs ────────────────────────────────────────────────────────────────

    public function cvs(Request $request): JsonResponse
    {
        $query = Cv::with('user:id,name,email')->latest('updated_at');
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->user_id);
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', "%{$request->q}%");
        }
        if ($request->has('public')) {
            $query->where('is_public', $request->boolean('public'));
        }
        if ($request->filled('template')) {
            $query->where('template', $request->template);
        }
        $cvs = $query->paginate(min((int) $request->input('per_page', 20), 100));
        $cvs->getCollection()->transform(fn (Cv $cv) => $this->cvSummary($cv));

        return response()->json([
            'success' => true,
            'summary' => [
                'total' => Cv::count(),
                'public' => Cv::where('is_public', true)->count(),
                'by_template' => Cv::select('template', DB::raw('count(*) as count'))->groupBy('template')->pluck('count', 'template'),
                'total_views' => (int) Cv::sum('views_count'),
            ],
        ] + $cvs->toArray());
    }

    public function showCv(int $id): JsonResponse
    {
        $cv = Cv::with('user:id,name,email')->findOrFail($id);

        return response()->json(['success' => true, 'data' => $cv, 'public_url' => $this->cvPublicUrl($cv)]);
    }

    public function updateCv(Request $request, int $id): JsonResponse
    {
        $cv = Cv::findOrFail($id);
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'template' => ['sometimes', Rule::in(Cv::TEMPLATES)],
            'theme_color' => 'sometimes|nullable|string|max:20',
            'font_family' => 'sometimes|nullable|string|max:80',
            'personal' => 'sometimes|array',
            'summary' => 'sometimes|nullable|string',
            'experience' => 'sometimes|array',
            'education' => 'sometimes|array',
            'skills' => 'sometimes|array',
            'languages' => 'sometimes|array',
            'certifications' => 'sometimes|array',
            'projects' => 'sometimes|array',
            'references_list' => 'sometimes|array',
            'section_order' => 'sometimes|array',
            'is_public' => 'sometimes|boolean',
        ]);
        if (($data['is_public'] ?? false) && ! $cv->share_uuid) {
            $cv->share_uuid = (string) Str::uuid();
        }
        $cv->fill($data)->save();

        return response()->json(['success' => true, 'data' => $cv->fresh(), 'public_url' => $this->cvPublicUrl($cv)]);
    }

    public function destroyCv(int $id): JsonResponse
    {
        Cv::findOrFail($id)->delete();

        return response()->json(['success' => true, 'deleted' => $id]);
    }

    // ─── Bookmarks ──────────────────────────────────────────────────────────

    public function bookmarks(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 30);
        $top = Bookmark::select('job_listing_id', DB::raw('count(*) as bookmarks'))
            ->when($days > 0, fn ($q) => $q->where('bookmarks.created_at', '>=', now()->subDays($days)))
            ->groupBy('job_listing_id')
            ->orderByDesc('bookmarks')
            ->limit(min((int) $request->input('limit', 20), 100))
            ->with('jobListing:id,title,slug,is_active,category_id,city_id')
            ->get()
            ->map(fn ($b) => [
                'job_id' => $b->job_listing_id,
                'bookmarks' => (int) $b->bookmarks,
                'title' => $b->jobListing?->title,
                'slug' => $b->jobListing?->slug,
                'is_active' => (bool) $b->jobListing?->is_active,
            ]);

        return response()->json([
            'success' => true,
            'summary' => [
                'total' => Bookmark::count(),
                'users_with_bookmarks' => Bookmark::distinct('user_id')->count('user_id'),
                'jobs_bookmarked' => Bookmark::distinct('job_listing_id')->count('job_listing_id'),
                'window_days' => $days,
            ],
            'top_jobs' => $top,
        ]);
    }

    public function toggleBookmark(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'job_id' => 'required|integer|exists:job_listings,id',
        ]);
        $existing = Bookmark::where('user_id', $data['user_id'])->where('job_listing_id', $data['job_id'])->first();
        if ($existing) {
            $existing->delete();

            return response()->json(['success' => true, 'bookmarked' => false]);
        }
        Bookmark::create(['user_id' => $data['user_id'], 'job_listing_id' => $data['job_id']]);

        return response()->json(['success' => true, 'bookmarked' => true]);
    }

    // ─── Source images (manual ingest into scraper pipeline) ────────────────

    public function showSourceImage(int $id): JsonResponse
    {
        $image = JobSourceImage::with('jobListing:id,title,slug,is_active')->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->imageSummary($image)]);
    }

    public function storeSourceImage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'image_url' => 'required_without:image_base64|nullable|url',
            'image_base64' => 'required_without:image_url|nullable|string',
            'source_page_url' => 'nullable|url',
            'article_text' => 'nullable|string',
            'publish_status' => ['nullable', Rule::in(['pending', 'published', 'skipped'])],
        ]);

        $sourcePage = $data['source_page_url'] ?? $data['image_url'] ?? url('/mcp/manual/'.Str::uuid());
        if (JobSourceImage::where('source_page_url', $sourcePage)->exists()) {
            return response()->json(['success' => false, 'message' => 'A source image with this source_page_url already exists.'], 422);
        }

        $bytes = null;
        $ext = 'jpg';
        if (! empty($data['image_base64'])) {
            $raw = $data['image_base64'];
            if (preg_match('#^data:image/(\w+);base64,(.+)$#s', $raw, $m)) {
                $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
                $raw = $m[2];
            }
            $bytes = base64_decode($raw, true);
            if ($bytes === false) {
                return response()->json(['success' => false, 'message' => 'image_base64 is not valid base64.'], 422);
            }
        } else {
            $response = Http::timeout(30)->withHeaders(['User-Agent' => 'Mozilla/5.0 JobsPic-MCP'])->get($data['image_url']);
            if (! $response->successful()) {
                return response()->json(['success' => false, 'message' => "Image download failed (HTTP {$response->status()})."], 422);
            }
            $bytes = $response->body();
            $ext = strtolower(pathinfo(parse_url($data['image_url'], PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            if (! in_array($ext, ['gif', 'jpg', 'jpeg', 'png', 'webp'], true)) {
                $ct = (string) $response->header('Content-Type');
                $ext = match (true) {
                    str_contains($ct, 'png') => 'png',
                    str_contains($ct, 'webp') => 'webp',
                    str_contains($ct, 'gif') => 'gif',
                    default => 'jpg',
                };
            }
        }
        if (! in_array($ext, ['gif', 'jpg', 'jpeg', 'png', 'webp'], true)) {
            $ext = 'jpg';
        }

        $path = 'job-sources/'.Str::slug($data['title']).'-'.time().'.'.$ext;
        Storage::disk('public')->put($path, $bytes, 'public');

        $image = JobSourceImage::create([
            'title' => $data['title'],
            'source_page_url' => $sourcePage,
            'source_image_url' => $data['image_url'] ?? null,
            'local_image_path' => $path,
            'is_processed' => true,
            'article_text' => $data['article_text'] ?? null,
            'publish_status' => $data['publish_status'] ?? 'pending',
        ]);

        return response()->json(['success' => true, 'data' => $this->imageSummary($image)], 201);
    }

    public function updateSourceImage(Request $request, int $id): JsonResponse
    {
        $image = JobSourceImage::findOrFail($id);
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'article_text' => 'sometimes|nullable|string',
            'source_page_url' => 'sometimes|nullable|url',
            'publish_status' => ['sometimes', Rule::in(['pending', 'published', 'skipped'])],
            'published_job_id' => 'sometimes|nullable|integer|exists:job_listings,id',
        ]);
        if (($data['publish_status'] ?? null) === 'published' && ! $image->published_at) {
            $image->published_at = now();
        }
        $image->fill($data)->save();

        return response()->json(['success' => true, 'data' => $this->imageSummary($image->fresh())]);
    }

    public function destroySourceImage(Request $request, int $id): JsonResponse
    {
        $image = JobSourceImage::findOrFail($id);
        if ($image->local_image_path && $request->boolean('delete_file', true)) {
            Storage::disk('public')->delete($image->local_image_path);
        }
        JobListing::where('job_source_image_id', $image->id)->update(['job_source_image_id' => null]);
        $image->delete();

        return response()->json(['success' => true, 'deleted' => $id]);
    }

    public function publishSourceImage(Request $request, int $id): JsonResponse
    {
        $image = JobSourceImage::findOrFail($id);
        $data = $request->validate(['job_id' => 'required|integer|exists:job_listings,id']);
        $job = JobListing::findOrFail($data['job_id']);
        $job->job_source_image_id = $image->id;
        $job->save();
        $image->forceFill([
            'publish_status' => 'published',
            'published_job_id' => $job->id,
            'published_at' => $image->published_at ?? now(),
        ])->save();

        return response()->json(['success' => true, 'data' => $this->imageSummary($image->fresh()), 'job' => ['id' => $job->id, 'slug' => $job->slug]]);
    }

    // ─── Sitemaps / feeds ───────────────────────────────────────────────────

    private const SITEMAP_KEYS = [
        'sitemap:index', 'sitemap:static', 'sitemap:categories', 'sitemap:cities',
        'sitemap:news', 'sitemap:feed', 'sitemap:images', 'sitemap:amp', 'sitemap:stories',
    ];

    public function sitemaps(): JsonResponse
    {
        $perPage = 1000;
        $jobPages = (int) ceil(max(1, JobListing::where('is_active', true)->count()) / $perPage);
        $urls = ['/sitemap.xml', '/sitemap-static.xml', '/sitemap-categories.xml', '/sitemap-cities.xml', '/news-sitemap.xml', '/image-sitemap.xml', '/amp-sitemap.xml', '/stories-sitemap.xml', '/feed', '/robots.txt'];
        for ($i = 1; $i <= $jobPages; $i++) {
            $urls[] = "/sitemap-jobs-{$i}.xml";
        }

        $cached = [];
        foreach (self::SITEMAP_KEYS as $key) {
            $cached[$key] = Cache::has($key);
        }
        for ($i = 1; $i <= $jobPages; $i++) {
            $cached["sitemap:jobs:{$i}"] = Cache::has("sitemap:jobs:{$i}");
        }

        return response()->json([
            'success' => true,
            'urls' => array_map(fn ($u) => url($u), $urls),
            'job_sitemap_pages' => $jobPages,
            'cached' => $cached,
        ]);
    }

    public function flushSitemaps(): JsonResponse
    {
        $flushed = [];
        foreach (self::SITEMAP_KEYS as $key) {
            if (Cache::forget($key)) {
                $flushed[] = $key;
            }
        }
        $jobPages = (int) ceil(max(1, JobListing::count()) / 1000) + 2;
        for ($i = 1; $i <= $jobPages; $i++) {
            if (Cache::forget("sitemap:jobs:{$i}")) {
                $flushed[] = "sitemap:jobs:{$i}";
            }
        }

        return response()->json(['success' => true, 'flushed' => $flushed]);
    }

    // ─── Job alerts / mail ──────────────────────────────────────────────────

    public function alertsPreview(Request $request): JsonResponse
    {
        $hours = (int) $request->input('hours', 24);
        $since = now()->subHours($hours);
        $subscribers = Subscriber::where('is_active', true)->with(['category:id,name', 'city:id,name'])->get();

        $matches = $subscribers->map(function (Subscriber $s) use ($since) {
            $q = JobListing::where('is_active', true)->where('created_at', '>=', $since);
            if ($s->category_id) {
                $q->where('category_id', $s->category_id);
            }
            if ($s->city_id) {
                $q->where('city_id', $s->city_id);
            }
            $count = $q->count();

            return [
                'subscriber_id' => $s->id,
                'contact' => $s->email_or_whatsapp,
                'is_email' => filter_var($s->email_or_whatsapp, FILTER_VALIDATE_EMAIL) !== false,
                'category' => $s->category?->name,
                'city' => $s->city?->name,
                'matching_jobs' => $count,
            ];
        });

        return response()->json([
            'success' => true,
            'window_hours' => $hours,
            'mailer' => config('mail.default'),
            'active_subscribers' => $subscribers->count(),
            'would_send' => $matches->where('matching_jobs', '>', 0)->where('is_email', true)->count(),
            'subscribers' => $matches->values(),
        ]);
    }

    public function alertsSend(Request $request): JsonResponse
    {
        $request->validate(['subscriber_ids' => 'nullable|array', 'subscriber_ids.*' => 'integer']);
        if ($request->filled('subscriber_ids')) {
            $since = now()->subDay();
            $sent = [];
            foreach (Subscriber::whereIn('id', $request->subscriber_ids)->where('is_active', true)->get() as $s) {
                if (filter_var($s->email_or_whatsapp, FILTER_VALIDATE_EMAIL) === false) {
                    continue;
                }
                $q = JobListing::where('is_active', true)->where('created_at', '>=', $since);
                if ($s->category_id) {
                    $q->where('category_id', $s->category_id);
                }
                if ($s->city_id) {
                    $q->where('city_id', $s->city_id);
                }
                $jobs = $q->get();
                if ($jobs->isEmpty()) {
                    continue;
                }
                Mail::to($s->email_or_whatsapp)->send(new JobAlert($jobs));
                $sent[] = ['subscriber_id' => $s->id, 'jobs' => $jobs->count()];
            }

            return response()->json(['success' => true, 'sent' => $sent]);
        }

        $exit = Artisan::call('jobs:send-alerts');

        return response()->json(['success' => $exit === 0, 'exit_code' => $exit, 'output' => trim(Artisan::output())]);
    }

    public function mailTest(Request $request): JsonResponse
    {
        $data = $request->validate(['to' => 'required|email', 'subject' => 'nullable|string|max:255', 'body' => 'nullable|string']);
        Mail::raw($data['body'] ?? 'JobsPic MCP mail test at '.now()->toDateTimeString(), function ($m) use ($data) {
            $m->to($data['to'])->subject($data['subject'] ?? 'JobsPic mail test');
        });

        return response()->json(['success' => true, 'mailer' => config('mail.default'), 'to' => $data['to']]);
    }

    // ─── Storage ────────────────────────────────────────────────────────────

    public function storage(Request $request): JsonResponse
    {
        $dir = trim((string) $request->input('dir', ''), '/');
        if (str_contains($dir, '..')) {
            return response()->json(['success' => false, 'message' => 'Invalid directory.'], 422);
        }
        $disk = Storage::disk('public');
        $files = collect($disk->files($dir))
            ->map(fn ($f) => ['path' => $f, 'size' => $disk->size($f), 'modified_at' => date('c', $disk->lastModified($f)), 'url' => $disk->url($f)])
            ->sortByDesc('modified_at')
            ->take(min((int) $request->input('limit', 100), 500))
            ->values();

        $dirs = $disk->directories($dir);
        $totals = [];
        foreach (($dir === '' ? $dirs : [$dir]) as $d) {
            $all = $disk->allFiles($d);
            $totals[$d] = ['files' => count($all), 'bytes' => array_sum(array_map(fn ($f) => $disk->size($f), $all))];
        }

        return response()->json([
            'success' => true,
            'dir' => $dir,
            'directories' => $dirs,
            'totals' => $totals,
            'files' => $files,
            'storage_link_ok' => is_link(public_path('storage')) || is_dir(public_path('storage')),
            'disk_free_bytes' => @disk_free_space(storage_path()) ?: null,
        ]);
    }

    public function storageDelete(Request $request): JsonResponse
    {
        $data = $request->validate(['paths' => 'required|array|min:1', 'paths.*' => 'string']);
        $disk = Storage::disk('public');
        $deleted = [];
        $skipped = [];
        foreach ($data['paths'] as $p) {
            $p = ltrim($p, '/');
            if (str_contains($p, '..') || ! $disk->exists($p)) {
                $skipped[] = $p;

                continue;
            }
            $disk->delete($p);
            $deleted[] = $p;
        }

        return response()->json(['success' => true, 'deleted' => $deleted, 'skipped' => $skipped]);
    }

    public function orphanImages(Request $request): JsonResponse
    {
        $disk = Storage::disk('public');
        $referenced = JobSourceImage::whereNotNull('local_image_path')->pluck('local_image_path')->flip();
        $orphans = collect($disk->files('job-sources'))->reject(fn ($f) => $referenced->has($f))->values();
        $missing = JobSourceImage::whereNotNull('local_image_path')->get(['id', 'local_image_path'])
            ->reject(fn ($i) => $disk->exists($i->local_image_path))->values();

        if ($request->boolean('delete_orphans')) {
            $disk->delete($orphans->all());
        }

        return response()->json([
            'success' => true,
            'orphan_files' => $orphans,
            'orphan_bytes' => $request->boolean('delete_orphans') ? 0 : $orphans->sum(fn ($f) => $disk->size($f)),
            'deleted' => $request->boolean('delete_orphans'),
            'records_missing_file' => $missing,
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function cvSummary(Cv $cv): array
    {
        return [
            'id' => $cv->id,
            'title' => $cv->title,
            'template' => $cv->template,
            'is_public' => (bool) $cv->is_public,
            'views_count' => (int) $cv->views_count,
            'public_url' => $this->cvPublicUrl($cv),
            'user' => $cv->user ? ['id' => $cv->user->id, 'name' => $cv->user->name, 'email' => $cv->user->email] : null,
            'updated_at' => $cv->updated_at,
        ];
    }

    private function cvPublicUrl(Cv $cv): ?string
    {
        return $cv->is_public && $cv->share_uuid ? url('/cv/s/'.$cv->share_uuid) : null;
    }

    private function imageSummary(JobSourceImage $image): array
    {
        return [
            'id' => $image->id,
            'title' => $image->title,
            'source_page_url' => $image->source_page_url,
            'source_image_url' => $image->source_image_url,
            'local_image_path' => $image->local_image_path,
            'image_url' => $image->local_image_path ? asset('storage/'.$image->local_image_path) : null,
            'is_processed' => (bool) $image->is_processed,
            'publish_status' => $image->publish_status,
            'published_job_id' => $image->published_job_id,
            'published_at' => $image->published_at,
            'article_text' => $image->article_text,
            'job' => $image->relationLoaded('jobListing') && $image->jobListing ? ['id' => $image->jobListing->id, 'title' => $image->jobListing->title, 'slug' => $image->jobListing->slug] : null,
            'created_at' => $image->created_at,
        ];
    }
}
