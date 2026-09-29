<?php

namespace App\Http\Controllers\Api\Mcp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class McpFileController extends Controller
{
    // Sirf in folders/files ke andar kaam hoga
    private const ALLOWED_ROOTS = [
        'app/', 'routes/', 'resources/views/', 'resources/css/', 'resources/js/',
        'config/', 'database/migrations/', 'database/seeders/', 'lang/', 'public/',
    ];

    // Yeh kabhi touch nahi honge
    private const BLOCKED = [
        '.env', 'vendor/', 'storage/', 'bootstrap/', 'node_modules/', '.git/',
        'public/storage', 'public/build/', 'config/database.php', 'config/app.php',
    ];

    private const EXTENSIONS = ['php', 'js', 'css', 'txt', 'json', 'xml', 'md', 'html', 'svg', 'webmanifest'];

    /* ---------------- helpers ---------------- */

    private function resolve(string $path, bool $mustExist = true): string
    {
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');

        abort_if($path === '' || str_contains($path, '..') || str_contains($path, "\0"), 422, 'Invalid path.');
        foreach (self::BLOCKED as $b) {
            abort_if($path === rtrim($b, '/') || str_starts_with($path, $b), 403, "Access to {$b} is blocked.");
        }
        abort_unless(collect(self::ALLOWED_ROOTS)->contains(fn ($r) => str_starts_with($path, $r)), 403, 'Path is outside allowed folders.');

        $ext = str_ends_with($path, '.blade.php') ? 'php' : strtolower(pathinfo($path, PATHINFO_EXTENSION));
        abort_unless(in_array($ext, self::EXTENSIONS, true), 422, "Extension .{$ext} not allowed.");

        $base = realpath(base_path());
        $full = $base . DIRECTORY_SEPARATOR . $path;

        if ($mustExist) {
            $real = realpath($full);
            abort_unless($real && str_starts_with($real, $base . DIRECTORY_SEPARATOR), 404, 'File not found.');
            return $real;
        }

        abort_if(file_exists($full), 409, 'File already exists — use files_replace.');
        $dir = dirname($full);
        if (! is_dir($dir)) {
            File::ensureDirectoryExists($dir);
        }
        abort_unless(str_starts_with(realpath($dir) . DIRECTORY_SEPARATOR, $base . DIRECTORY_SEPARATOR), 422, 'Invalid folder.');
        return $full;
    }

    private function rel(string $full): string
    {
        return ltrim(str_replace([realpath(base_path()), '\\'], ['', '/'], $full), '/');
    }

    private function lint(string $path, string $content): ?string
    {
        if (! str_ends_with($path, '.php')) {
            if (str_ends_with($path, '.json')) {
                json_decode($content);
                return json_last_error() ? 'Invalid JSON: ' . json_last_error_msg() : null;
            }
            return null;
        }

        $php = str_ends_with($path, '.blade.php') ? Blade::compileString($content) : $content;
        $base = tempnam(sys_get_temp_dir(), 'mcp_');
        $tmp = $base . '.php';
        file_put_contents($tmp, $php);
        $p = new Process([PHP_BINARY, '-l', $tmp]);
        $p->run();
        @unlink($tmp);
        @unlink($base);

        return $p->isSuccessful() ? null : trim($p->getOutput() . $p->getErrorOutput());
    }

    private function backup(string $full): string
    {
        $rel = $this->rel($full);
        $dir = storage_path('app/mcp-backups/' . dirname($rel));
        File::ensureDirectoryExists($dir);
        $name = basename($full) . '.' . now()->format('Ymd_His') . '.bak';
        File::copy($full, "{$dir}/{$name}");
        return ltrim(dirname($rel) . '/' . $name, './');
    }

    private function afterWrite(string $rel, string $action): void
    {
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        if (str_starts_with($rel, 'resources/views/')) {
            Artisan::call('view:clear');
        } elseif (str_starts_with($rel, 'routes/')) {
            Artisan::call('route:clear');
        } elseif (str_starts_with($rel, 'config/')) {
            Artisan::call('config:clear');
        }
        Log::build(['driver' => 'single', 'path' => storage_path('logs/mcp-files.log')])
            ->info("{$action}: {$rel}", ['ip' => request()->ip()]);
    }

    /* ---------------- tools ---------------- */

