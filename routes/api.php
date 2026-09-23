<?php

use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\CityApiController;
use App\Http\Controllers\Api\JobApiController;
use App\Http\Controllers\Api\JobArticleController;
use App\Http\Controllers\Api\LandingGroupApiController;
use App\Http\Controllers\Api\Mcp\McpAdminController;
use App\Http\Controllers\Api\Mcp\McpContentController;
use App\Http\Controllers\Api\Mcp\McpOpsController;
use App\Http\Controllers\Api\Mcp\McpRpcController;
use App\Http\Controllers\Api\Mcp\McpSystemController;
use App\Http\Controllers\Api\ScraperApiController;
use App\Http\Controllers\Api\ScraperQueueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ── CORS OPTIONS preflight (allows test.html file:// access) ──────────────────
Route::options('/{any}', function () {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With, X-MCP-Token');
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
Route::get('/v2/jobs', [JobApiController::class, 'index']);
Route::get('/v2/jobs/stats', [JobApiController::class, 'stats']);
Route::get('/v2/jobs/{idOrSlug}', [JobApiController::class, 'show']);
Route::post('/v2/jobs', [JobApiController::class, 'store']);
Route::put('/v2/jobs/{id}', [JobApiController::class, 'update']);
Route::delete('/v2/jobs/{id}', [JobApiController::class, 'destroy']);
Route::post('/v2/jobs/{id}/toggle', [JobApiController::class, 'toggleStatus']);
Route::post('/v2/jobs/{id}/duplicate', [JobApiController::class, 'duplicate']);
Route::post('/v2/jobs/bulk', [JobApiController::class, 'bulkStore']);
Route::post('/v2/jobs/bulk-status', [JobApiController::class, 'bulkUpdateStatus']);
Route::delete('/v2/jobs/bulk', [JobApiController::class, 'bulkDelete']);

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
Route::get('/scraper-status-all', [ScraperApiController::class, 'statusAll']);
Route::post('/trigger-scrape', [ScraperApiController::class, 'trigger']);
Route::post('/scrape-image', [ScraperApiController::class, 'scrapeImage']);

// Scraper Queue APIs
Route::get('/v2/scraper-queue/stats', [ScraperQueueController::class, 'stats']);
Route::get('/v2/scraper-queue/pending', [ScraperQueueController::class, 'pending']);
Route::get('/v2/scraper-queue/next', [ScraperQueueController::class, 'next']);
Route::put('/v2/scraper-queue/{id}/status', [ScraperQueueController::class, 'updateStatus']);
Route::post('/v2/scraper-queue/{id}/skip', [ScraperQueueController::class, 'skip']);
Route::post('/v2/scraper-queue/{id}/reset', [ScraperQueueController::class, 'resetStatus']);
Route::get('/v2/scraper-queue/{id}/image', [ScraperQueueController::class, 'imageProxy']);

// ── MCP control surface (token protected, see config/mcp.php + mcp/README.md) ─
Route::prefix('mcp')->middleware('mcp.token')->group(function () {
    // Streamable-HTTP MCP endpoint (JSON-RPC 2.0) for browser/remote MCP clients
    Route::post('/rpc', [McpRpcController::class, 'handle']);

    // System / diagnostics
    Route::get('/health', [McpSystemController::class, 'health']);
    Route::get('/schema', [McpSystemController::class, 'schema']);
    Route::get('/routes', [McpSystemController::class, 'routes']);
    Route::get('/logs', [McpSystemController::class, 'logs']);
    Route::get('/sql', [McpSystemController::class, 'sql']);
    Route::post('/sql', [McpSystemController::class, 'sql']);
    Route::get('/artisan', [McpSystemController::class, 'artisanCommands']);
    Route::post('/artisan', [McpSystemController::class, 'artisan']);
    Route::get('/queue', [McpSystemController::class, 'queue']);
    Route::post('/cache', [McpSystemController::class, 'cache']);

    // Jobs ops / SEO / AI / push
    Route::get('/analytics', [McpOpsController::class, 'analytics']);
    Route::get('/seo/audit', [McpOpsController::class, 'seoAudit']);
    Route::post('/jobs/deactivate-expired', [McpOpsController::class, 'deactivateExpired']);
    Route::delete('/jobs/bulk-delete-by-filter', [McpOpsController::class, 'bulkDeleteByFilter']);
    Route::post('/jobs/regenerate-schema', [McpOpsController::class, 'regenerateSchema']);
    Route::post('/ai/extract', [McpOpsController::class, 'aiExtract']);
    Route::post('/indexnow', [McpOpsController::class, 'indexNow']);
    Route::get('/push/stats', [McpOpsController::class, 'pushStats']);
    Route::post('/push/broadcast', [McpOpsController::class, 'pushBroadcast']);

    // Taxonomy
    Route::put('/categories/{id}', [McpOpsController::class, 'updateCategory']);
    Route::delete('/categories/{id}', [McpOpsController::class, 'destroyCategory']);
    Route::post('/categories/merge', [McpOpsController::class, 'mergeCategories']);
    Route::put('/cities/{id}', [McpOpsController::class, 'updateCity']);
    Route::delete('/cities/{id}', [McpOpsController::class, 'destroyCity']);

    // Scraper queue extras
    Route::get('/scraper-queue', [McpOpsController::class, 'scraperQueueSearch']);
    Route::post('/scraper-queue/bulk-status', [McpOpsController::class, 'scraperQueueBulk']);
    Route::post('/scraper-queue/purge', [McpOpsController::class, 'scraperQueuePurge']);

    // Content
    Route::get('/posts', [McpContentController::class, 'posts']);
    Route::post('/posts', [McpContentController::class, 'storePost']);
    Route::get('/posts/{idOrSlug}', [McpContentController::class, 'showPost']);
    Route::put('/posts/{id}', [McpContentController::class, 'updatePost']);
    Route::delete('/posts/{id}', [McpContentController::class, 'destroyPost']);

    Route::get('/settings', [McpContentController::class, 'settings']);
    Route::put('/settings', [McpContentController::class, 'upsertSettings']);
    Route::delete('/settings/{key}', [McpContentController::class, 'destroySetting']);

    Route::get('/comments', [McpContentController::class, 'comments']);
    Route::post('/comments/moderate', [McpContentController::class, 'moderateComments']);

    Route::get('/subscribers', [McpContentController::class, 'subscribers']);
    Route::put('/subscribers/{id}', [McpContentController::class, 'updateSubscriber']);
    Route::delete('/subscribers/{id}', [McpContentController::class, 'destroySubscriber']);

    Route::get('/landing', [McpContentController::class, 'landing']);
    Route::put('/landing-groups/{id}', [McpContentController::class, 'updateLandingGroup']);
    Route::delete('/landing-groups/{id}', [McpContentController::class, 'destroyLandingGroup']);
    Route::post('/landing-links', [McpContentController::class, 'storeLandingLink']);
    Route::put('/landing-links/{id}', [McpContentController::class, 'updateLandingLink']);
    Route::delete('/landing-links/{id}', [McpContentController::class, 'destroyLandingLink']);

    Route::get('/home-blocks', [McpContentController::class, 'homeBlocks']);
    Route::post('/home-blocks', [McpContentController::class, 'storeHomeBlock']);
    Route::post('/home-blocks/reorder', [McpContentController::class, 'reorderHomeBlocks']);
    Route::put('/home-blocks/{id}', [McpContentController::class, 'updateHomeBlock']);
    Route::delete('/home-blocks/{id}', [McpContentController::class, 'destroyHomeBlock']);

    Route::get('/users', [McpContentController::class, 'users']);
    Route::post('/users', [McpAdminController::class, 'createUser']);
    Route::get('/users/{id}', [McpAdminController::class, 'showUser']);
    Route::put('/users/{id}', [McpAdminController::class, 'updateUser']);
    Route::delete('/users/{id}', [McpAdminController::class, 'destroyUser']);
    Route::post('/users/{id}/reset-password', [McpAdminController::class, 'resetPassword']);

    Route::get('/cvs', [McpAdminController::class, 'cvs']);
    Route::get('/cvs/{id}', [McpAdminController::class, 'showCv']);
    Route::put('/cvs/{id}', [McpAdminController::class, 'updateCv']);
    Route::delete('/cvs/{id}', [McpAdminController::class, 'destroyCv']);

    Route::get('/bookmarks', [McpAdminController::class, 'bookmarks']);
    Route::post('/bookmarks/toggle', [McpAdminController::class, 'toggleBookmark']);

    Route::post('/source-images', [McpAdminController::class, 'storeSourceImage']);
    Route::get('/source-images/{id}', [McpAdminController::class, 'showSourceImage']);
    Route::get('/source-images/{id}/view', [McpOpsController::class, 'viewSourceImage']);
    Route::put('/source-images/{id}', [McpAdminController::class, 'updateSourceImage']);
    Route::delete('/source-images/{id}', [McpAdminController::class, 'destroySourceImage']);
    Route::post('/source-images/{id}/publish', [McpAdminController::class, 'publishSourceImage']);

    Route::get('/sitemaps', [McpAdminController::class, 'sitemaps']);
    Route::post('/sitemaps/flush', [McpAdminController::class, 'flushSitemaps']);

    Route::get('/alerts/preview', [McpAdminController::class, 'alertsPreview']);
    Route::post('/alerts/send', [McpAdminController::class, 'alertsSend']);
    Route::post('/mail/test', [McpAdminController::class, 'mailTest']);

    Route::get('/storage', [McpAdminController::class, 'storage']);
    Route::post('/storage/delete', [McpAdminController::class, 'storageDelete']);
    Route::get('/storage/orphans', [McpAdminController::class, 'orphanImages']);
    Route::post('/storage/orphans', [McpAdminController::class, 'orphanImages']);
});
