<?php

namespace App\Providers;

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
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            // Seed defaults if empty
            if (\App\Models\Setting::count() === 0) {
                \App\Models\Setting::insert([
                    ['key' => 'header_tags', 'value' => '<!-- Add custom meta/scripts here -->', 'created_at' => now(), 'updated_at' => now()],
                    ['key' => 'ad_home_top', 'value' => '<!-- Ad: Home Top -->', 'created_at' => now(), 'updated_at' => now()],
                    ['key' => 'ad_job_sidebar', 'value' => '<!-- Ad: Job Sidebar -->', 'created_at' => now(), 'updated_at' => now()],
                    ['key' => 'ad_job_bottom', 'value' => '<!-- Ad: Job Bottom -->', 'created_at' => now(), 'updated_at' => now()],
                    ['key' => 'ad_footer', 'value' => '<!-- Ad: Footer Top -->', 'created_at' => now(), 'updated_at' => now()],
                ]);
            }

            $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
            \Illuminate\Support\Facades\View::share('settings', $settings);
        }
    }
}
