<?php

use App\Http\Controllers\Admin\EngagementAnalyticsController;

use App\Http\Controllers\Frontend\FollowPreferenceController;

use App\Http\Controllers\Frontend\OnboardingController;

use App\Http\Controllers\Frontend\SearchController as AdvancedSearchController;

use App\Http\Controllers\Admin\CommunityCommentController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Frontend\AccountController;
use App\Http\Controllers\Frontend\CommunityController;
use App\Http\Controllers\Frontend\NotificationController as FrontendNotificationController;
use App\Http\Controllers\Frontend\UserComparisonController;
use App\Http\Controllers\Frontend\UserInteractionController;
use App\Http\Controllers\PublicReviewController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Models\Benchmark;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public user-action support
|--------------------------------------------------------------------------
*/

Route::get('/user/interactions/status', [UserInteractionController::class, 'status'])
    ->name('user.interactions.status');

Route::post('/user/interactions/intent', [UserInteractionController::class, 'intent'])
    ->middleware('throttle:30,1')
    ->name('user.interactions.intent');

Route::get('/user/comparisons/status', [UserComparisonController::class, 'status'])
    ->name('user.comparisons.status');

Route::post('/user/comparisons/intent', [UserComparisonController::class, 'intent'])
    ->middleware('throttle:30,1')
    ->name('user.comparisons.intent');

/*
|--------------------------------------------------------------------------
| Community Reviews & Discussions
|--------------------------------------------------------------------------
*/

Route::get('/community/context', [CommunityController::class, 'context'])
    ->name('community.context');

Route::get('/community/comments', [CommunityController::class, 'comments'])
    ->name('community.comments.index');

Route::get('/community/reviews', [CommunityController::class, 'reviews'])
    ->name('community.reviews.public');

Route::get('/community/login', [CommunityController::class, 'login'])
    ->name('community.login');

Route::get('/benchmarks/{benchmark:slug}/discussion', function (Benchmark $benchmark) {
    abort_unless($benchmark->is_active, 404);

    return view('frontend.benchmarks.discussion', compact('benchmark'));
})->name('benchmarks.discussion');

/*
|--------------------------------------------------------------------------
| Authenticated + active user actions
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', EnsureAccountIsActive::class])->group(function () {
    // Community comments
    Route::post('/community/comments', [CommunityController::class, 'store'])
        ->middleware('throttle:15,1')
        ->name('community.comments.store');

    Route::patch('/community/comments/{comment}', [CommunityController::class, 'update'])
        ->middleware('throttle:20,1')
        ->name('community.comments.update');

    Route::delete('/community/comments/{comment}', [CommunityController::class, 'destroy'])
        ->middleware('throttle:20,1')
        ->name('community.comments.destroy');

    Route::post('/community/helpful', [CommunityController::class, 'toggleHelpful'])
        ->middleware('throttle:60,1')
        ->name('community.helpful');

    Route::post('/community/report', [CommunityController::class, 'report'])
        ->middleware('throttle:10,1')
        ->name('community.report');

    // Reviews. Tool review routes are defined in routes/web.php; model reviews live here.
    Route::get('/models/{model}/review', [PublicReviewController::class, 'createModel'])
        ->name('reviews.models.create');

    Route::post('/models/{model}/review', [PublicReviewController::class, 'storeModel'])
        ->middleware('throttle:10,1')
        ->name('reviews.models.store');

    // My AI Hub
    Route::get('/account', [AccountController::class, 'dashboard'])
        ->name('account.dashboard');

    Route::get('/account/reviews', [AccountController::class, 'reviews'])
        ->name('account.reviews');

    Route::get('/account/comments', [AccountController::class, 'comments'])
        ->name('account.comments');

    Route::get('/account/following', [AccountController::class, 'following'])
        ->name('account.following');

    Route::get('/account/activity', [AccountController::class, 'activity'])
        ->name('account.activity');

    Route::get('/account/settings', [AccountController::class, 'settings'])
        ->name('account.settings');

    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])
        ->middleware('throttle:20,1')
        ->name('account.profile.update');

    Route::patch('/account/password', [AccountController::class, 'updatePassword'])
        ->middleware('throttle:10,1')
        ->name('account.password.update');

    // User notifications
    Route::get('/account/notifications', [FrontendNotificationController::class, 'index'])
        ->name('account.notifications');

    Route::get('/account/notifications/{notification}/open', [FrontendNotificationController::class, 'open'])
        ->name('account.notifications.open');

    Route::post('/account/notifications/read-all', [FrontendNotificationController::class, 'markAllRead'])
        ->name('account.notifications.read-all');

    Route::delete('/account/notifications/{notification}', [FrontendNotificationController::class, 'destroy'])
        ->name('account.notifications.destroy');

    // Follow / helpful / user interactions
    Route::post('/user/interactions/toggle', [UserInteractionController::class, 'toggle'])
        ->middleware('throttle:60,1')
        ->name('user.interactions.toggle');

    // Comparisons
    Route::post('/user/comparisons/toggle', [UserComparisonController::class, 'toggle'])
        ->middleware('throttle:30,1')
        ->name('user.comparisons.toggle');

    Route::get('/my/comparisons', [UserComparisonController::class, 'index'])
        ->name('user.comparisons.index');

    Route::get('/my/comparison-history', [UserComparisonController::class, 'history'])
        ->name('user.comparisons.history');

    // Test Lab history
    Route::get('/my/test-lab-history', [UserInteractionController::class, 'testHistory'])
        ->name('user.testlab.history');
});

/*
|--------------------------------------------------------------------------
| Admin community moderation
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', EnsureAccountIsActive::class, 'admin'])->group(function () {
    Route::patch('/admin/users/{id}/community-trust', [AdminUserController::class, 'updateCommunityTrust'])
        ->whereNumber('id')
        ->middleware(\App\Http\Middleware\RequirePermission::class . ':Users,Edit')
        ->name('admin.users.community-trust');

    Route::prefix('admin/community/comments')
        ->name('admin.community.comments.')
        ->group(function () {
            Route::get('/', [CommunityCommentController::class, 'index'])
                ->middleware(\App\Http\Middleware\RequirePermission::class . ':Users,View')
                ->name('index');

            Route::patch('/{comment}', [CommunityCommentController::class, 'update'])
                ->middleware(\App\Http\Middleware\RequirePermission::class . ':Users,Edit')
                ->name('update');

            Route::delete('/{comment}', [CommunityCommentController::class, 'destroy'])
                ->middleware(\App\Http\Middleware\RequirePermission::class . ':Users,Delete')
                ->name('destroy');
        });
});


/* Six-feature intelligence upgrade */
Route::middleware(['auth', EnsureAccountIsActive::class])->group(function () {
    Route::post('/search/save', [AdvancedSearchController::class, 'save'])->name('search.save');
    Route::delete('/search/saved/{savedSearch}', [AdvancedSearchController::class, 'destroySaved'])->name('search.saved.destroy');
    Route::post('/search/click', [AdvancedSearchController::class, 'click'])->name('search.click');

    Route::get('/onboarding', [OnboardingController::class, 'show'])->name('account.onboarding');
    Route::post('/onboarding', [OnboardingController::class, 'store'])->name('account.onboarding.store');

    Route::patch('/account/following/{interaction}/alerts', [FollowPreferenceController::class, 'update'])
        ->name('account.following.alerts');
});

Route::get('/admin/analytics/engagement', [EngagementAnalyticsController::class, 'index'])
    ->middleware(['auth', \App\Http\Middleware\RequirePermission::class . ':Analytics,View'])
    ->name('admin.analytics.engagement');
