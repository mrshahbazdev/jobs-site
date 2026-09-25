<?php

namespace App\Http\Controllers\Api\Mcp;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Cv;
use App\Models\HomeBlock;
use App\Models\LandingGroup;
use App\Models\LandingLink;
use App\Models\Post;
use App\Models\PushSubscription;
use App\Models\Setting;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class McpContentController extends Controller
{
    // ─── Blog posts ─────────────────────────────────────────────────────────

    public function posts(Request $request): JsonResponse
    {
        $query = Post::query()->latest();
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$request->q}%")->orWhere('content', 'like', "%{$request->q}%"));
        }
        if ($request->has('published')) {
            $query->where('is_published', $request->boolean('published'));
        }
        $posts = $query->paginate(min((int) $request->input('per_page', 20), 100));
        $posts->getCollection()->transform(fn (Post $p) => $this->postSummary($p, $request->boolean('with_content')));

        return response()->json(['success' => true] + $posts->toArray());
    }

    public function showPost(string $idOrSlug): JsonResponse
    {
        $post = is_numeric($idOrSlug) ? Post::findOrFail($idOrSlug) : Post::where('slug', $idOrSlug)->firstOrFail();

        return response()->json(['success' => true, 'data' => $this->postSummary($post, true)]);
    }

    public function storePost(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|string|max:500',
            'meta_description' => 'nullable|string|max:255',
            'is_published' => 'nullable|boolean',
        ]);

        try {
            $data['slug'] = $this->uniqueSlug(Post::class, $data['slug'] ?? $data['title']);
            $data['is_published'] = $data['is_published'] ?? false;
            $post = Post::create($data);

            return response()->json(['success' => true, 'data' => $this->postSummary($post, true)], 201);
        } catch (\Throwable $e) {
            Log::error('MCP '.$request->path(), ['msg' => $e->getMessage(), 'at' => $e->getFile().':'.$e->getLine()]);

            return response()->json(['success' => false, 'error' => 'Server Error'], 500);
        }
    }

    public function updatePost(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'image' => 'nullable|string|max:500',
            'meta_description' => 'nullable|string|max:255',
            'is_published' => 'sometimes|boolean',
        ]);

        try {
            if (isset($data['slug']) && $data['slug'] !== $post->slug) {
                $data['slug'] = $this->uniqueSlug(Post::class, $data['slug'], $post->id);
            }
            $post->update($data);

            return response()->json(['success' => true, 'data' => $this->postSummary($post->fresh(), true)]);
        } catch (\Throwable $e) {
            Log::error('MCP '.$request->path(), ['msg' => $e->getMessage(), 'at' => $e->getFile().':'.$e->getLine()]);

            return response()->json(['success' => false, 'error' => 'Server Error'], 500);
        }
    }

    public function destroyPost(int $id): JsonResponse
    {
        Post::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Post deleted.']);
    }

    // ─── Settings (key/value: ads, header tags, etc.) ───────────────────────

    public function settings(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Setting::orderBy('key')->get(['id', 'key', 'value', 'updated_at'])]);
    }

    public function upsertSettings(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => 'required|array|min:1',
            'settings.*.key' => 'required|string|max:191',
            'settings.*.value' => 'present|nullable|string',
        ]);

        $saved = [];
        foreach ($request->settings as $item) {
            $saved[] = Setting::updateOrCreate(['key' => $item['key']], ['value' => $item['value'] ?? '']);
        }

        return response()->json(['success' => true, 'updated' => count($saved), 'data' => $saved]);
    }

    public function destroySetting(string $key): JsonResponse
    {
        $deleted = Setting::where('key', $key)->delete();

        return response()->json(['success' => $deleted > 0, 'deleted' => $deleted]);
    }

    // ─── Comments moderation ────────────────────────────────────────────────

    public function comments(Request $request): JsonResponse
    {
        $query = Comment::with(['user:id,name,email', 'jobListing:id,title,slug'])->latest();
        $status = $request->input('status', 'pending');
        if ($status === 'pending') {
            $query->where('is_approved', false);
        } elseif ($status === 'approved') {
            $query->where('is_approved', true);
        }
        if ($request->filled('job_id')) {
            $query->where('job_listing_id', $request->job_id);
        }

        return response()->json(['success' => true] + $query->paginate(min((int) $request->input('per_page', 20), 100))->toArray());
    }

    public function moderateComments(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:comments,id',
            'action' => 'required|in:approve,reject,delete',
        ]);

        $count = match ($request->action) {
            'approve' => Comment::whereIn('id', $request->ids)->update(['is_approved' => true]),
            'reject' => Comment::whereIn('id', $request->ids)->update(['is_approved' => false]),
            'delete' => Comment::whereIn('id', $request->ids)->delete(),
        };

        return response()->json(['success' => true, 'action' => $request->action, 'affected' => $count]);
    }

    // ─── Subscribers (email/WhatsApp alerts) ────────────────────────────────

    public function subscribers(Request $request): JsonResponse
    {
        $query = Subscriber::with(['category:id,name', 'city:id,name'])->latest();
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('email_or_whatsapp', 'like', "%{$request->q}%")->orWhere('name', 'like', "%{$request->q}%"));
        }
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        return response()->json([
            'success' => true,
            'summary' => [
                'total' => Subscriber::count(),
                'active' => Subscriber::where('is_active', true)->count(),
                'email' => Subscriber::where('email_or_whatsapp', 'like', '%@%')->count(),
                'whatsapp' => Subscriber::where('email_or_whatsapp', 'not like', '%@%')->count(),
            ],
        ] + $query->paginate(min((int) $request->input('per_page', 20), 100))->toArray());
    }

    public function updateSubscriber(Request $request, int $id): JsonResponse
    {
        $sub = Subscriber::findOrFail($id);
        $sub->update($request->validate([
            'is_active' => 'sometimes|boolean',
            'name' => 'sometimes|nullable|string|max:255',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'city_id' => 'sometimes|nullable|exists:cities,id',
        ]));

        return response()->json(['success' => true, 'data' => $sub->fresh()]);
    }

    public function destroySubscriber(int $id): JsonResponse
    {
        Subscriber::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    // ─── Landing groups & links (homepage navigation) ───────────────────────

    public function landing(): JsonResponse
    {
        $groups = LandingGroup::with(['links' => fn ($q) => $q->orderBy('sort_order')])->orderBy('sort_order')->get();

        return response()->json(['success' => true, 'data' => $groups]);
    }

    public function updateLandingGroup(Request $request, int $id): JsonResponse
    {
        $group = LandingGroup::findOrFail($id);
        $group->update($request->validate([
            'name' => 'sometimes|string|max:255',
            'sub_label' => 'sometimes|nullable|string|max:255',
            'icon' => 'sometimes|nullable|string|max:255',
            'sort_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
            'section_type' => 'sometimes|in:grid,strip,industry',
        ]));

        return response()->json(['success' => true, 'data' => $group->fresh('links')]);
    }

    public function destroyLandingGroup(int $id): JsonResponse
    {
        LandingGroup::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function storeLandingLink(Request $request): JsonResponse
    {
        $data = $request->validate([
            'landing_group_id' => 'required|exists:landing_groups,id',
            'title' => 'required|string|max:255',
            'url' => 'nullable|string|max:500',
            'route_name' => 'nullable|string|max:255',
            'route_param' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        $link = LandingLink::create($data + ['sort_order' => $data['sort_order'] ?? 0, 'is_active' => $data['is_active'] ?? true]);

        return response()->json(['success' => true, 'data' => $link], 201);
    }

    public function updateLandingLink(Request $request, int $id): JsonResponse
    {
        $link = LandingLink::findOrFail($id);
        $link->update($request->validate([
            'landing_group_id' => 'sometimes|exists:landing_groups,id',
            'title' => 'sometimes|string|max:255',
            'url' => 'sometimes|nullable|string|max:500',
            'route_name' => 'sometimes|nullable|string|max:255',
            'route_param' => 'sometimes|nullable|string|max:255',
            'icon' => 'sometimes|nullable|string|max:255',
            'sort_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]));

        return response()->json(['success' => true, 'data' => $link->fresh()]);
    }

    public function destroyLandingLink(int $id): JsonResponse
    {
        LandingLink::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    // ─── Home blocks (page builder) ─────────────────────────────────────────

    public function homeBlocks(Request $request): JsonResponse
    {
        $query = HomeBlock::orderBy('sort_order');
        if ($request->filled('page_slug')) {
            $query->where('page_slug', $request->page_slug);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function storeHomeBlock(Request $request): JsonResponse
    {
        $block = HomeBlock::create($this->validateHomeBlock($request, true));

        return response()->json(['success' => true, 'data' => $block], 201);
    }

    public function updateHomeBlock(Request $request, int $id): JsonResponse
    {
        $block = HomeBlock::findOrFail($id);
        $block->update($this->validateHomeBlock($request, false));

        return response()->json(['success' => true, 'data' => $block->fresh()]);
    }

    public function destroyHomeBlock(int $id): JsonResponse
    {
        HomeBlock::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function reorderHomeBlocks(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer|exists:home_blocks,id']);
        DB::transaction(function () use ($request) {
            foreach ($request->ids as $i => $id) {
                HomeBlock::where('id', $id)->update(['sort_order' => $i]);
            }
        });

        return response()->json(['success' => true, 'order' => $request->ids]);
    }

    // ─── Users / CVs (read-only insights) ───────────────────────────────────

    public function users(Request $request): JsonResponse
    {
        $query = User::withCount(['cvs', 'bookmarks', 'comments'])->latest();
        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$request->q}%")->orWhere('email', 'like', "%{$request->q}%"));
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        return response()->json([
            'success' => true,
            'summary' => [
                'total' => User::count(),
                'by_role' => User::select('role', DB::raw('count(*) as count'))->groupBy('role')->pluck('count', 'role'),
                'new_last_7d' => User::where('created_at', '>=', now()->subDays(7))->count(),
                'cvs' => Cv::count(),
                'public_cvs' => Cv::where('is_public', true)->count(),
                'push_subscriptions' => PushSubscription::count(),
            ],
        ] + $query->paginate(min((int) $request->input('per_page', 20), 100))->toArray());
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function postSummary(Post $p, bool $withContent): array
    {
        return array_filter([
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'url' => url('/blog/'.$p->slug),
            'image' => $p->image,
            'meta_description' => $p->meta_description,
            'poster_path' => $p->poster_path,
            'is_published' => (bool) $p->is_published,
            'word_count' => str_word_count(strip_tags((string) $p->content)),
            'content' => $withContent ? $p->content : null,
            'created_at' => $p->created_at,
            'updated_at' => $p->updated_at,
        ], fn ($v) => $v !== null);
    }

    private function uniqueSlug(string $model, string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: Str::random(8);
        $slug = $base;
        $i = 1;
        while ($model::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function validateHomeBlock(Request $request, bool $creating): array
    {
        $req = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'page_slug' => 'sometimes|string|max:100',
            'type' => "{$req}|string|max:100",
            'title' => 'sometimes|nullable|string|max:255',
            'url' => 'sometimes|nullable|string|max:500',
            'list_source' => 'sometimes|nullable|string|max:100',
            'display_type' => 'sometimes|string|max:50',
            'heading_text' => 'sometimes|nullable|string|max:255',
            'sub_text' => 'sometimes|nullable|string',
            'job_count' => 'sometimes|nullable|integer|min:1|max:100',
            'show_sidebar' => 'sometimes|boolean',
            'variant' => 'sometimes|nullable|string|max:100',
            'icon' => 'sometimes|nullable|string|max:100',
            'cards' => 'sometimes|nullable|array',
            'settings' => 'sometimes|nullable|array',
            'sort_order' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);
    }
}
