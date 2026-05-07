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

        foreach ($chunks as $i => $chunk) {
            $ok = IndexNowService::submitBatch($chunk);
            $status = $ok ? 'OK' : 'FAILED';
            $this->line("  Batch " . ($i + 1) . ": {$status} (" . count($chunk) . " URLs)");
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
