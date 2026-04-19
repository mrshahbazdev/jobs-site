<?php

namespace App\Console\Commands;

use App\Models\JobListing;
use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SendJobPushNotifications extends Command
{
    protected $signature = 'push:send-new-jobs {--limit=10 : Max number of jobs to notify in one run} {--dry-run}';
    protected $description = 'Send a web-push notification for each active job that has not yet been pushed';

    public function handle(WebPushService $push): int
    {
        $jobs = JobListing::query()
            ->where('is_active', true)
            ->whereNull('push_notified_at')
            ->orderBy('created_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($jobs->isEmpty()) {
            $this->info('No new jobs to notify.');
            return self::SUCCESS;
        }

        $this->info("Processing {$jobs->count()} job(s)...");

        foreach ($jobs as $job) {
            $subs = PushSubscription::query()
                ->when($job->category_id, fn ($q) => $q->where(function ($inner) use ($job) {
                    $inner->whereNull('category_id')->orWhere('category_id', $job->category_id);
                }))
                ->when($job->city_id, fn ($q) => $q->where(function ($inner) use ($job) {
                    $inner->whereNull('city_id')->orWhere('city_id', $job->city_id);
                }))
                ->get();

            $payload = [
                'title' => Str::limit($job->title, 60),
                'body' => $job->meta_description
                    ?: ($job->department ? $job->department . ' — ' : '') . ($job->category?->name ?? 'New Job'),
                'url' => url('/jobs/' . $job->slug),
                'tag' => 'job-' . $job->id,
                'icon' => asset('icons/icon-192x192.png'),
                'badge' => asset('icons/icon-192x192.png'),
            ];

            if ($this->option('dry-run')) {
                $this->line("  DRY: would notify {$subs->count()} subscribers about: {$job->title}");
                continue;
            }

            if ($subs->isEmpty()) {
                $job->forceFill(['push_notified_at' => now()])->save();
                continue;
            }

            $result = $push->broadcast($subs, $payload);
            $this->line("  [{$job->id}] {$job->title} → sent={$result['success']} failed={$result['failed']} pruned={$result['pruned']}");

            $job->forceFill(['push_notified_at' => now()])->save();
        }

        return self::SUCCESS;
    }
}
