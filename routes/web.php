<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\SignupController;
use App\Http\Controllers\Auth\LoginController;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\ToolController;
use App\Http\Controllers\Admin\AiModelController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\TaxonomyController;
use App\Http\Controllers\Admin\ComparisonController;
use App\Http\Controllers\Admin\TestlabController;
use App\Http\Controllers\Admin\BenchmarkController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SubmissionController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\AnalyticsController;

use App\Http\Controllers\Admin\Content\ArticleController;
use App\Http\Controllers\Admin\Content\ReviewController;
use App\Http\Controllers\Admin\Content\SocialController;
use App\Http\Controllers\Admin\Content\ApprovalWorkflowController;

use App\Http\Controllers\Admin\System\NotificationController;
use App\Http\Controllers\Admin\System\ActivityLogController;
use App\Http\Controllers\Admin\System\RoleController;
use App\Http\Controllers\Admin\System\SecurityController;
use App\Http\Controllers\Admin\System\TwoFactorController;
use App\Http\Controllers\Admin\System\BackupController;
use App\Http\Controllers\Admin\System\ApiMonitoringController;
use App\Http\Controllers\Admin\System\NewsSourceController;
use App\Http\Controllers\Admin\System\AutomationMonitorController;
use App\Http\Controllers\Admin\System\DataVerificationController;
use App\Http\Controllers\Admin\System\SourceReliabilityController;
use App\Http\Controllers\Admin\System\HealthController;
use App\Http\Controllers\Admin\System\ErrorController;
use App\Http\Controllers\Admin\System\FeatureFlagController;
use App\Http\Controllers\Admin\System\SeoController;
use App\Http\Controllers\Admin\System\IntegrationController;
use App\Http\Controllers\Admin\System\SettingController;
use App\Http\Controllers\Admin\System\NotificationRuleController;
use App\Http\Controllers\PublicReviewController;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::view('/', 'welcome')->name('home');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
| Guest-only screens are gated so a logged-in admin can't reopen them,
| and logout requires a session to end.
*/

