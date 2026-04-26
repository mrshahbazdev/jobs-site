<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JobArticleController;
use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\CityApiController;
use App\Http\Controllers\Api\ScraperApiController;
use App\Http\Controllers\Api\LandingGroupApiController;
use App\Http\Controllers\Api\JobApiController;
use App\Http\Controllers\Api\ScraperQueueController;

// ── CORS OPTIONS preflight (allows test.html file:// access) ──────────────────
Route::options('/{any}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With');
})->where('any', '.*');

// ── Core API Routes (legacy — kept for backward compatibility) ───────────────
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/pending-images', [JobArticleController::class, 'pendingImages']);
Route::get('/internal-links', [JobArticleController::class, 'internalLinks']);
Route::post('/submit-article', [JobArticleController::class, 'submitArticle']);
Route::post('/post-job', [JobArticleController::class, 'postJob']); // ← test.html se direct post
Route::get('/jobs', [JobArticleController::class, 'jobsList']);       // legacy (basic list)
Route::post('/skip-image', [JobArticleController::class, 'skipImage']);

// ── Enhanced Job API ─────────────────────────────────────────────────────────
// Advanced listing with pagination, filters, search, sorting
Route::get('/v2/jobs',                   [JobApiController::class, 'index']);
Route::get('/v2/jobs/stats',             [JobApiController::class, 'stats']);
Route::get('/v2/jobs/{idOrSlug}',        [JobApiController::class, 'show']);
Route::post('/v2/jobs',                  [JobApiController::class, 'store']);
Route::put('/v2/jobs/{id}',              [JobApiController::class, 'update']);
Route::delete('/v2/jobs/{id}',           [JobApiController::class, 'destroy']);
Route::post('/v2/jobs/{id}/toggle',      [JobApiController::class, 'toggleStatus']);
Route::post('/v2/jobs/{id}/duplicate',   [JobApiController::class, 'duplicate']);
Route::post('/v2/jobs/bulk',             [JobApiController::class, 'bulkStore']);
Route::post('/v2/jobs/bulk-status',      [JobApiController::class, 'bulkUpdateStatus']);
Route::delete('/v2/jobs/bulk',           [JobApiController::class, 'bulkDelete']);

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

// Scraper Queue APIs
Route::get('/v2/scraper-queue/stats', [ScraperQueueController::class, 'stats']);
Route::get('/v2/scraper-queue/pending', [ScraperQueueController::class, 'pending']);
Route::get('/v2/scraper-queue/next', [ScraperQueueController::class, 'next']);
Route::put('/v2/scraper-queue/{id}/status', [ScraperQueueController::class, 'updateStatus']);
Route::post('/v2/scraper-queue/{id}/skip', [ScraperQueueController::class, 'skip']);
Route::post('/v2/scraper-queue/{id}/reset', [ScraperQueueController::class, 'resetStatus']);