    public function list(Request $request)
    {
        $dir = trim((string) $request->query('dir', 'app/'), '/') . '/';
        $this->resolveDir($dir);

        $files = collect(File::allFiles(base_path($dir)))
            ->map(fn ($f) => $this->rel($f->getPathname()))
            ->reject(fn ($p) => collect(self::BLOCKED)->contains(fn ($b) => str_starts_with($p, $b)))
            ->filter(fn ($p) => ($q = $request->query('filter')) ? str_contains($p, $q) : true)
            ->values();

        return response()->json(['success' => true, 'count' => $files->count(), 'files' => $files->take(500)]);
    }

    private function resolveDir(string $dir): void
    {
        abort_if(str_contains($dir, '..'), 422, 'Invalid path.');
        foreach (self::BLOCKED as $b) {
            abort_if(str_starts_with($dir, $b), 403, "Access to {$b} is blocked.");
        }
        abort_unless(collect(self::ALLOWED_ROOTS)->contains(fn ($r) => str_starts_with($dir, $r)), 403, 'Folder not allowed.');
        abort_unless(is_dir(base_path($dir)), 404, 'Folder not found.');
    }

    public function read(Request $request)
    {
        $full = $this->resolve((string) $request->query('path'));
        return response()->json(['success' => true, 'path' => $this->rel($full), 'content' => File::get($full)]);
    }

    public function replace(Request $request)
    {
        $data = $request->validate([
            'path'    => 'required|string',
            'old_str' => 'required|string',
            'new_str' => 'present|string',
        ]);

        $full = $this->resolve($data['path']);
        $content = File::get($full);
        $count = substr_count($content, $data['old_str']);
        abort_if($count === 0, 422, 'old_str not found.');
        abort_if($count > 1, 422, "old_str found {$count} times; make it unique.");

        $updated = str_replace($data['old_str'], $data['new_str'], $content);
        $rel = $this->rel($full);

        if ($err = $this->lint($rel, $updated)) {
            return response()->json(['success' => false, 'error' => 'Syntax check failed, nothing saved.', 'details' => $err], 422);
        }

        $backup = $this->backup($full);
        File::put($full, $updated);
        $this->afterWrite($rel, 'replace');

        return response()->json(['success' => true, 'path' => $rel, 'backup' => $backup]);
    }

    public function create(Request $request)
    {
        $data = $request->validate(['path' => 'required|string', 'content' => 'required|string|max:300000']);

        $full = $this->resolve($data['path'], false);
        $rel = $this->rel($full);

        if ($err = $this->lint($rel, $data['content'])) {
            return response()->json(['success' => false, 'error' => 'Syntax check failed, nothing saved.', 'details' => $err], 422);
        }

        File::put($full, $data['content']);
        $this->afterWrite($rel, 'create');

        return response()->json(['success' => true, 'path' => $rel]);
    }

    public function backups(Request $request)
    {
        $dir = storage_path('app/mcp-backups');
        $files = File::isDirectory($dir)
            ? collect(File::allFiles($dir))->map(fn ($f) => str_replace('\\', '/', $f->getRelativePathname()))
            : collect();

        if ($q = $request->query('filter')) {
            $files = $files->filter(fn ($p) => str_contains($p, $q));
        }

        return response()->json(['success' => true, 'backups' => $files->sortDesc()->values()->take(50)]);
    }

    public function restore(Request $request)
    {
        $data = $request->validate(['backup' => 'required|string']);
        abort_if(str_contains($data['backup'], '..'), 422, 'Invalid path.');

        $src = storage_path('app/mcp-backups/' . ltrim($data['backup'], '/'));
        abort_unless(File::exists($src), 404, 'Backup not found.');

        $original = preg_replace('/\.\d{8}_\d{6}\.bak$/', '', ltrim($data['backup'], '/'));
        $full = $this->resolve($original);

        $this->backup($full);
        File::copy($src, $full);
        $this->afterWrite($this->rel($full), 'restore');

        return response()->json(['success' => true, 'restored' => $this->rel($full)]);
    }

    public function siteFetch(Request $request)
    {
        $path = '/' . ltrim((string) $request->query('path', '/'), '/');
        abort_if(str_contains($path, '..') || str_starts_with($path, '//'), 422, 'Invalid path.');

        $res = Http::withHeaders(['User-Agent' => $request->query('user_agent', 'JobsPic-MCP/1.0')])
            ->timeout(25)->withoutRedirecting()
            ->get(rtrim(config('app.url'), '/') . $path);

        return response()->json([
            'success'      => true,
            'status'       => $res->status(),
            'location'     => $res->header('Location'),
            'content_type' => $res->header('Content-Type'),
            'body'         => mb_substr($res->body(), 0, 200000),
        ]);
    }
}
