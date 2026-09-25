<?php

namespace App\Http\Controllers\Api\Mcp;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Streamable-HTTP MCP endpoint: JSON-RPC 2.0 over POST /api/mcp/rpc.
 * Serves the same tool surface as mcp/server.js — the tool registry is
 * generated from the Node server (mcp/manifest.js -> mcp/tools.json) so
 * both transports never drift apart.
 */
class McpRpcController extends Controller
{
    private const PROTOCOL_VERSION = '2025-03-26';

    private const RESOURCES = [
        ['uri' => 'jobs-site://health', 'name' => 'health', 'title' => 'Health', 'description' => 'Live health snapshot.', 'get' => '/api/mcp/health'],
        ['uri' => 'jobs-site://schema', 'name' => 'schema', 'title' => 'DB schema', 'get' => '/api/mcp/schema'],
        ['uri' => 'jobs-site://categories', 'name' => 'categories', 'title' => 'Categories', 'get' => '/api/categories'],
        ['uri' => 'jobs-site://cities', 'name' => 'cities', 'title' => 'Cities', 'get' => '/api/cities'],
        ['uri' => 'jobs-site://settings', 'name' => 'settings', 'title' => 'Site settings', 'get' => '/api/mcp/settings'],
        ['uri' => 'jobs-site://seo/audit', 'name' => 'seo-audit', 'title' => 'SEO audit', 'get' => '/api/mcp/seo/audit'],
        ['uri' => 'jobs-site://analytics', 'name' => 'analytics', 'title' => 'Analytics (30d)', 'get' => '/api/mcp/analytics?days=30'],
        ['uri' => 'jobs-site://artisan', 'name' => 'artisan-allowlist', 'title' => 'Artisan allow-list', 'get' => '/api/mcp/artisan'],
        ['uri' => 'jobs-site://sitemaps', 'name' => 'sitemaps', 'title' => 'Sitemaps', 'get' => '/api/mcp/sitemaps'],
        ['uri' => 'jobs-site://queue', 'name' => 'queue', 'title' => 'Queue status', 'get' => '/api/mcp/queue'],
        ['uri' => 'jobs-site://users', 'name' => 'users-summary', 'title' => 'Users summary', 'get' => '/api/mcp/users?per_page=1'],
    ];

    /** Tools whose arguments can't be passed through verbatim. */
    private function callOverride(string $name, array $args): ?array
    {
        return match ($name) {
            'scraper_status' => empty($args['source'])
                ? ['GET', '/api/scraper-status-all', []]
                : ['GET', '/api/scraper-status', ['source' => $args['source']]],
            'schedule_list' => ['POST', '/api/mcp/artisan', ['command' => 'schedule:list', 'arguments' => []]],
            'maintenance_mode' => ['POST', '/api/mcp/artisan', [
                'command' => $args['action'] ?? 'up',
                'arguments' => ($args['action'] ?? '') === 'down'
                    ? array_filter(['--secret' => $args['secret'] ?? null, '--retry' => isset($args['retry']) ? (string) $args['retry'] : null])
                    : [],
            ]],
            default => null,
        };
    }

    public function handle(Request $request): JsonResponse|Response
    {
        $payload = json_decode($request->getContent(), true);
        if (! is_array($payload)) {
            return response()->json($this->rpcError(null, -32700, 'Parse error'));
        }

        $batch = array_is_list($payload) ? $payload : [$payload];

        // Pure notification batches must not return a body.
        $responses = [];
        foreach ($batch as $msg) {
            if (! is_array($msg) || ! array_key_exists('id', $msg)) {
                continue; // notification
            }
            $responses[] = $this->dispatch($msg, $request);
        }
        if ($responses === []) {
            return response('', 202);
        }

        return response()->json(array_is_list($payload) ? $responses : $responses[0])
            ->header('Cache-Control', 'no-store');
    }

