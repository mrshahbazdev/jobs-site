<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\JobController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;

// Homepage & Main Job Routes
Route::get('/', [JobController::class, 'index']);
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/news-sitemap.xml', [SitemapController::class, 'news']);
Route::get('/image-sitemap.xml', [SitemapController::class, 'images']);
Route::get('/feed', [SitemapController::class, 'feed']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);
Route::get('/jobs/{slug}', [JobController::class, 'show'])->name('jobs.show');
Route::get('/jobs/{slug}/amp', [JobController::class, 'ampShow'])->name('jobs.amp');
Route::get('/web-stories/{slug}', [JobController::class, 'storyShow'])->name('jobs.story');
Route::get('/categories', [JobController::class, 'categories'])->name('categories.index');
Route::get('/categories/{slug}', [JobController::class, 'category'])->name('categories.show');
Route::get('/cities/{slug}', [JobController::class, 'city'])->name('cities.show');
Route::get('/search', [JobController::class, 'search'])->name('jobs.search');

// Authentication Helper (Temporary)
Route::redirect('/login', '/admin/login')->name('login');

// Newsletter / Subscribe
Route::post('/subscribe', [SubscriberController::class, 'subscribe'])->name('subscribe');

// Web Push Notifications
Route::get('/push/public-key', [\App\Http\Controllers\PushController::class, 'publicKey'])->name('push.public_key');
Route::post('/push/subscribe', [\App\Http\Controllers\PushController::class, 'subscribe'])
    ->middleware('throttle:20,1')
    ->name('push.subscribe');
Route::post('/push/unsubscribe', [\App\Http\Controllers\PushController::class, 'unsubscribe'])
    ->middleware('throttle:20,1')
    ->name('push.unsubscribe');

// Bookmark Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/bookmarks', [BookmarkController::class, 'index'])->name('bookmarks.index');
    Route::post('/bookmarks/{job}/toggle', [BookmarkController::class, 'toggle'])->name('bookmarks.toggle');
});

// Blog Routes
Route::get('/blog', [\App\Http\Controllers\PostController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [\App\Http\Controllers\PostController::class, 'show'])->name('blog.show');

// Special Lists
Route::get('/education/{education}', [JobController::class, 'education'])->name('jobs.education');
Route::get('/newspaper/{newspaper}', [JobController::class, 'newspaper'])->name('jobs.newspaper');
Route::get('/department/{department}', [JobController::class, 'department'])->name('jobs.department');
Route::get('/province/{province}', [JobController::class, 'province'])->name('jobs.province');
Route::get('/gender/{gender}', [JobController::class, 'gender'])->name('jobs.gender');
Route::get('/bps/{scale}', [JobController::class, 'bps'])->name('jobs.bps');
Route::get('/government-jobs', [JobController::class, 'sector'])->defaults('sector', 'government')->name('jobs.government');
Route::get('/today-jobs', [JobController::class, 'today'])->name('jobs.today');
Route::get('/expiring-soon', [JobController::class, 'expiring'])->name('jobs.expiring');
Route::get('/degree/{degree}', [JobController::class, 'degree'])->name('jobs.degree');
Route::get('/type/{type}', [JobController::class, 'type'])->name('jobs.type');
Route::get('/salary-range/{bucket}', [JobController::class, 'salaryRange'])->name('jobs.salary_range');
Route::get('/quota/{quota_type}', [JobController::class, 'quota'])->name('jobs.quota');
Route::get('/testing-service/{service}', [JobController::class, 'testingService'])->name('jobs.testing_service');
Route::get('/country/{country}', [JobController::class, 'country'])->name('jobs.country');
Route::get('/sector/{sector}', [JobController::class, 'sector'])->name('jobs.sector');
Route::get('/role/{role}', [JobController::class, 'role'])->name('jobs.role');
Route::get('/council/{council}', [JobController::class, 'council'])->name('jobs.council');
Route::get('/archive/{year}/{month}', [JobController::class, 'archive'])->name('jobs.archive');
Route::get('/all-lists', [JobController::class, 'allLists'])->name('jobs.all_lists');
Route::get('/walk-in-interviews', [JobController::class, 'walkin'])->name('jobs.walkin');
Route::get('/whatsapp-apply-jobs', [JobController::class, 'whatsappJobs'])->name('jobs.whatsapp');
Route::get('/remote-jobs', [JobController::class, 'remote'])->name('jobs.remote');
Route::get('/fresh-graduates', [JobController::class, 'freshGraduates'])->name('jobs.fresh_graduates');
Route::get('/retired-army-jobs', [JobController::class, 'retiredArmy'])->name('jobs.retired_army');
Route::get('/student-jobs', [JobController::class, 'studentJobs'])->name('jobs.student_jobs');
Route::get('/industrial/{slug}', [JobController::class, 'industrial'])->name('jobs.industrial');
Route::get('/contract/{type}', [JobController::class, 'contract'])->name('jobs.contract');
Route::get('/jobs-with-accommodation', [JobController::class, 'accommodation'])->name('jobs.accommodation');
Route::get('/jobs-with-transport', [JobController::class, 'transport'])->name('jobs.transport');
Route::get('/skill/{skill}', [JobController::class, 'skill'])->name('jobs.skill');

// Static Pages
Route::get('/about', [PageController::class, 'about'])->name('pages.about');
Route::get('/privacy-policy', [PageController::class, 'privacy'])->name('pages.privacy');
Route::get('/terms', [PageController::class, 'terms'])->name('pages.terms');