Route::middleware('guest')->group(function () {
    Route::get('/auth/signup', [SignupController::class, 'index'])->name('signup');
    Route::post('/auth/signup', [SignupController::class, 'store'])->name('signup.store');

    Route::get('/auth/login', [LoginController::class, 'index'])->name('login');
    Route::post('/auth/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin panel
|--------------------------------------------------------------------------
| Everything below lives under /admin, requires auth + an admin gate, and
| every route name is prefixed admin.* — e.g. admin.tools.edit,
| admin.system.health. Swap the 'admin' middleware for whatever
| role/permission check your app uses (spatie/laravel-permission, a gate,
| etc).
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        /*
        |----------------------------------------------------------------
        | AI Intelligence — News
        |----------------------------------------------------------------
        */
        Route::controller(NewsController::class)
            ->prefix('news')
            ->name('news.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/breaking', 'breaking')->name('breaking');
                Route::get('/trending', 'trending')->name('trending');
                Route::get('/updates', 'updates')->name('updates');
                Route::get('/saved', 'saved')->name('saved');
                Route::get('/create', 'create')->name('create');
                Route::get('/duplicates', 'duplicates')->name('duplicates');
                Route::get('/{id}/edit', 'edit')->whereNumber('id')->name('edit');
                Route::post('/', 'store')->name('store');
                Route::put('/{id}', 'update')->whereNumber('id')->name('update');
                Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
                Route::get('/{id}', 'show')->whereNumber('id')->name('show');
            });

        /*
        |----------------------------------------------------------------
        | AI Management — Tools / Models / Companies / Taxonomy
        |----------------------------------------------------------------
        | Full CRUD, so these are real Route::resource declarations
        | instead of one-off Route::view calls. `only()` drops the
        | endpoints these modules don't use.
        */
        Route::resource('tools', ToolController::class)
            ->parameters(['tools' => 'id'])
            ->except(['destroy'])
            ->missing(fn () => abort(404));
        Route::delete('tools/{id}', [ToolController::class, 'destroy'])->name('tools.destroy');

        Route::resource('models', AiModelController::class)
            ->parameters(['models' => 'id'])
            ->except(['destroy']);
        Route::delete('models/{id}', [AiModelController::class, 'destroy'])->name('models.destroy');

        Route::resource('companies', CompanyController::class)
            ->parameters(['companies' => 'id'])
            ->except(['destroy']);
        Route::delete('companies/{id}', [CompanyController::class, 'destroy'])->name('companies.destroy');

        Route::controller(TaxonomyController::class)
            ->prefix('taxonomy')
            ->name('taxonomy.')
            ->group(function () {
                Route::get('/categories', 'categories')->name('categories');
                Route::get('/subcategories', 'subcategories')->name('subcategories');
                Route::get('/features', 'features')->name('features');
                Route::get('/tags', 'tags')->name('tags');
                Route::post('/{type}', 'store')->name('store');
                Route::put('/{type}/{id}', 'update')->name('update');
                Route::delete('/{type}/{id}', 'destroy')->name('destroy');
            });

        /*
        |----------------------------------------------------------------
        | Comparison & Benchmarks
        |----------------------------------------------------------------
        */
        Route::controller(ComparisonController::class)
            ->prefix('comparisons')
            ->name('comparisons.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/builder', 'builder')->name('builder');
                Route::get('/metrics', 'metrics')->name('metrics');
            });

        Route::controller(TestlabController::class)
            ->prefix('testlab')
            ->name('testlab.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/results', 'results')->name('results');
            });

        Route::controller(BenchmarkController::class)
            ->prefix('benchmarks')
            ->name('benchmarks.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
            });

        /*
        |----------------------------------------------------------------
        | Pricing
        |----------------------------------------------------------------
        */
        Route::controller(PricingController::class)
            ->prefix('pricing')
            ->name('pricing.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/api', 'api')->name('api');
                Route::get('/history', 'history')->name('history');
                Route::get('/changes', 'history')->name('changes'); // alias of history
            });

        /*
        |----------------------------------------------------------------
        | Content — Articles / Reviews / Social / Approval workflow
        |----------------------------------------------------------------
        */
        Route::name('content.')->prefix('content')->group(function () {
            Route::controller(ArticleController::class)
                ->prefix('articles')
                ->name('articles.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/drafts', 'drafts')->name('drafts');
                    Route::get('/editor', 'editor')->name('editor.create');
                    Route::get('/editor/{id}', 'editor')->whereNumber('id')->name('editor.edit');
                });
            Route::get('/guides', [ArticleController::class, 'guides'])->name('guides');

            Route::controller(ReviewController::class)
                ->prefix('reviews')
                ->name('reviews.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/editor', 'editor')->name('editor');
                    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
                });

            Route::get('/social', [SocialController::class, 'index'])->name('social');

            Route::get('/approval-workflow', [ApprovalWorkflowController::class, 'index'])
                ->name('approval-workflow');
        });

        /*
        |----------------------------------------------------------------
        | Users & Community
        |----------------------------------------------------------------
        */
        Route::controller(UserController::class)
            ->prefix('users')
            ->name('users.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/reports', 'reports')->name('reports');
                Route::post('/{id}/suspend', 'suspend')->whereNumber('id')->name('suspend');
                Route::post('/{id}/activate', 'activate')->whereNumber('id')->name('activate');
                Route::get('/{id}', 'show')->whereNumber('id')->name('show');
            });
        // Shares the reviews listing/controller — kept as an alias route.
        Route::get('/users/reviews', [ReviewController::class, 'index'])->name('users.reviews');

        Route::controller(SubmissionController::class)
            ->prefix('submissions')
            ->name('submissions.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/all', 'all')->name('all');
            });

        /*
        |----------------------------------------------------------------
        | Media
        |----------------------------------------------------------------
        */
        Route::get('/media', [MediaController::class, 'index'])->name('media');

        /*
        |----------------------------------------------------------------
        | Analytics
        |----------------------------------------------------------------
        */
        Route::controller(AnalyticsController::class)
            ->prefix('analytics')
            ->name('analytics.')
            ->group(function () {
                Route::get('/website', 'website')->name('website');
                Route::get('/tools', 'tools')->name('tools');
                Route::get('/search', 'search')->name('search');
                Route::get('/comparisons', 'comparisons')->name('comparisons');
                Route::get('/content', 'content')->name('content');
                Route::get('/trending', 'trending')->name('trending');
            });

        /*
        |----------------------------------------------------------------
        | System
        |----------------------------------------------------------------
        */
        Route::name('system.')->prefix('system')->group(function () {
            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
            Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs');
            Route::get('/roles', [RoleController::class, 'index'])->name('roles');
            Route::get('/security', [SecurityController::class, 'index'])->name('security');
            Route::get('/2fa', [TwoFactorController::class, 'index'])->name('2fa');
            Route::get('/backups', [BackupController::class, 'index'])->name('backups');
            Route::get('/api-monitoring', [ApiMonitoringController::class, 'index'])->name('api-monitoring');

            Route::get('/automation-monitor', [AutomationMonitorController::class, 'index'])->name('automation-monitor');
            Route::get('/data-verification', [DataVerificationController::class, 'index'])->name('data-verification');
            Route::get('/source-reliability', [SourceReliabilityController::class, 'index'])->name('source-reliability');
            Route::get('/health', [HealthController::class, 'index'])->name('health');

            Route::controller(ErrorController::class)
                ->prefix('errors')
                ->name('errors.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/{id}', 'show')->whereNumber('id')->name('show');
                });

            Route::get('/feature-flags', [FeatureFlagController::class, 'index'])->name('feature-flags');
            Route::get('/seo', [SeoController::class, 'index'])->name('seo');
            Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations');

            Route::get('/settings', [SettingController::class, 'index'])->name('settings');
            Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

            Route::get('/notification-rules', [NotificationRuleController::class, 'index'])->name('notification-rules');
        });

        // News source management lives under /system in the sidebar but
        // is its own controller since it's really a News concern.
        Route::get('/news/sources', [NewsSourceController::class, 'index'])->name('system.news-sources');
    });
Route::middleware('auth')->group(function () {
    Route::get('/tools/{tool}/review', [PublicReviewController::class, 'create'])->name('reviews.create');
    Route::post('/tools/{tool}/review', [PublicReviewController::class, 'store'])->name('reviews.store');
});
Route::post('/reviews/{id}/approve', [\App\Http\Controllers\Admin\Content\ReviewController::class, 'approve'])->name('reviews.approve');
Route::post('/reviews/{id}/flag', [\App\Http\Controllers\Admin\Content\ReviewController::class, 'flag'])->name('reviews.flag');
Route::delete('/reviews/{id}', [\App\Http\Controllers\Admin\Content\ReviewController::class, 'destroy'])->name('reviews.destroy');
