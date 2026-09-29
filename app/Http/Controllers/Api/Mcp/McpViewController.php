<?php

namespace App\Http\Controllers\Api\Mcp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class McpViewController extends Controller
{
    private const BLOCKED = ['vendor/', 'filament/', 'errors/'];

    private function resolve(string $path, bool $mustExist = true): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        abort_unless(str_ends_with($path, '.blade.php'), 422, 'Only .blade.php files allowed.');
        abort_if(str_contains($path, '..'), 422, 'Invalid path.');
        foreach (self::BLOCKED as $b) {
            abort_if(str_starts_with($path, $b), 403, "Editing {$b} is not allowed.");
        }

        $base = realpath(resource_path('views'));
        $full = $base . DIRECTORY_SEPARATOR . $path;

        if ($mustExist) {
            $real = realpath($full);
            abort_unless($real && str_starts_with($real, $base . DIRECTORY_SEPARATOR), 404, 'View not found.');
            return $real;
        }

        $dir = realpath(dirname($full)) ?: null;
        abort_unless($dir && str_starts_with($dir . DIRECTORY_SEPARATOR, $base . DIRECTORY_SEPARATOR), 422, 'Folder must already exist inside views.');
        abort_if(file_exists($full), 409, 'File already exists.');
        return $full;
    }

    private function relative(string $full): string
    {
        return ltrim(str_replace(realpath(resource_path('views')), '', $full), DIRECTORY_SEPARATOR);
    }

    private function lint(string $blade): ?string
    {
        $base = tempnam(sys_get_temp_dir(), 'blade_');
        $tmp = $base . '.php';
        file_put_contents($tmp, Blade::compileString($blade));
        $p = new Process([PHP_BINARY, '-l', $tmp]);
        $p->run();
        @unlink($tmp);
        @unlink($base);
        return $p->isSuccessful() ? null : trim($p->getOutput() . $p->getErrorOutput());
    }

    private function backup(string $full): string
    {
        $dir = storage_path('app/view-backups/' . dirname($this->relative($full)));
        File::ensureDirectoryExists($dir);
        $name = basename($full) . '.' . now()->format('Ymd_His') . '.bak';
        File::copy($full, "{$dir}/{$name}");
        return trim(dirname($this->relative($full)) . "/{$name}", './');
    }

    public function list(Request $request)
    {
        $filter = (string) $request->query('filter', '');
        $files = collect(File::allFiles(resource_path('views')))
            ->map(fn ($f) => str_replace('\\', '/', $f->getRelativePathname()))
            ->filter(fn ($p) => str_ends_with($p, '.blade.php'))
            ->reject(fn ($p) => collect(self::BLOCKED)->contains(fn ($b) => str_starts_with($p, $b)))
            ->filter(fn ($p) => $filter === '' || str_contains($p, $filter))
            ->values();

        return response()->json(['success' => true, 'count' => $files->count(), 'files' => $files]);
    }

    public function read(Request $request)
    {
        $full = $this->resolve((string) $request->query('path'));
        return response()->json([
            'success' => true,
            'path'    => $this->relative($full),
            'content' => File::get($full),
        ]);
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

        if ($err = $this->lint($updated)) {
            return response()->json(['success' => false, 'error' => 'Syntax check failed, nothing saved.', 'details' => $err], 422);
        }

        $backup = $this->backup($full);
        File::put($full, $updated);
        Artisan::call('view:clear');

        return response()->json(['success' => true, 'path' => $this->relative($full), 'backup' => $backup]);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'path'    => 'required|string',
            'content' => 'required|string|max:200000',
        ]);

        $full = $this->resolve($data['path'], false);

        if ($err = $this->lint($data['content'])) {
            return response()->json(['success' => false, 'error' => 'Syntax check failed, nothing saved.', 'details' => $err], 422);
        }

        File::put($full, $data['content']);
        Artisan::call('view:clear');

        return response()->json(['success' => true, 'path' => $this->relative($full)]);
    }

    public function backups(Request $request)
    {
        $dir = storage_path('app/view-backups');
        $files = File::isDirectory($dir)
            ? collect(File::allFiles($dir))->map(fn ($f) => str_replace('\\', '/', $f->getRelativePathname()))->sortDesc()->values()
            : collect();

        $filter = (string) $request->query('filter', '');
        if ($filter !== '') {
            $files = $files->filter(fn ($p) => str_contains($p, $filter))->values();
        }

        return response()->json(['success' => true, 'backups' => $files->take(50)]);
    }

    public function restore(Request $request)
    {
        $data = $request->validate(['backup' => 'required|string']);
        abort_if(str_contains($data['backup'], '..'), 422, 'Invalid path.');

        $src = storage_path('app/view-backups/' . ltrim($data['backup'], '/'));
        abort_unless(File::exists($src), 404, 'Backup not found.');

        $original = preg_replace('/\.\d{8}_\d{6}\.bak$/', '', ltrim($data['backup'], '/'));
        $full = $this->resolve($original);

        $this->backup($full);
        File::copy($src, $full);
        Artisan::call('view:clear');

        return response()->json(['success' => true, 'restored' => $this->relative($full)]);
    }
}
