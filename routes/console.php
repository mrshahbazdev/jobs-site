<?php

use App\Models\JobListing;
use App\Services\JobPosterService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('posters:generate {--force}', function () {
    $q = JobListing::query();
    if (! $this->option('force')) {
        $q->whereNull('poster_path');
    }
    $q->each(function ($job) {
        $job->updateQuietly(['poster_path' => app(JobPosterService::class)->generate($job)]);
        $this->info("✔ {$job->slug}");
    });
});

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('push:send-new-jobs --limit=20')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
