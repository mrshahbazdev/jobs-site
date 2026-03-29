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
     * Display a basic robots.txt.
     */
    public function robots(): Response
    {
        $content = "User-agent: *\nDisallow: /admin\nDisallow: /api\nDisallow: /search\n\nSitemap: " . url('/sitemap.xml');
        return response($content)->header('Content-Type', 'text/plain');
    }
}
