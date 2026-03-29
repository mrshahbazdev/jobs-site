<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendJobAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'jobs:send-alerts';
    protected $description = 'Send matching job alerts to subscribers';

    public function handle()
    {
        $subscribers = \App\Models\Subscriber::where('is_active', true)->get();
        $count = 0;

        foreach ($subscribers as $subscriber) {
            $query = \App\Models\JobListing::where('is_active', true)
                ->where('created_at', '>=', now()->subDay());

            if ($subscriber->category_id) {
                $query->where('category_id', $subscriber->category_id);
            }

            if ($subscriber->city_id) {
                $query->where('city_id', $subscriber->city_id);
            }

            $jobs = $query->get();

            if ($jobs->count() > 0) {
                \Illuminate\Support\Facades\Mail::to($subscriber->email_or_whatsapp)
                    ->send(new \App\Mail\JobAlert($jobs));
                $count++;
            }
        }

        $this->info("Successfully sent {$count} job alerts.");
    }
}