    private function dispatch(array $msg, Request $origin): array
    {
        $id = $msg['id'] ?? null;
        $method = $msg['method'] ?? null;
        $params = $msg['params'] ?? [];

        return match ($method) {
            'initialize' => $this->rpcOk($id, [
                'protocolVersion' => self::PROTOCOL_VERSION,
                'capabilities' => ['tools' => new \stdClass, 'resources' => new \stdClass, 'prompts' => new \stdClass],
                'serverInfo' => [
                    'name' => 'jobs-site-mcp',
                    'version' => '1.1.0',
                    'websiteUrl' => config('app.url'),
                ],
                'instructions' => 'JobsPic MCP control plane. Tools return JSON text + structuredContent; '
                    .'destructive tools carry destructiveHint. Prefer jobs_search/source_image_* over db_query.',
            ]),
            'ping' => $this->rpcOk($id, new \stdClass),
            'resources/templates/list' => $this->rpcOk($id, ['resourceTemplates' => []]),
            'logging/setLevel' => $this->rpcOk($id, new \stdClass),
            'completion/complete' => $this->rpcOk($id, ['completion' => ['values' => [], 'total' => 0, 'hasMore' => false]]),
            'tools/list' => $this->rpcOk($id, ['tools' => array_map(
                fn ($t) => [
                    'name' => $t->name,
                    'title' => $t->title,
                    'description' => $t->description,
                    'inputSchema' => $t->inputSchema,
                    'annotations' => $t->annotations ?? new \stdClass,
                ],
                $this->tools(),
            )]),
            'tools/call' => $this->callTool($id, $params, $origin),
            'resources/list' => $this->rpcOk($id, ['resources' => array_map(
                fn ($r) => array_intersect_key($r, array_flip(['uri', 'name', 'title', 'description'])) + ['mimeType' => 'application/json'],
                self::RESOURCES,
            )]),
            'resources/read' => $this->readResource($id, $params, $origin),
            'prompts/list' => $this->rpcOk($id, ['prompts' => array_map(fn ($p) => [
                'name' => $p['name'],
                'title' => $p['title'],
                'description' => $p['description'],
                'arguments' => $p['arguments'],
            ], self::PROMPTS)]),
            'prompts/get' => $this->getPrompt($id, $params, $origin),
            default => $this->rpcError($id, -32601, 'Method not found'),
        };
    }

    /** @return array<int, \stdClass> Raw objects — keeps empty {} (inputSchema.properties) as objects. */
    private function tools(): array
    {
        static $tools = null;
        if ($tools === null) {
            $tools = json_decode(file_get_contents(base_path('mcp/tools.json'))) ?: [];
        }

        return $tools;
    }

    // ─── tools/call ─────────────────────────────────────────────────────────

    private function callTool(mixed $id, array $params, Request $origin): array
    {
        $name = $params['name'] ?? null;
        $args = $params['arguments'] ?? [];
        $tool = collect($this->tools())->firstWhere('name', $name);
        if (! $tool) {
            return $this->rpcOk($id, $this->toolError("Unknown tool: {$name}"));
        }

        $override = $this->callOverride($name, $args);
        if ($override) {
            [$method, $path, $data] = $override;
            $query = [];
            $body = $data;
        } else {
            $method = $tool->http->method ?? null;
            $path = $tool->http->path ?? null;
            if (! $method || ! $path) {
                return $this->rpcOk($id, $this->toolError("Tool {$name} has no HTTP mapping."));
            }
            $path = preg_replace_callback('/\{(\w+)\}/', function ($m) use (&$args) {
                $v = $args[$m[1]] ?? '';
                unset($args[$m[1]]);

                return rawurlencode((string) $v);
            }, $path);
            $query = $method === 'GET' ? $args : [];
            $body = $method === 'GET' ? [] : $args;
        }

        $response = $this->subRequest($method, $path, $query, $body, $origin);
        $status = $response->getStatusCode();
        $data = json_decode($response->getContent(), true);
        $text = is_array($data) ? json_encode($data, JSON_PRETTY_PRINT) : $response->getContent();

        if ($status >= 400) {
            $err = $this->toolError(
                $data['message'] ?? $data['error'] ?? "HTTP {$status}",
                ['status' => $status] + (isset($data['errors']) ? ['errors' => $data['errors']] : []),
            );

            return $this->rpcOk($id, $err);
        }

        $result = ['content' => [['type' => 'text', 'text' => $text]]];
        if (is_array($data)) {
            // Responses already in MCP content-block form (e.g. source_image_view) pass through.
            if (isset($data['content']) && is_array($data['content'])) {
                return $this->rpcOk($id, $data);
            }
            $result['structuredContent'] = array_is_list($data) ? ['result' => $data] : $data;
        }

        return $this->rpcOk($id, $result);
    }

    // ─── resources ──────────────────────────────────────────────────────────

    private function readResource(mixed $id, array $params, Request $origin): array
    {
        $uri = $params['uri'] ?? '';
        $res = collect(self::RESOURCES)->firstWhere('uri', $uri);
        if (! $res) {
            return $this->rpcError($id, -32602, "Unknown resource: {$uri}");
        }
        $response = $this->subRequest('GET', $res['get'], [], [], $origin);

        return $this->rpcOk($id, ['contents' => [[
            'uri' => $uri,
            'mimeType' => 'application/json',
            'text' => $response->getContent(),
        ]]]);
    }

