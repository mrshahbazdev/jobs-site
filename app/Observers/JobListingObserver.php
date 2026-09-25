<?php

namespace App\Observers;

use App\Models\JobListing;
use App\Services\IndexNowService;
use App\Services\JobPosterService;
use Illuminate\Support\Facades\Log;

class JobListingObserver
{
    public function saved(JobListing $job): void
    {
        $watch = ['title', 'slug', 'deadline', 'education', 'company_name', 'category_id', 'city_id', 'bps_scale'];
        if (! $job->poster_path || $job->wasChanged($watch) || $job->wasRecentlyCreated) {
            try {
                $path = app(JobPosterService::class)->generate($job);
                $job->updateQuietly(['poster_path' => $path]);
            } catch (\Throwable $e) {
                Log::error('Job poster generation failed', [
                    'job_id' => $job->id,
                    'msg' => $e->getMessage(),
                    'at' => $e->getFile().':'.$e->getLine(),
                ]);
            }
        }
    }

    public function created(JobListing $job): void
    {
        if ($job->is_active) {
            IndexNowService::submit(url('/jobs/'.$job->slug));
        }
    }

    public function updated(JobListing $job): void
    {
        if ($job->is_active) {
            IndexNowService::submit(url('/jobs/'.$job->slug));
        }
    }

    public function deleted(JobListing $job): void
    {
        IndexNowService::submit(url('/jobs/'.$job->slug));
    }
}
