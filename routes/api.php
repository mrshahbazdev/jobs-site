<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JobArticleController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\CityApiController;
use App\Http\Controllers\Api\ScraperApiController;
use App\Http\Controllers\Api\LandingGroupApiController;

// ── CORS OPTIONS preflight (allows test.html file:// access) ──────────────────
Route::options('/{any}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With');
})->where('any', '.*');

// ── Core API Routes ──────────────────────────────────────────────────────────
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/pending-images', [JobArticleController::class, 'pendingImages']);
Route::get('/internal-links', [JobArticleController::class, 'internalLinks']);
Route::post('/submit-article', [JobArticleController::class, 'submitArticle']);
Route::post('/post-job', [JobArticleController::class, 'postJob']); // ← test.html se direct post
Route::get('/jobs', [JobArticleController::class, 'jobsList']);
Route::post('/skip-image', [JobArticleController::class, 'skipImage']);

// Category APIs
Route::get('/categories', [CategoryApiController::class, 'index']);
Route::post('/categories', [CategoryApiController::class, 'store']);
Route::post('/categories/resolve', [CategoryApiController::class, 'resolve']);

// Landing Group APIs
Route::get('/landing-groups', [LandingGroupApiController::class, 'index']);
Route::post('/landing-groups', [LandingGroupApiController::class, 'store']);

// City APIs
Route::get('/cities', [CityApiController::class, 'index']);
Route::post('/cities', [CityApiController::class, 'store']);

// Scraper APIs
Route::get('/scraper-status', [ScraperApiController::class, 'status']);
Route::post('/trigger-scrape', [ScraperApiController::class, 'trigger']);
Route::post('/scrape-image', [ScraperApiController::class, 'scrapeImage']);
