<?php

use App\Models\JobListing;
use App\Models\Post;
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

Artisan::command('posters:generate-posts {--force}', function () {
    $q = Post::query();
    if (! $this->option('force')) {
        $q->whereNull('poster_path');
    }
    $q->each(function ($post) {
        $post->updateQuietly(['poster_path' => app(JobPosterService::class)->generateForPost($post)]);
        $this->info("✔ {$post->slug}");
    });
});

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('push:send-new-jobs --limit=20')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