    // ─── prompts ────────────────────────────────────────────────────────────

    private const PROMPTS = [
        ['name' => 'daily_ops_checklist', 'title' => 'Daily operations checklist', 'description' => 'Walk through health, queue, scrapers, expired jobs, SEO gaps and push backlog, then propose actions.', 'arguments' => []],
        ['name' => 'publish_scraped_job', 'title' => 'Publish a scraped job', 'description' => 'Take a scraper-queue item and publish it as a fully enriched, SEO-ready job listing.', 'arguments' => [['name' => 'item_id', 'required' => false]]],
        ['name' => 'seo_improvement_plan', 'title' => 'SEO improvement plan', 'description' => 'Generate a prioritized SEO plan from the live audit and analytics.', 'arguments' => [['name' => 'focus', 'required' => false]]],
        ['name' => 'write_job_guide_post', 'title' => 'Write a job guide blog post', 'description' => 'Draft and publish a helpful blog post about a job category, exam, or hiring process.', 'arguments' => [['name' => 'topic', 'required' => true], ['name' => 'keyword', 'required' => false]]],
        ['name' => 'incident_triage', 'title' => 'Incident triage', 'description' => 'Investigate an error or outage using logs, queue, schema and read-only SQL.', 'arguments' => [['name' => 'symptom', 'required' => true]]],
        ['name' => 'job_from_ad_image', 'title' => 'Publish job from an ad image/text (no Gemini needed)', 'description' => 'Read a newspaper ad image or its text and publish a complete listing.', 'arguments' => [['name' => 'image_url', 'required' => false], ['name' => 'ad_text', 'required' => false]]],
        ['name' => 'weekly_content_plan', 'title' => 'Weekly content & growth plan', 'description' => 'Use analytics, bookmarks, subscribers and SEO audit to plan the week.', 'arguments' => []],
        ['name' => 'cv_review', 'title' => 'Review a user CV', 'description' => 'Critique and improve a CV builder document, optionally against a target job.', 'arguments' => [['name' => 'cv_id', 'required' => true], ['name' => 'job_id', 'required' => false]]],
        ['name' => 'cleanup_and_maintenance', 'title' => 'Storage & data cleanup', 'description' => 'Find orphan images, stale queue items, expired jobs and unused taxonomy, then clean safely.', 'arguments' => []],
    ];

