<?php

namespace App\Http\Controllers\Api\Mcp;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\City;
use App\Models\Comment;
use App\Models\Cv;
use App\Models\JobListing;
use App\Models\JobSourceImage;
use App\Models\Post;
use App\Models\PushSubscription;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Output\BufferedOutput;

class McpSystemController extends Controller
{
    public function health(): JsonResponse
    {
        $dbOk = true;
        $dbError = null;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbOk = false;
            $dbError = $e->getMessage();
        }

        $counts = [];
        if ($dbOk) {
            $counts = [
                'jobs' => JobListing::count(),
                'jobs_active' => JobListing::where('is_active', true)->count(),
                'categories' => Category::count(),
                'cities' => City::count(),
                'posts' => Post::count(),
                'subscribers' => Subscriber::count(),
                'push_subscriptions' => PushSubscription::count(),
                'users' => User::count(),
                'cvs' => Cv::count(),
                'comments_pending' => Comment::where('is_approved', false)->count(),
                'scraper_queue_pending' => JobSourceImage::pending()->count(),
                'queued_jobs' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : null,
                'failed_jobs' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'app' => [
                    'name' => config('app.name'),
                    'url' => config('app.url'),
                    'env' => config('app.env'),
                    'debug' => (bool) config('app.debug'),
                    'laravel' => app()->version(),
                    'php' => PHP_VERSION,
                    'timezone' => config('app.timezone'),
                    'maintenance' => app()->isDownForMaintenance(),
                ],
                'database' => [
                    'ok' => $dbOk,
                    'driver' => config('database.default'),
                    'error' => $dbError,
                ],
                'drivers' => [
                    'cache' => config('cache.default'),
                    'queue' => config('queue.default'),
                    'session' => config('session.driver'),
                    'mail' => config('mail.default'),
                ],
                'integrations' => [
                    'gemini' => (bool) config('services.gemini.key'),
                    'vapid' => (bool) config('services.webpush.vapid.public_key'),
                    'indexnow' => (bool) config('indexnow.enabled'),
                    'cron_secret' => (bool) env('CRON_SECRET'),
                ],
                'counts' => $counts,
                'scrapers' => $this->scraperStatuses(),
            ],
        ]);
    }

    public function schema(Request $request): JsonResponse
    {
        $only = $request->input('table');
        $tables = collect(Schema::getTableListing(schemaQualified: false))
            ->map(fn ($t) => is_array($t) ? ($t['name'] ?? '') : (string) $t)
            ->filter(fn ($t) => $t !== '' && (! $only || $t === $only))
            ->values();

        $result = $tables->mapWithKeys(function (string $table) use ($request) {
            $columns = collect(Schema::getColumns($table))->map(fn ($c) => [
                'name' => $c['name'],
                'type' => $c['type_name'] ?? $c['type'] ?? null,
                'nullable' => $c['nullable'] ?? null,
                'default' => $c['default'] ?? null,
            ])->values();

            $entry = ['columns' => $columns];
            if ($request->boolean('with_counts')) {
                try {
                    $entry['rows'] = DB::table($table)->count();
                } catch (\Throwable) {
                    $entry['rows'] = null;
                }
            }

            return [$table => $entry];
        });

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function routes(Request $request): JsonResponse
    {
        $filter = $request->input('filter');
        $routes = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($r) => [
                'methods' => array_values(array_diff($r->methods(), ['HEAD'])),
                'uri' => $r->uri(),
                'name' => $r->getName(),
                'action' => $r->getActionName(),
                'middleware' => $r->gatherMiddleware(),
            ])
            ->filter(fn ($r) => ! $filter || str_contains($r['uri'], $filter) || str_contains((string) $r['name'], $filter))
            ->values();

        return response()->json(['success' => true, 'count' => $routes->count(), 'data' => $routes]);
    }

    public function logs(Request $request): JsonResponse
    {
        $lines = min((int) $request->input('lines', 200), (int) config('mcp.log_max_lines'));
        $level = $request->input('level');
        $file = $request->input('file', 'laravel.log');
        $path = storage_path('logs/'.basename($file));

        if (! is_file($path)) {
            $available = array_map('basename', glob(storage_path('logs/*.log')) ?: []);

            return response()->json(['success' => false, 'message' => 'Log file not found.', 'available' => $available], 404);
        }

        $tail = $this->tailFile($path, $lines * 4);
        if ($level) {
            $tail = array_values(array_filter($tail, fn ($l) => stripos($l, '.'.strtoupper($level).':') !== false));
        }
        $tail = array_slice($tail, -$lines);

        return response()->json([
            'success' => true,
            'file' => basename($path),
            'size_bytes' => filesize($path),
            'lines' => $tail,
        ]);
    }

    public function sql(Request $request): JsonResponse
    {
        if (! config('mcp.sql.enabled')) {
            return response()->json(['success' => false, 'message' => 'Read-only SQL is disabled (MCP_SQL_ENABLED=false).'], 403);
        }

        $request->validate(['query' => 'required|string|max:5000']);
        $sql = trim(rtrim(trim($request->query('query', $request->input('query'))), ';'));

        if (! preg_match('/^\s*(select|with|explain|pragma|show|describe)\b/i', $sql) || str_contains($sql, ';')) {
            return response()->json(['success' => false, 'message' => 'Only a single read-only statement (SELECT / WITH / EXPLAIN) is allowed.'], 422);
        }
        if (preg_match('/\b(insert|update|delete|drop|alter|create|truncate|replace|attach|grant|revoke)\b/i', $sql)) {
            return response()->json(['success' => false, 'message' => 'Mutating keywords are not allowed.'], 422);
        }

        $max = (int) config('mcp.sql.max_rows');
        $started = microtime(true);
        try {
            $rows = DB::select($sql);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $truncated = count($rows) > $max;

        return response()->json([
            'success' => true,
            'row_count' => count($rows),
            'truncated' => $truncated,
            'duration_ms' => round((microtime(true) - $started) * 1000, 2),
            'rows' => array_slice($rows, 0, $max),
        ]);
    }

    public function artisan(Request $request): JsonResponse
    {
        $request->validate([
            'command' => 'required|string',
            'arguments' => 'nullable|array',
        ]);

        $command = $request->input('command');
        $allowed = config('mcp.artisan');

        if (! array_key_exists($command, $allowed)) {
            return response()->json([
                'success' => false,
                'message' => "Command '{$command}' is not allow-listed.",
                'allowed' => array_keys($allowed),
            ], 422);
        }

        $args = [];
        foreach ((array) $request->input('arguments', []) as $key => $value) {
            if (! in_array($key, $allowed[$command], true)) {
                return response()->json([
                    'success' => false,
                    'message' => "Argument '{$key}' is not permitted for '{$command}'.",
                    'allowed_arguments' => $allowed[$command],
                ], 422);
            }
            $args[$key] = $value;
        }

        $output = new BufferedOutput;
        $started = microtime(true);
        try {
            $exit = Artisan::call($command, $args, $output);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'command' => $command,
                'message' => $e->getMessage(),
                'output' => $output->fetch(),
            ], 500);
        }

        return response()->json([
            'success' => $exit === 0,
            'command' => $command,
            'arguments' => $args,
            'exit_code' => $exit,
            'duration_ms' => round((microtime(true) - $started) * 1000, 2),
            'output' => $output->fetch(),
        ]);
    }

    public function artisanCommands(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => config('mcp.artisan')]);
    }

    public function queue(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 20), 100);

        $pending = Schema::hasTable('jobs')
            ? DB::table('jobs')->orderBy('id')->limit($limit)->get()->map(fn ($j) => [
                'id' => $j->id,
                'queue' => $j->queue,
                'attempts' => $j->attempts,
                'job' => json_decode($j->payload, true)['displayName'] ?? null,
                'available_at' => date('c', $j->available_at),
                'created_at' => date('c', $j->created_at),
            ])
            : collect();

        $failed = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->orderByDesc('id')->limit($limit)->get()->map(fn ($j) => [
                'id' => $j->id,
                'uuid' => $j->uuid,
                'queue' => $j->queue,
                'job' => json_decode($j->payload, true)['displayName'] ?? null,
                'exception' => mb_substr($j->exception, 0, 600),
                'failed_at' => $j->failed_at,
            ])
            : collect();

        return response()->json([
            'success' => true,
            'data' => [
                'connection' => config('queue.default'),
                'pending_count' => Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
                'failed_count' => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
                'pending' => $pending,
                'failed' => $failed,
                'scrapers' => $this->scraperStatuses(),
            ],
        ]);
    }

    public function cache(Request $request): JsonResponse
    {
        $request->validate(['action' => 'required|in:clear,forget,get', 'key' => 'nullable|string']);

        return match ($request->action) {
            'clear' => response()->json(['success' => Cache::flush(), 'message' => 'Application cache flushed.']),
            'forget' => response()->json(['success' => Cache::forget($request->key), 'key' => $request->key]),
            default => response()->json(['success' => true, 'key' => $request->key, 'value' => Cache::get($request->key)]),
        };
    }

    private function scraperStatuses(): array
    {
        $map = [
            'pakistan-jobs' => 'scraper_progress',
            'jobsalert' => 'scraper_progress_jobsalert',
            'jobz-pk' => 'scraper_progress_jobz',
        ];
        $out = [];
        foreach ($map as $source => $key) {
            try {
                $out[$source] = Cache::get($key, ['status' => 'idle']);
            } catch (\Throwable) {
                $out[$source] = ['status' => 'unknown'];
            }
        }

        return $out;
    }

    private function tailFile(string $path, int $lines): array
    {
        $fp = fopen($path, 'rb');
        if (! $fp) {
            return [];
        }
        $buffer = '';
        $chunk = 8192;
        fseek($fp, 0, SEEK_END);
        $pos = ftell($fp);
        while ($pos > 0 && substr_count($buffer, "\n") <= $lines) {
            $read = min($chunk, $pos);
            $pos -= $read;
            fseek($fp, $pos);
            $buffer = fread($fp, $read).$buffer;
        }
        fclose($fp);

        $all = explode("\n", rtrim($buffer, "\n"));

        return array_slice($all, -$lines);
    }
}
