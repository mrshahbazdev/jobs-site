<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('push:send-new-jobs --limit=20')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();