    private function getPrompt(mixed $id, array $params, Request $origin): array
    {
        $name = $params['name'] ?? '';
        $args = $params['arguments'] ?? [];
        if (! collect(self::PROMPTS)->firstWhere('name', $name)) {
            return $this->rpcError($id, -32602, "Unknown prompt: {$name}");
        }

        $text = match ($name) {
            'daily_ops_checklist' => "You are operating the jobs-site platform via MCP. Perform a daily ops review:\n"
                ."1. site_overview + queue_status + logs_tail(level=ERROR) — flag failures.\n"
                ."2. scraper_status + scraper_queue_stats — are scrapers stuck? pending backlog?\n"
                ."3. jobs_deactivate_expired(dry_run=true) — how many to deactivate? Deactivate if reasonable.\n"
                ."4. seo_audit — summarise top issues; fix cheap ones with jobs_update / jobs_regenerate_schema.\n"
                ."5. push_stats — jobs_awaiting_push; run artisan_run push:send-new-jobs --dry-run first.\n"
                .'Finish with a short report and the concrete tool calls you executed.',
            'publish_scraped_job' => 'Publish '.(! empty($args['item_id']) ? "scraper queue item #{$args['item_id']}" : 'the next pending scraper queue item (scraper_queue_next)')." as a job listing.\n"
                .'Steps: read the item; use ai_extract_job on its text/HTML if Gemini is configured; resolve category via categories_resolve and city via cities_list '
                .'(create if missing); build a rich description_html, meta_description (<=160 chars), meta_keywords, deadline, salary, education, experience; '
                .'call jobs_create; then scraper_queue_set_status(status=published, published_job_id=<new id>) and indexnow_submit(job_ids=[<id>]).',
            'seo_improvement_plan' => 'Create a prioritized SEO improvement plan for the jobs site'.(! empty($args['focus']) ? " focused on {$args['focus']}" : '').'. '
                .'Use jobs_update, jobs_regenerate_schema, posts_create (supporting guides), landing_link_create (internal links) and indexnow_submit to apply fixes.',
            'write_job_guide_post' => "Write a well-structured HTML blog post for the jobs site about: {$args['topic']}. Primary keyword: ".($args['keyword'] ?? $args['topic'] ?? '').'. '
                .'Pull real, current job data with jobs_search to reference live listings (link to their URLs). Include H2 sections, a FAQ, and a call to action to subscribe. '
                .'Publish it with posts_create(is_published=true) and submit the URL via indexnow_submit.',
            'incident_triage' => "Triage this incident on the jobs site: \"{$args['symptom']}\".\n"
                .'Use site_overview, logs_tail(level=ERROR, lines=300), queue_status, routes_list, db_schema and db_query (read-only) to find the root cause. '
                .'Where safe, remediate with artisan_run (optimize:clear, queue:retry, migrate --force) or cache_manage. Report cause, fix, and follow-ups.',
            'job_from_ad_image' => "Publish a job listing from this advertisement without relying on ai_extract_job.\n"
                .(! empty($args['image_url']) ? "Image: {$args['image_url']}\n" : '')
                .(! empty($args['ad_text']) ? "Ad text:\n{$args['ad_text']}\n" : '')
                .'Steps: 1) Transcribe/extract: title, department/company, city, province, positions, qualification, experience, age, BPS, salary, deadline, how to apply, newspaper. '
                .'2) categories_resolve + cities_list (cities_create if missing). 3) Write SEO description_html (H2 sections: Overview, Vacancies, Eligibility, How to Apply, Important Dates), '
                .'meta_description <=160 chars, meta_keywords, set flags (is_special_quota, has_walkin_interview...). 4) jobs_create. '
                .(! empty($args['image_url']) ? '5) source_image_add(title, image_url, article_text=<transcription>) then source_image_publish(id, job_id). ' : '')
                .'6) indexnow_submit(job_ids=[id]) and sitemaps_flush. Report the job URL.',
            'weekly_content_plan' => 'Plan this week for the jobs site. Fetch analytics (days=7), bookmarks_stats (days=7), seo_audit and subscribers_list: '
                .'propose 3 blog posts (posts_create), 2 landing-page link groups (landing_link_create), which jobs to feature (jobs_toggle is_featured), '
                .'a push broadcast (push_broadcast) and whether to run alerts_send. Execute the low-risk items and list the rest for approval.',
            'cv_review' => "Review CV #{$args['cv_id']} via cvs_get".(! empty($args['job_id']) ? " against job #{$args['job_id']} (jobs_get)" : '').'. '
                .'Give an ATS-style score, list gaps, rewrite the summary and weak bullet points, suggest skills. '
                .'Apply improvements with cvs_update only if explicitly asked; otherwise return the proposed JSON changes.',
            'cleanup_and_maintenance' => 'Run a maintenance pass: storage_orphans (report first), scraper_queue_search(status=skipped, older than 30 days) -> scraper_queue_purge, '
                .'jobs_deactivate_expired(dry_run=true), categories_list / cities_list with zero jobs (do NOT delete without confirming), '
                .'queue_status failed jobs -> artisan_run queue:retry or queue:flush, then sitemaps_flush + cache_manage(clear). Summarise what was cleaned and what needs approval.',
            default => '',
        };

        return $this->rpcOk($id, [
            'description' => collect(self::PROMPTS)->firstWhere('name', $name)['description'],
            'messages' => [['role' => 'user', 'content' => ['type' => 'text', 'text' => $text]]],
        ]);
    }

    // ─── internals ──────────────────────────────────────────────────────────

    /** Dispatch an internal request through the HTTP kernel so routing, middleware and controllers are fully reused. */
    private function subRequest(string $method, string $path, array $query, array $body, Request $origin): Response
    {
        $url = url($path);
        if ($query) {
            $url .= '?'.http_build_query($query);
        }
        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.config('mcp.token'),
        ];
        $content = null;
        if ($method !== 'GET' && $body !== []) {
            $server['CONTENT_TYPE'] = 'application/json';
            $content = json_encode($body);
        }
        $sub = Request::create($url, $method, [], [], [], $server, $content);

        return app('Illuminate\Contracts\Http\Kernel')->handle($sub);
    }

    private function toolError(string $error, array $extra = []): array
    {
        $payload = ['success' => false, 'error' => $error] + $extra;

        return [
            'content' => [['type' => 'text', 'text' => json_encode($payload, JSON_PRETTY_PRINT)]],
            'structuredContent' => $payload,
            'isError' => true,
        ];
    }

    private function rpcOk(mixed $id, mixed $result): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }

    private function rpcError(mixed $id, int $code, string $message): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'error' => ['code' => $code, 'message' => $message]];
    }
}
