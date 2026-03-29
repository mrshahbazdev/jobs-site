<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\JobListing;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate dynamic XML sitemap.
     */
    public function index(): Response
    {
        $categories = Category::all();
        $cities = City::all();
        $jobs = JobListing::active()->orderBy('created_at', 'desc')->take(1000)->get();

        return response()->view('sitemap', [
            'categories' => $categories,
            'cities' => $cities,
            'jobs' => $jobs,
        ])->header('Content-Type', 'text/xml');
    }

    /**
     * Generate Google News XML sitemap.
     */
    public function news(): Response
    {
        // Google News sitemap can only contain articles from the last 2 days.
        $jobs = JobListing::active()
            ->where('created_at', '>=', now()->subDays(2))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->view('news_sitemap', [
            'jobs' => $jobs,
        ])->header('Content-Type', 'text/xml');
    }

    /**
     * Generate RSS feed for job aggregators.
     */
    public function feed(): Response
    {
        $jobs = JobListing::active()->orderBy('created_at', 'desc')->take(50)->get();

        return response()->view('feed', [
            'jobs' => $jobs,
        ])->header('Content-Type', 'application/rss+xml');
    }

    /**
     * Generate Google Image XML sitemap.
     */
    public function images(): Response
    {
        $jobs = JobListing::active()->whereNotNull('job_source_image_id')->orderBy('created_at', 'desc')->take(1000)->get();

        return response()->view('image_sitemap', [
            'jobs' => $jobs,
        ])->header('Content-Type', 'text/xml');
    }

    /**
     * Display a basic robots.txt.
     */
    public function robots(): Response
    {
        $content = "User-agent: *\nDisallow: /admin\nDisallow: /api\nDisallow: /search\n\nSitemap: " . url('/sitemap.xml') . "\nSitemap: " . url('/news-sitemap.xml') . "\nSitemap: " . url('/image-sitemap.xml') . "\nSitemap: " . url('/feed');
        return response($content)->header('Content-Type', 'text/plain');
    }
}
