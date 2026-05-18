<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\City;
use App\Models\JobListing;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    private const JOBS_PER_PAGE = 5000;
    private const CACHE_TTL = 3600; // 60 minutes

    /**
     * Sitemap index – lightweight pointer to sub-sitemaps.
     */
    public function index(): Response
    {
        $xml = Cache::remember('sitemap:index', self::CACHE_TTL, function () {
            $totalJobs = JobListing::active()->count();
            $pages = max(1, (int) ceil($totalJobs / self::JOBS_PER_PAGE));
            $now = now()->toAtomString();

            return view('sitemaps.index', compact('pages', 'now'))->render();
        });

        return response($xml)->header('Content-Type', 'text/xml');
    }

    /**
     * Categories sub-sitemap.
     */
    public function categoriesSitemap(): Response
    {
        $xml = Cache::remember('sitemap:categories', self::CACHE_TTL, function () {
            $categories = Category::select('slug', 'updated_at')->get();

            return view('sitemaps.categories', compact('categories'))->render();
        });

        return response($xml)->header('Content-Type', 'text/xml');
    }

    /**
     * Cities sub-sitemap.
     */
    public function citiesSitemap(): Response
    {
        $xml = Cache::remember('sitemap:cities', self::CACHE_TTL, function () {
            $cities = City::select('slug', 'updated_at')->get();

            return view('sitemaps.cities', compact('cities'))->render();
        });

        return response($xml)->header('Content-Type', 'text/xml');
    }

    /**
     * Paginated jobs sub-sitemap.
     */
    public function jobsSitemap(int $page): Response
    {
        $xml = Cache::remember("sitemap:jobs:{$page}", self::CACHE_TTL, function () use ($page) {
            $jobs = JobListing::active()
                ->select('slug', 'updated_at')
                ->orderBy('id')
                ->skip(($page - 1) * self::JOBS_PER_PAGE)
                ->take(self::JOBS_PER_PAGE)
                ->get();

            return view('sitemaps.jobs', compact('jobs'))->render();
        });

        return response($xml)->header('Content-Type', 'text/xml');
    }

    /**
     * Static pages sub-sitemap.
     */
    public function staticSitemap(): Response
    {
        $xml = Cache::remember('sitemap:static', self::CACHE_TTL, function () {
            return view('sitemaps.static')->render();
        });

        return response($xml)->header('Content-Type', 'text/xml');
    }

    /**
     * Generate Google News XML sitemap (last 2 days only).
     */
    public function news(): Response
    {
        $xml = Cache::remember('sitemap:news', self::CACHE_TTL, function () {
            $jobs = JobListing::active()
                ->select('title', 'slug', 'created_at')
                ->where('created_at', '>=', now()->subDays(2))
                ->orderBy('created_at', 'desc')
                ->get();

            return view('news_sitemap', compact('jobs'))->render();
        });

        return response($xml)->header('Content-Type', 'text/xml');
    }

    /**
     * Generate RSS feed for job aggregators.
     */
    public function feed(): Response
    {
        $xml = Cache::remember('sitemap:feed', self::CACHE_TTL, function () {
            $jobs = JobListing::active()
                ->orderBy('created_at', 'desc')
                ->take(50)
                ->get();

            return view('feed', compact('jobs'))->render();
        });

        return response($xml)->header('Content-Type', 'application/rss+xml');
    }

    /**
     * Generate Google Image XML sitemap.
     */
    public function images(): Response
    {
        $xml = Cache::remember('sitemap:images', self::CACHE_TTL, function () {
            $jobs = JobListing::active()
                ->whereNotNull('job_source_image_id')
                ->whereHas('sourceImage', fn ($q) => $q->withImage())
                ->with('sourceImage', 'city')
                ->select('job_listings.id', 'job_listings.slug', 'job_listings.title', 'job_listings.job_source_image_id', 'job_listings.city_id', 'job_listings.updated_at')
                ->orderBy('created_at', 'desc')
                ->take(1000)
                ->get();

            return view('image_sitemap', compact('jobs'))->render();
        });

        return response($xml)->header('Content-Type', 'text/xml');
    }

    /**
     * Generate AMP pages XML sitemap.
     */
    public function amp(): Response
    {
        $xml = Cache::remember('sitemap:amp', self::CACHE_TTL, function () {
            $jobs = JobListing::active()
                ->select('slug', 'updated_at')
                ->orderBy('created_at', 'desc')
                ->take(1000)
                ->get();

            return view('amp_sitemap', compact('jobs'))->render();
        });

        return response($xml)->header('Content-Type', 'text/xml');
    }

    /**
     * Generate Web Stories XML sitemap.
     */
    public function stories(): Response
    {
        $xml = Cache::remember('sitemap:stories', self::CACHE_TTL, function () {
            $jobs = JobListing::active()
                ->select('slug', 'updated_at')
                ->orderBy('created_at', 'desc')
                ->take(1000)
                ->get();

            return view('story_sitemap', compact('jobs'))->render();
        });

        return response($xml)->header('Content-Type', 'text/xml');
    }

    /**
     * Display a basic robots.txt.
     */
    public function robots(): Response
    {
        $content = "User-agent: *\nDisallow: /admin\nDisallow: /api\nDisallow: /search\n\nSitemap: " . url('/sitemap.xml') . "\nSitemap: " . url('/news-sitemap.xml') . "\nSitemap: " . url('/image-sitemap.xml') . "\nSitemap: " . url('/amp-sitemap.xml') . "\nSitemap: " . url('/stories-sitemap.xml') . "\nSitemap: " . url('/feed');

        return response($content)->header('Content-Type', 'text/plain');
    }
}
