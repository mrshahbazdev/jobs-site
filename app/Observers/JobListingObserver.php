<?php

namespace App\Observers;

use App\Models\JobListing;
use App\Services\IndexNowService;

class JobListingObserver
{
    public function created(JobListing $job): void
    {
        if ($job->is_active) {
            IndexNowService::submit(url('/jobs/' . $job->slug));
        }
    }

    public function updated(JobListing $job): void
    {
        if ($job->is_active) {
            IndexNowService::submit(url('/jobs/' . $job->slug));
        }
    }

    public function deleted(JobListing $job): void
    {
        IndexNowService::submit(url('/jobs/' . $job->slug));
    }
}
