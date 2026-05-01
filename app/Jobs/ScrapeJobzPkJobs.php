<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScrapeJobzPkJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;
    public $tries = 1;

    public bool $onlyLinks;
    public ?int $imageId;
    public ?int $limit;

    public function __construct(bool $onlyLinks = false, ?int $imageId = null, ?int $limit = null)
    {
        $this->onlyLinks = $onlyLinks;
        $this->imageId = $imageId;
        $this->limit = $limit;
    }

    public function handle(): void
    {
        $params = [];
        if ($this->onlyLinks) {
            $params['--only-links'] = true;
        }
        if ($this->imageId) {
            $params['--image-id'] = $this->imageId;
        }
        if ($this->limit) {
            $params['--limit'] = $this->limit;
        }

        Artisan::call('scrape:jobz-pk', $params);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[ScrapeJobzPkJobs] queue job failed', [
            'error' => $exception->getMessage(),
        ]);

        Cache::put('scraper_progress_jobz', [
            'current' => 0,
            'total' => 0,
            'status' => 'error',
            'message' => 'Job failed: ' . $exception->getMessage(),
        ], 600);
    }
}
