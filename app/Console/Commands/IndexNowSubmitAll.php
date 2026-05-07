<?php

namespace App\Console\Commands;

use App\Models\JobListing;
use App\Services\IndexNowService;
use Illuminate\Console\Command;

class IndexNowSubmitAll extends Command
{
    protected $signature = 'indexnow:submit-all {--limit=500 : Max URLs per batch}';

    protected $description = 'Submit all active job URLs to IndexNow in batches';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $host = parse_url(config('app.url'), PHP_URL_HOST);
        $key  = config('indexnow.key');

        $this->line("Host: {$host}");
        $this->line("Key: {$key}");
        $this->line("Key file: " . url("/{$key}.txt"));
        $this->line("Endpoint: " . config('indexnow.endpoint'));
        $this->newLine();

        $jobs = JobListing::where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->get(['slug']);

        if ($jobs->isEmpty()) {
            $this->info('No active jobs found.');
            return self::SUCCESS;
        }

        $urls = $jobs->map(fn (JobListing $job) => url('/jobs/' . $job->slug))->toArray();

        $chunks = array_chunk($urls, $limit);

        $this->info("Submitting {$jobs->count()} URL(s) in " . count($chunks) . " batch(es)...");

        $allOk = true;
        foreach ($chunks as $i => $chunk) {
            $result = IndexNowService::submitBatch($chunk);
            if ($result['ok']) {
                $this->info("  Batch " . ($i + 1) . ": OK (" . count($chunk) . " URLs)");
            } else {
                $allOk = false;
                $this->error("  Batch " . ($i + 1) . ": FAILED (" . count($chunk) . " URLs)");
                $this->error("  Error: " . ($result['error'] ?? 'Unknown'));
            }
        }

        $this->newLine();
        $this->info('Done.');
        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
