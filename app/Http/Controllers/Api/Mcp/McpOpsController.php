<?php

namespace App\Http\Controllers\Api\Mcp;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\JobListing;
use App\Models\JobSourceImage;
use App\Models\PushSubscription;
use App\Services\GeminiService;
use App\Services\IndexNowService;
use App\Services\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class McpOpsController extends Controller
{
    // ─── SEO audit & maintenance ────────────────────────────────────────────

    public function seoAudit(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 50), 200);
        $pick = fn ($q) => $q->latest()->limit($limit)->get(['id', 'title', 'slug', 'deadline', 'updated_at'])
            ->map(fn ($j) => ['id' => $j->id, 'title' => $j->title, 'url' => url('/jobs/'.$j->slug), 'deadline' => $j->deadline]);

        $active = JobListing::where('is_active', true);

        $checks = [
            'missing_meta_description' => (clone $active)->where(fn ($q) => $q->whereNull('meta_description')->orWhere('meta_description', '')),
            'long_meta_description' => (clone $active)->whereRaw('LENGTH(meta_description) > 160'),
            'missing_meta_keywords' => (clone $active)->where(fn ($q) => $q->whereNull('meta_keywords')->orWhere('meta_keywords', '')),
            'missing_schema_json' => (clone $active)->where(fn ($q) => $q->whereNull('schema_json')->orWhereRaw('LENGTH(schema_json) < 50')),
            'missing_deadline' => (clone $active)->whereNull('deadline'),
            'expired_but_active' => (clone $active)->whereNotNull('deadline')->whereDate('deadline', '<', now()),
            'missing_company_logo' => (clone $active)->whereNull('company_logo'),
            'thin_description' => (clone $active)->whereRaw('LENGTH(description_html) < 400'),
            'missing_category_or_city' => (clone $active)->where(fn ($q) => $q->whereNull('category_id')->orWhereNull('city_id')),
            'long_title' => (clone $active)->whereRaw('LENGTH(title) > 70'),
        ];

        $report = [];
        foreach ($checks as $name => $query) {
            $report[$name] = ['count' => (clone $query)->count(), 'samples' => $pick(clone $query)];
        }

        $duplicateTitles = JobListing::select('title', DB::raw('COUNT(*) as count'))
            ->groupBy('title')->having('count', '>', 1)->orderByDesc('count')->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'active_jobs' => (clone $active)->count(),
                'checks' => $report,
                'duplicate_titles' => $duplicateTitles,
                'empty_categories' => Category::doesntHave('jobs')->get(['id', 'name', 'slug']),
                'empty_cities' => City::doesntHave('jobs')->get(['id', 'name', 'slug']),
            ],
        ]);
    }

    public function deactivateExpired(Request $request): JsonResponse
    {
        $grace = (int) $request->input('grace_days', 0);
        $query = JobListing::where('is_active', true)->whereNotNull('deadline')
            ->whereDate('deadline', '<', now()->subDays($grace));

        $ids = $query->pluck('id');
        if (! $request->boolean('dry_run')) {
            JobListing::whereIn('id', $ids)->update(['is_active' => false]);
        }

        return response()->json([
            'success' => true,
            'dry_run' => $request->boolean('dry_run'),
            'affected' => $ids->count(),
            'ids' => $ids,
        ]);
    }

    public function bulkDeleteByFilter(Request $request): JsonResponse
    {
        $request->validate([
            'expired' => 'nullable|boolean',
            'inactive' => 'nullable|boolean',
            'category' => 'nullable|string',
            'city' => 'nullable|string',
            'job_type' => 'nullable|string',
            'older_than_days' => 'nullable|integer|min:1|max:3650',
            'deadline_before' => 'nullable|date',
            'title_like' => 'nullable|string|max:200',
            'dry_run' => 'nullable|boolean',
            'confirm' => 'nullable|boolean',
        ]);

        $hasFilter = collect($request->only(['expired', 'inactive', 'category', 'city', 'job_type', 'older_than_days', 'deadline_before', 'title_like']))
            ->filter(fn ($v) => $v !== null && $v !== '' && $v !== false)->isNotEmpty();
        if (! $hasFilter) {
            return response()->json(['success' => false, 'message' => 'At least one filter is required (refusing to delete all jobs).'], 422);
        }

        $query = JobListing::query();
        if ($request->boolean('expired')) {
            $query->whereNotNull('deadline')->whereDate('deadline', '<', now());
        }
        if ($request->boolean('inactive')) {
            $query->where('is_active', false);
        }
        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category)->orWhere('id', $request->category)->orWhere('name', $request->category));
        }
        if ($request->filled('city')) {
            $query->whereHas('city', fn ($q) => $q->where('slug', $request->city)->orWhere('id', $request->city)->orWhere('name', $request->city));
        }
        if ($request->filled('job_type')) {
            $query->where('job_type', 'like', '%'.$request->job_type.'%');
        }
        if ($request->filled('older_than_days')) {
            $query->where('created_at', '<', now()->subDays((int) $request->older_than_days));
        }
        if ($request->filled('deadline_before')) {
            $query->whereNotNull('deadline')->whereDate('deadline', '<', $request->deadline_before);
        }
        if ($request->filled('title_like')) {
            $query->where('title', 'like', '%'.$request->title_like.'%');
        }

        $matched = (clone $query)->count();
        $samples = (clone $query)->latest()->limit(20)->get(['id', 'title', 'slug', 'deadline', 'is_active']);
        $dryRun = $request->boolean('dry_run') || ! $request->boolean('confirm');
        $deleted = $dryRun ? 0 : $query->delete();

        return response()->json([
            'success' => true,
            'dry_run' => $dryRun,
            'matched' => $matched,
            'deleted' => $deleted,
            'samples' => $samples,
        ]);
    }

    public function regenerateSchema(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'nullable|array', 'ids.*' => 'integer', 'only_missing' => 'nullable|boolean', 'limit' => 'nullable|integer|min:1|max:500']);

        $query = JobListing::with('city');
        if ($request->filled('ids')) {
            $query->whereIn('id', $request->ids);
        } elseif ($request->boolean('only_missing', true)) {
            $query->where(fn ($q) => $q->whereNull('schema_json')->orWhereRaw('LENGTH(schema_json) < 50'));
        }
        $jobs = $query->limit((int) $request->input('limit', 100))->get();

        $done = [];
        foreach ($jobs as $job) {
            if ($request->boolean('force')) {
                $job->schema_json = null;
            }
            $job->schema_json = $job->generateSchema();
            $job->save();
            $done[] = $job->id;
        }

        return response()->json(['success' => true, 'regenerated' => count($done), 'ids' => $done]);
    }

    // ─── AI enrichment (Gemini) ─────────────────────────────────────────────

    public function aiExtract(Request $request): JsonResponse
    {
        $request->validate(['html' => 'required_without:job_id|string', 'job_id' => 'required_without:html|integer|exists:job_listings,id']);

        if (! config('services.gemini.key')) {
            return response()->json(['success' => false, 'message' => 'GEMINI_API_KEY is not configured.'], 422);
        }

        $html = $request->input('html');
        $job = null;
        if ($request->filled('job_id')) {
            $job = JobListing::findOrFail($request->job_id);
            $html = $job->description_html;
        }

        $meta = GeminiService::extractMetadata((string) $html);

        if ($job && $request->boolean('apply')) {
            $job->fill(array_filter([
                'meta_description' => Str::limit($meta['meta_description'] ?? '', 160, ''),
                'meta_keywords' => $meta['meta_keywords'] ?? null,
                'experience' => $job->experience ?: ($meta['experience'] ?? null),
                'job_type' => $job->job_type ?: ($meta['job_type'] ?? null),
            ]))->save();
        }

        return response()->json(['success' => true, 'applied' => (bool) ($job && $request->boolean('apply')), 'job_id' => $job?->id, 'data' => $meta]);
    }

    // ─── IndexNow / search engine pings ─────────────────────────────────────

    public function indexNow(Request $request): JsonResponse
    {
        $request->validate(['urls' => 'nullable|array|max:10000', 'urls.*' => 'url', 'job_ids' => 'nullable|array', 'job_ids.*' => 'integer']);

        $urls = collect($request->input('urls', []));
        if ($request->filled('job_ids')) {
            $urls = $urls->merge(JobListing::whereIn('id', $request->job_ids)->pluck('slug')->map(fn ($s) => url('/jobs/'.$s)));
        }
        $urls = $urls->unique()->values();

        if ($urls->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No URLs to submit.'], 422);
        }

        $result = $urls->count() === 1
            ? ['ok' => IndexNowService::submit($urls->first()), 'status' => null, 'error' => null]
            : IndexNowService::submitBatch($urls->all());

        return response()->json(['success' => $result['ok'], 'submitted' => $urls->count(), 'result' => $result, 'urls' => $urls]);
    }

    // ─── Web push ───────────────────────────────────────────────────────────

    public function pushStats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'vapid_configured' => (bool) config('services.webpush.vapid.public_key'),
                'total' => PushSubscription::count(),
                'failing' => PushSubscription::where('failure_count', '>', 0)->count(),
                'by_category' => PushSubscription::select('category_id', DB::raw('count(*) as count'))->groupBy('category_id')->pluck('count', 'category_id'),
                'by_city' => PushSubscription::select('city_id', DB::raw('count(*) as count'))->groupBy('city_id')->pluck('count', 'city_id'),
                'jobs_awaiting_push' => JobListing::where('is_active', true)->whereNull('push_notified_at')->count(),
                'last_notified_at' => PushSubscription::max('last_notified_at'),
            ],
        ]);
    }

    public function pushBroadcast(Request $request, WebPushService $push): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:300',
            'url' => 'nullable|url',
            'category_id' => 'nullable|exists:categories,id',
            'city_id' => 'nullable|exists:cities,id',
            'limit' => 'nullable|integer|min:1|max:5000',
            'dry_run' => 'nullable|boolean',
        ]);

        $subs = PushSubscription::query()
            ->when($data['category_id'] ?? null, fn ($q, $c) => $q->where(fn ($i) => $i->whereNull('category_id')->orWhere('category_id', $c)))
            ->when($data['city_id'] ?? null, fn ($q, $c) => $q->where(fn ($i) => $i->whereNull('city_id')->orWhere('city_id', $c)))
            ->limit($data['limit'] ?? 1000)
            ->get();

        if ($request->boolean('dry_run')) {
            return response()->json(['success' => true, 'dry_run' => true, 'recipients' => $subs->count()]);
        }

        $payload = [
            'title' => $data['title'],
            'body' => $data['body'],
            'url' => $data['url'] ?? url('/'),
            'tag' => 'mcp-'.Str::random(6),
            'icon' => asset('icons/icon-192x192.png'),
            'badge' => asset('icons/icon-192x192.png'),
        ];

        try {
            $summary = $push->broadcast($subs, $payload);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'recipients' => $subs->count(), 'result' => $summary]);
    }

    // ─── Taxonomy management (missing from public API) ──────────────────────

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $cat = Category::findOrFail($id);
        $data = $request->validate(['name' => 'sometimes|string|max:255', 'slug' => 'sometimes|string|max:255', 'icon_name' => 'sometimes|nullable|string|max:100']);
        if (isset($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        }
        $cat->update($data);

        return response()->json(['success' => true, 'data' => $cat->fresh()->loadCount('jobs')]);
    }

    public function destroyCategory(Request $request, int $id): JsonResponse
    {
        $cat = Category::findOrFail($id);
        $jobs = $cat->jobs()->count();
        if ($jobs > 0 && ! $request->filled('reassign_to')) {
            return response()->json(['success' => false, 'message' => "Category has {$jobs} job(s). Pass reassign_to=<category_id>."], 422);
        }
        if ($request->filled('reassign_to')) {
            Category::findOrFail($request->reassign_to);
            $cat->jobs()->update(['category_id' => $request->reassign_to]);
        }
        $cat->delete();

        return response()->json(['success' => true, 'reassigned_jobs' => $jobs]);
    }

    public function mergeCategories(Request $request): JsonResponse
    {
        $request->validate(['source_ids' => 'required|array|min:1', 'source_ids.*' => 'integer|exists:categories,id', 'target_id' => 'required|integer|exists:categories,id']);
        $sources = array_diff($request->source_ids, [$request->target_id]);
        $moved = JobListing::whereIn('category_id', $sources)->update(['category_id' => $request->target_id]);
        Category::whereIn('id', $sources)->delete();

        return response()->json(['success' => true, 'moved_jobs' => $moved, 'deleted_categories' => count($sources)]);
    }

    public function updateCity(Request $request, int $id): JsonResponse
    {
        $city = City::findOrFail($id);
        $data = $request->validate(['name' => 'sometimes|string|max:255', 'slug' => 'sometimes|string|max:255']);
        if (isset($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        }
        $city->update($data);

        return response()->json(['success' => true, 'data' => $city->fresh()->loadCount('jobs')]);
    }

    public function destroyCity(Request $request, int $id): JsonResponse
    {
        $city = City::findOrFail($id);
        $jobs = $city->jobs()->count();
        if ($jobs > 0 && ! $request->filled('reassign_to')) {
            return response()->json(['success' => false, 'message' => "City has {$jobs} job(s). Pass reassign_to=<city_id>."], 422);
        }
        if ($request->filled('reassign_to')) {
            City::findOrFail($request->reassign_to);
            $city->jobs()->update(['city_id' => $request->reassign_to]);
        }
        $city->delete();

        return response()->json(['success' => true, 'reassigned_jobs' => $jobs]);
    }

    // ─── Scraper queue extras ───────────────────────────────────────────────

    public function scraperQueueSearch(Request $request): JsonResponse
    {
        $query = JobSourceImage::query()->orderByDesc('id');
        if ($request->filled('status')) {
            $query->where('publish_status', $request->status);
        }
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$request->q}%")->orWhere('source_page_url', 'like', "%{$request->q}%"));
        }
        if ($request->filled('source')) {
            $query->where('source_page_url', 'like', "%{$request->source}%");
        }

        $page = $query->paginate(min((int) $request->input('per_page', 20), 100));
        $page->getCollection()->transform(fn ($i) => [
            'id' => $i->id,
            'title' => $i->title,
            'source_page_url' => $i->source_page_url,
            'has_image' => (bool) $i->local_image_path,
            'image_url' => $i->local_image_path ? asset('storage/'.$i->local_image_path) : $i->source_image_url,
            'publish_status' => $i->publish_status,
            'published_job_id' => $i->published_job_id,
            'article_excerpt' => Str::limit(strip_tags((string) $i->article_text), 200),
            'created_at' => $i->created_at,
        ]);

        return response()->json(['success' => true] + $page->toArray());
    }

    public function scraperQueueBulk(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:job_source_images,id',
            'status' => 'required|in:pending,published,skipped,failed',
        ]);
        $data = ['publish_status' => $request->status];
        if ($request->status === 'pending') {
            $data += ['published_job_id' => null, 'published_at' => null];
        }
        $n = JobSourceImage::whereIn('id', $request->ids)->update($data);

        return response()->json(['success' => true, 'updated' => $n]);
    }

    public function scraperQueuePurge(Request $request): JsonResponse
    {
        $request->validate(['status' => 'required|in:skipped,failed,published', 'older_than_days' => 'nullable|integer|min:0']);
        $q = JobSourceImage::where('publish_status', $request->status);
        if ($request->filled('older_than_days')) {
            $q->where('created_at', '<', now()->subDays((int) $request->older_than_days));
        }
        $count = $q->count();
        if (! $request->boolean('dry_run')) {
            $q->delete();
        }

        return response()->json(['success' => true, 'dry_run' => $request->boolean('dry_run'), 'affected' => $count]);
    }

    // ─── Analytics ──────────────────────────────────────────────────────────

    public function analytics(Request $request): JsonResponse
    {
        $days = min((int) $request->input('days', 30), 365);
        $since = now()->subDays($days)->startOfDay();
        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'sqlite' ? "strftime('%Y-%m-%d', created_at)" : 'DATE(created_at)';

        $perDay = JobListing::where('created_at', '>=', $since)
            ->select(DB::raw("{$dateExpr} as day"), DB::raw('COUNT(*) as count'))
            ->groupBy('day')->orderBy('day')->pluck('count', 'day');

        $topFields = [];
        foreach (['sector', 'province', 'education', 'newspaper', 'job_type', 'testing_service', 'department', 'company_name'] as $field) {
            $topFields[$field] = JobListing::where('is_active', true)->whereNotNull($field)->where($field, '!=', '')
                ->select($field, DB::raw('COUNT(*) as count'))->groupBy($field)->orderByDesc('count')->limit(10)->pluck('count', $field);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'window_days' => $days,
                'jobs_created_per_day' => $perDay,
                'jobs_created_in_window' => $perDay->sum(),
                'expiring_next_7d' => JobListing::where('is_active', true)->whereBetween('deadline', [now()->toDateString(), now()->addDays(7)->toDateString()])->count(),
                'top' => $topFields,
                'flags' => collect([
                    'is_featured', 'is_premium', 'is_overseas', 'is_remote', 'has_walkin_interview',
                    'is_whatsapp_apply', 'is_retired_army', 'is_student_friendly', 'has_accommodation',
                    'has_transport', 'has_medical_insurance', 'is_special_quota', 'is_minority_quota',
                ])->mapWithKeys(fn ($f) => [$f => JobListing::where('is_active', true)->where($f, true)->count()]),
                'scraper_queue' => JobSourceImage::select('publish_status', DB::raw('COUNT(*) as count'))->groupBy('publish_status')->pluck('count', 'publish_status'),
            ],
        ]);
    }
}
