<?php

namespace App\Providers;

use App\Models\JobListing;
use App\Models\Post;
use App\Models\Setting;
use App\Observers\JobListingObserver;
use App\Observers\PostObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JobListing::observe(JobListingObserver::class);
        Post::observe(PostObserver::class);

        Schema::defaultStringLength(191);

        // Prevent crashes during migrations or before DB is setup
        try {
            if (! app()->runningInConsole() && Schema::hasTable('settings')) {
                // Seed defaults if empty
                if (Setting::count() === 0) {
                    Setting::insert([
                        ['key' => 'header_tags', 'value' => '<!-- Add custom meta/scripts here -->', 'created_at' => now(), 'updated_at' => now()],
                        ['key' => 'ad_home_top', 'value' => '<!-- Ad: Home Top -->', 'created_at' => now(), 'updated_at' => now()],
                        ['key' => 'ad_job_sidebar', 'value' => '<!-- Ad: Job Sidebar -->', 'created_at' => now(), 'updated_at' => now()],
                        ['key' => 'ad_job_bottom', 'value' => '<!-- Ad: Job Bottom -->', 'created_at' => now(), 'updated_at' => now()],
                        ['key' => 'ad_footer', 'value' => '<!-- Ad: Footer Top -->', 'created_at' => now(), 'updated_at' => now()],
                    ]);
                }

                $settings = Setting::pluck('value', 'key')->toArray();
                View::share('settings', $settings);
            }
        } catch (\Exception $e) {
            // Silently fail if database is not reachable yet
        }
    }
}
