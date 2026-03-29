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
     * Display a basic robots.txt.
     */
    public function robots(): Response
    {
        $content = "User-agent: *\nDisallow: /admin\nDisallow: /api\nDisallow: /search\n\nSitemap: " . url('/sitemap.xml') . "\nSitemap: " . url('/news-sitemap.xml');
        return response($content)->header('Content-Type', 'text/plain');
    }
}
