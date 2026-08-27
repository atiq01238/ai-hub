<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\SignupController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Frontend\EmailPreferenceController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\ToolController;
use App\Http\Controllers\Admin\AiModelController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DataImportController;
use App\Http\Controllers\Admin\ToolImportController;
use App\Http\Controllers\Admin\PricingImportController;
use App\Http\Controllers\Admin\BenchmarkImportController;
use App\Http\Controllers\Admin\LogoImportController;
use App\Http\Controllers\Admin\TaxonomyController;
use App\Http\Controllers\Admin\ComparisonController;
use App\Http\Controllers\Admin\TestlabController;
use App\Http\Controllers\Admin\BenchmarkController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\ModelPricingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\AnalyticsController;

use App\Http\Controllers\Admin\Content\ArticleController;
use App\Http\Controllers\Admin\Content\ReviewController;
use App\Http\Controllers\Admin\Content\SocialController;
use App\Http\Controllers\Admin\Content\ApprovalWorkflowController;

use App\Http\Controllers\Admin\System\NotificationController;
use App\Http\Controllers\Admin\System\SystemOverviewController;
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
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\ToolController as FrontendToolController;
use App\Http\Controllers\Frontend\ModelController as FrontendModelController;
use App\Http\Controllers\Frontend\NewsController as FrontendNewsController;
use App\Http\Controllers\Frontend\ComparisonController as FrontendComparisonController;
use App\Http\Controllers\Frontend\CompanyController as FrontendCompanyController;
use App\Http\Controllers\Frontend\CompanySitemapController;
use App\Http\Controllers\Frontend\SeoSitemapController;
use App\Http\Controllers\Frontend\ArticleController as FrontendArticleController;
use App\Http\Controllers\Frontend\ReviewController as FrontendReviewController;
use App\Http\Controllers\Frontend\TestLabController as FrontendTestLabController;
use App\Http\Controllers\Frontend\PricingIntelligenceController as FrontendPricingIntelligenceController;
use App\Http\Controllers\Frontend\SearchController as FrontendSearchController;
use App\Http\Controllers\Frontend\CategoryController as FrontendCategoryController;
use App\Http\Controllers\Frontend\TaxonomyDiscoveryController;
use App\Http\Controllers\Frontend\BenchmarkController as FrontendBenchmarkController;
use App\Http\Controllers\Frontend\TrendingController as FrontendTrendingController;
use App\Http\Controllers\Frontend\SavedController as FrontendSavedController;
use App\Http\Controllers\Frontend\PageController as FrontendPageController;
use App\Http\Controllers\Frontend\PublicMediaController;
use App\Http\Controllers\Admin\SubmissionController as AdminSubmissionController;
use App\Http\Controllers\ReportController as PublicReportController;
use App\Http\Controllers\SubmissionController as PublicSubmissionController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\RequirePermission;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/media/{path}', [PublicMediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.public');
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/ai-tools', [FrontendToolController::class, 'index'])->name('tools.index');
Route::get('/ai-tools/{tool:slug}', [FrontendToolController::class, 'show'])->name('tools.show');
Route::get('/ai-models', [FrontendModelController::class, 'index'])->name('models.index');
Route::get('/ai-models/{model:slug}', [FrontendModelController::class, 'show'])->name('models.show');
// Legacy public news URL: preserve old links and avoid duplicate/404 SEO signals.
Route::redirect('/news', '/ai-news', 301);

Route::get('/ai-news', [FrontendNewsController::class, 'index'])->name('news.index');
Route::get('/ai-news/{news:slug}', [FrontendNewsController::class, 'show'])->name('news.show');
// Published comparisons remain public for discovery/SEO. Creating a custom
// comparison is an authenticated user action. Keep static routes before the
// dynamic slug route so /compare/builder cannot be captured as a slug.
Route::get('/compare', [FrontendComparisonController::class, 'index'])->name('comparisons.index');
Route::middleware(['auth', 'verified', EnsureAccountIsActive::class])->group(function () {
    Route::get('/compare/builder', [FrontendComparisonController::class, 'builder'])->name('comparisons.builder');
    Route::get('/compare/preview', [FrontendComparisonController::class, 'preview'])->name('comparisons.preview');
});
Route::get('/compare/{comparison:slug}', [FrontendComparisonController::class, 'show'])->name('comparisons.show');
Route::get('/sitemap.xml', [SeoSitemapController::class, 'index'])->name('sitemap.index');
Route::get('/sitemap-companies.xml', CompanySitemapController::class)->name('sitemap.companies');
Route::get('/sitemap-tools.xml', [SeoSitemapController::class, 'tools'])->name('sitemap.tools');
Route::get('/sitemap-models.xml', [SeoSitemapController::class, 'models'])->name('sitemap.models');
Route::get('/sitemap-news.xml', [SeoSitemapController::class, 'news'])->name('sitemap.news');
Route::get('/sitemap-articles.xml', [SeoSitemapController::class, 'articles'])->name('sitemap.articles');
Route::get('/sitemap-reviews.xml', [SeoSitemapController::class, 'reviews'])->name('sitemap.reviews');
Route::get('/sitemap-pricing.xml', [SeoSitemapController::class, 'pricing'])->name('sitemap.pricing');
Route::get('/sitemap-comparisons.xml', [SeoSitemapController::class, 'comparisons'])->name('sitemap.comparisons');
Route::get('/sitemap-benchmarks.xml', [SeoSitemapController::class, 'benchmarks'])->name('sitemap.benchmarks');
Route::get('/sitemap-pages.xml', [SeoSitemapController::class, 'pages'])->name('sitemap.pages');
Route::get('/sitemap-taxonomy.xml', [SeoSitemapController::class, 'taxonomy'])->name('sitemap.taxonomy');
Route::get('/sitemap-testlab.xml', [SeoSitemapController::class, 'testLab'])->name('sitemap.testlab');
Route::get('/companies', [FrontendCompanyController::class, 'index'])->name('companies.index');
Route::get('/companies/{company:slug}', [FrontendCompanyController::class, 'show'])->name('companies.show');
Route::get('/articles', [FrontendArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{article:slug}', [FrontendArticleController::class, 'show'])->name('articles.show');
Route::get('/reviews', [FrontendReviewController::class, 'index'])->name('reviews.index');
Route::get('/reviews/{review}', [FrontendReviewController::class, 'show'])->whereNumber('review')->name('reviews.show');
Route::get('/test-lab', [FrontendTestLabController::class, 'index'])->name('testlab.index');
Route::get('/test-lab/leaderboard', [FrontendTestLabController::class, 'leaderboard'])->name('testlab.leaderboard');
Route::get('/test-lab/{test}', [FrontendTestLabController::class, 'show'])->name('testlab.show');
Route::get('/pricing-intelligence', [FrontendPricingIntelligenceController::class, 'index'])->name('pricing.index');
Route::get('/pricing-intelligence/{tool:slug}', [FrontendPricingIntelligenceController::class, 'show'])->name('pricing.show');
Route::get('/search/suggest', [FrontendSearchController::class, 'suggest'])
    ->middleware('throttle:60,1')
    ->name('search.suggest');
Route::post('/search/click', [FrontendSearchController::class, 'click'])
    ->middleware('throttle:120,1')
    ->name('search.click');
Route::get('/search', [FrontendSearchController::class, 'index'])->name('search.index');
Route::get('/categories', [FrontendCategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{legacySlug}', [FrontendCategoryController::class, 'legacy'])
    ->whereIn('legacySlug', collect(config('taxonomy_v2.product_categories', []))
        ->flatMap(fn ($definition) => collect($definition['legacy'] ?? [])->map(fn ($name) => \Illuminate\Support\Str::slug($name)))
        ->filter()->unique()->values()->all())
    ->name('categories.legacy');
Route::get('/categories/{category:slug}/{subcategory:slug}', [FrontendCategoryController::class, 'subcategory'])->name('categories.subcategories.show');
Route::get('/categories/{category:slug}', [FrontendCategoryController::class, 'show'])->name('categories.show');
Route::get('/features', [TaxonomyDiscoveryController::class, 'features'])->name('features.index');
Route::get('/features/{feature:slug}', [TaxonomyDiscoveryController::class, 'feature'])->name('features.show');
Route::get('/use-cases', [TaxonomyDiscoveryController::class, 'useCases'])->name('use-cases.index');
Route::get('/use-cases/{useCase:slug}', [TaxonomyDiscoveryController::class, 'useCase'])->name('use-cases.show');
Route::get('/topics', [TaxonomyDiscoveryController::class, 'topics'])->name('topics.index');
Route::get('/topics/{category:slug}', [TaxonomyDiscoveryController::class, 'topic'])->name('topics.show');
Route::get('/benchmarks', [FrontendBenchmarkController::class, 'index'])->name('benchmarks.index');
Route::get('/benchmarks/{benchmark:slug}', [FrontendBenchmarkController::class, 'show'])->name('benchmarks.show');
Route::get('/trending', [FrontendTrendingController::class, 'index'])->name('trending.index');
Route::get('/saved/status', [FrontendSavedController::class, 'status'])->middleware(EnsureAccountIsActive::class)->name('saved.status');
Route::post('/saved/intent', [FrontendSavedController::class, 'intent'])
    ->middleware([EnsureAccountIsActive::class, 'throttle:30,1'])
    ->name('saved.intent');

Route::middleware(['auth', 'verified', EnsureAccountIsActive::class])->group(function () {
    Route::get('/saved', [FrontendSavedController::class, 'index'])
        ->name('saved.index');

    Route::post('/saved/toggle', [FrontendSavedController::class, 'toggle'])
        ->middleware('throttle:60,1')
        ->name('saved.toggle');
});
Route::get('/about', [FrontendPageController::class, 'about'])->name('about');
Route::get('/methodology', [FrontendPageController::class, 'methodology'])->name('methodology');
Route::get('/contact', [FrontendPageController::class, 'contact'])->name('contact');
Route::get('/privacy', [FrontendPageController::class, 'privacy'])->name('privacy');
Route::get('/terms', [FrontendPageController::class, 'terms'])->name('terms');
Route::get('/cookies', [FrontendPageController::class, 'cookies'])->name('cookies');
Route::get('/disclosures', [FrontendPageController::class, 'disclosures'])->name('disclosures');
Route::post('/contact', [FrontendPageController::class, 'storeContact'])->middleware('throttle:5,10')->name('contact.store');

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
| Guest-only screens are gated so a logged-in admin can't reopen them,
| and logout requires a session to end.
*/

Route::middleware('guest')->group(function () {
    Route::get('/auth/signup', [SignupController::class, 'index'])->name('signup');
    Route::post('/auth/signup', [SignupController::class, 'store'])->middleware('throttle:5,10')->name('signup.store');

    Route::get('/auth/login', [LoginController::class, 'index'])->name('login');
    Route::post('/auth/login', [LoginController::class, 'authenticate'])->middleware('throttle:10,1')->name('login.authenticate');

    Route::get('/auth/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/auth/forgot-password', [ForgotPasswordController::class, 'send'])->middleware('throttle:5,10')->name('password.email');
    Route::get('/auth/reset-password/{token}', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('/auth/reset-password', [ResetPasswordController::class, 'reset'])->middleware('throttle:10,10')->name('password.update');

    Route::get('/auth/social/{provider}', [SocialAuthController::class, 'redirect'])
        ->whereIn('provider', ['google', 'apple'])
        ->name('social.redirect');
    Route::match(['GET', 'POST'], '/auth/social/{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', ['google', 'apple'])
        ->name('social.callback');
});

Route::get('/email/verify', fn () => view('auth.verify-email'))
    ->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('account.onboarding')->with('status', 'Email verified. Welcome to AI Orbit!');
})->middleware(['auth','signed','throttle:6,1'])->name('verification.verify');
Route::post('/email/verification-notification', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) return redirect()->route('home');
    $request->user()->sendEmailVerificationNotification();
    return back()->with('status', 'verification-link-sent');
})->middleware(['auth','throttle:6,1'])->name('verification.send');

Route::get('/email/unsubscribe/{user}', [EmailPreferenceController::class, 'unsubscribe'])
    ->middleware('signed')->name('email.unsubscribe');

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

Route::middleware(['auth', EnsureAccountIsActive::class, 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->middleware(RequirePermission::class . ':Dashboard,View')->name('dashboard');

        /*
        |----------------------------------------------------------------
        | AI Intelligence — News
        |----------------------------------------------------------------
        */
        Route::controller(\App\Http\Controllers\Admin\Discovery\DiscoveryController::class)
            ->prefix('discovery')
            ->name('discovery.')
            ->group(function () {
                Route::get('/', 'index')->middleware(RequirePermission::class . ':AI News,View')->name('index');
                Route::post('/scan-now', 'scanNow')->middleware(RequirePermission::class . ':AI News,Edit')->name('scan-now');
                Route::get('/{id}', 'show')->whereNumber('id')->middleware(RequirePermission::class . ':AI News,View')->name('show');
                Route::post('/{id}/ignore', 'ignore')->whereNumber('id')->middleware(RequirePermission::class . ':AI News,Edit')->name('ignore');
                Route::post('/{id}/restore', 'restore')->whereNumber('id')->middleware(RequirePermission::class . ':AI News,Edit')->name('restore');
                Route::post('/{id}/merge', 'merge')->whereNumber('id')->middleware(RequirePermission::class . ':AI News,Edit')->name('merge');
                Route::post('/{id}/tool', 'convertToTool')->whereNumber('id')->middleware(RequirePermission::class . ':AI Tools,Add')->name('tool');
                Route::post('/{id}/model', 'convertToModel')->whereNumber('id')->middleware(RequirePermission::class . ':AI Models,Add')->name('model');
                Route::put('/sources/{id}', 'updateSource')->whereNumber('id')->middleware(RequirePermission::class . ':AI News,Edit')->name('sources.update');
            });

        Route::controller(NewsController::class)
            ->prefix('news')
            ->name('news.')
            ->group(function () {
                Route::get('/', 'index')->middleware(RequirePermission::class . ':AI News,View')->name('index');
                Route::get('/breaking', 'breaking')->middleware(RequirePermission::class . ':AI News,View')->name('breaking');
                Route::get('/trending', 'trending')->middleware(RequirePermission::class . ':Analytics,View')->name('trending');
                Route::get('/updates', 'updates')->middleware(RequirePermission::class . ':AI News,View')->name('updates');
                Route::get('/saved', 'saved')->middleware(RequirePermission::class . ':AI News,View')->name('saved');
                Route::post('/{id}/save', 'toggleSaved')->whereNumber('id')->middleware(RequirePermission::class . ':AI News,Edit')->name('save');
                Route::get('/create', 'create')->middleware(RequirePermission::class . ':AI News,Add')->name('create');
                Route::get('/duplicates', 'duplicates')->middleware(RequirePermission::class . ':AI News,View')->name('duplicates');
                Route::post('/fetch-now', 'fetchNow')->middleware(RequirePermission::class . ':AI News,Add')->name('fetch-now');
                Route::post('/{id}/article-draft', 'createArticleDraft')->whereNumber('id')->middleware(RequirePermission::class . ':Content,Add')->name('article-draft');
                Route::get('/{id}/edit', 'edit')->whereNumber('id')->middleware(RequirePermission::class . ':AI News,Edit')->name('edit');
                Route::post('/', 'store')->middleware(RequirePermission::class . ':AI News,Add')->name('store');
                Route::put('/{id}', 'update')->whereNumber('id')->middleware(RequirePermission::class . ':AI News,Edit')->name('update');
                Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':AI News,Delete')->name('destroy');
                Route::get('/{id}', 'show')->whereNumber('id')->middleware(RequirePermission::class . ':AI News,View')->name('show');
            });

        /*
        |----------------------------------------------------------------
        | AI Management — Tools / Models / Companies / Taxonomy
        |----------------------------------------------------------------
        | Full CRUD, so these are real Route::resource declarations
        | instead of one-off Route::view calls. `only()` drops the
        | endpoints these modules don't use.
        */
        Route::controller(ToolController::class)->prefix('tools')->name('tools.')->group(function () {
            Route::get('/', 'index')->middleware(RequirePermission::class . ':AI Tools,View')->name('index');
            Route::get('/create', 'create')->middleware(RequirePermission::class . ':AI Tools,Add')->name('create');
            Route::post('/', 'store')->middleware(RequirePermission::class . ':AI Tools,Add')->name('store');
            Route::get('/{id}/edit', 'edit')->whereNumber('id')->middleware(RequirePermission::class . ':AI Tools,Edit')->name('edit');
            Route::put('/{id}', 'update')->whereNumber('id')->middleware(RequirePermission::class . ':AI Tools,Edit')->name('update');
            Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':AI Tools,Delete')->name('destroy');
            Route::get('/{id}', 'show')->whereNumber('id')->middleware(RequirePermission::class . ':AI Tools,View')->name('show');
        });

        Route::controller(AiModelController::class)->prefix('models')->name('models.')->group(function () {
            Route::get('/', 'index')->middleware(RequirePermission::class . ':AI Models,View')->name('index');
            Route::get('/create', 'create')->middleware(RequirePermission::class . ':AI Models,Add')->name('create');
            Route::post('/', 'store')->middleware(RequirePermission::class . ':AI Models,Add')->name('store');
            Route::get('/{id}/edit', 'edit')->whereNumber('id')->middleware(RequirePermission::class . ':AI Models,Edit')->name('edit');
            Route::put('/{id}', 'update')->whereNumber('id')->middleware(RequirePermission::class . ':AI Models,Edit')->name('update');
            Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':AI Models,Delete')->name('destroy');
            Route::get('/{id}', 'show')->whereNumber('id')->middleware(RequirePermission::class . ':AI Models,View')->name('show');
        });


        Route::controller(DataImportController::class)->prefix('data-import')->name('data-import.')->group(function () {
            Route::get('/', 'index')->middleware(RequirePermission::class . ':AI Companies,View')->name('index');
            Route::get('/companies/template', 'companyTemplate')->middleware(RequirePermission::class . ':AI Companies,View')->name('companies.template');
            Route::get('/companies/template-xlsx', 'companyXlsxTemplate')->middleware(RequirePermission::class . ':AI Companies,View')->name('companies.template-xlsx');
            Route::post('/companies/preview', 'previewCompanies')->middleware(RequirePermission::class . ':AI Companies,Add')->name('companies.preview');
            Route::post('/companies/import', 'importCompanies')->middleware(RequirePermission::class . ':AI Companies,Add')->name('companies.commit');
            Route::get('/models/template', 'modelTemplate')->middleware(RequirePermission::class . ':AI Models,View')->name('models.template');
            Route::post('/models/preview', 'previewModels')->middleware(RequirePermission::class . ':AI Models,Add')->name('models.preview');
            Route::post('/models/import', 'importModels')->middleware(RequirePermission::class . ':AI Models,Add')->name('models.commit');
        });


        Route::prefix('data-import')->name('data-import.')->group(function () {
            Route::get('/tools/template', [ToolImportController::class, 'template'])->middleware(RequirePermission::class . ':AI Tools,View')->name('tools.template');
            Route::post('/tools/preview', [ToolImportController::class, 'preview'])->middleware(RequirePermission::class . ':AI Tools,Add')->name('tools.preview');
            Route::post('/tools/import', [ToolImportController::class, 'commit'])->middleware(RequirePermission::class . ':AI Tools,Add')->name('tools.commit');

            Route::get('/pricing/template', [PricingImportController::class, 'template'])->middleware(RequirePermission::class . ':Pricing,View')->name('pricing.template');
            Route::post('/pricing/preview', [PricingImportController::class, 'preview'])->middleware(RequirePermission::class . ':Pricing,Add')->name('pricing.preview');
            Route::post('/pricing/import', [PricingImportController::class, 'commit'])->middleware(RequirePermission::class . ':Pricing,Add')->name('pricing.commit');

            Route::get('/benchmarks/template', [BenchmarkImportController::class, 'template'])->middleware(RequirePermission::class . ':Benchmarks,View')->name('benchmarks.template');
            Route::post('/benchmarks/preview', [BenchmarkImportController::class, 'preview'])->middleware(RequirePermission::class . ':Benchmarks,Add')->name('benchmarks.preview');
            Route::post('/benchmarks/import', [BenchmarkImportController::class, 'commit'])->middleware(RequirePermission::class . ':Benchmarks,Add')->name('benchmarks.commit');

            Route::get('/logos', [LogoImportController::class, 'index'])->middleware(RequirePermission::class . ':AI Companies,View')->name('logos.index');
            Route::post('/logos/companies/{id}', [LogoImportController::class, 'saveCompany'])->whereNumber('id')->middleware(RequirePermission::class . ':AI Companies,Edit')->name('logos.company-save');
            Route::post('/logos/tools/{id}', [LogoImportController::class, 'saveTool'])->whereNumber('id')->middleware(RequirePermission::class . ':AI Tools,Edit')->name('logos.tool-save');
            Route::post('/logos/models/{id}/fallback', [LogoImportController::class, 'useCompanyFallback'])->whereNumber('id')->middleware(RequirePermission::class . ':AI Models,Edit')->name('logos.model-fallback');
        });

        Route::controller(CompanyController::class)->prefix('companies')->name('companies.')->group(function () {
            Route::get('/', 'index')->middleware(RequirePermission::class . ':AI Companies,View')->name('index');
            Route::get('/create', 'create')->middleware(RequirePermission::class . ':AI Companies,Add')->name('create');
            Route::post('/', 'store')->middleware(RequirePermission::class . ':AI Companies,Add')->name('store');
            Route::get('/{id}/edit', 'edit')->whereNumber('id')->middleware(RequirePermission::class . ':AI Companies,Edit')->name('edit');
            Route::put('/{id}', 'update')->whereNumber('id')->middleware(RequirePermission::class . ':AI Companies,Edit')->name('update');
            Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':AI Companies,Delete')->name('destroy');
            Route::get('/{id}', 'show')->whereNumber('id')->middleware(RequirePermission::class . ':AI Companies,View')->name('show');
        });

        Route::controller(TaxonomyController::class)
            ->prefix('taxonomy')
            ->name('taxonomy.')
            ->group(function () {
                Route::get('/categories', 'categories')->middleware(RequirePermission::class . ':Taxonomy,View')->name('categories');
                Route::get('/subcategories', 'subcategories')->middleware(RequirePermission::class . ':Taxonomy,View')->name('subcategories');
                Route::get('/features', 'features')->middleware(RequirePermission::class . ':Taxonomy,View')->name('features');
                Route::get('/use-cases', 'useCases')->middleware(RequirePermission::class . ':Taxonomy,View')->name('use-cases');
                Route::get('/tags', 'tags')->middleware(RequirePermission::class . ':Taxonomy,View')->name('tags');
                Route::get('/content-topics', 'contentTopics')->middleware(RequirePermission::class . ':Taxonomy,View')->name('content-topics');
                Route::post('/{type}', 'store')->middleware(RequirePermission::class . ':Taxonomy,Add')->name('store');
                Route::put('/{type}/{id}', 'update')->middleware(RequirePermission::class . ':Taxonomy,Edit')->name('update');
                Route::delete('/{type}/{id}', 'destroy')->middleware(RequirePermission::class . ':Taxonomy,Delete')->name('destroy');
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
                Route::get('/', 'index')->middleware(RequirePermission::class . ':Comparisons,View')->name('index');
                Route::get('/builder', 'builder')->middleware(RequirePermission::class . ':Comparisons,Add')->name('builder');
                Route::get('/metrics', 'metrics')->middleware(RequirePermission::class . ':Comparisons,View')->name('metrics');
                Route::post('/', 'store')->middleware(RequirePermission::class . ':Comparisons,Add')->name('store');
                Route::get('/{id}/edit', 'edit')->whereNumber('id')->middleware(RequirePermission::class . ':Comparisons,Edit')->name('edit');
                Route::put('/{id}', 'update')->whereNumber('id')->middleware(RequirePermission::class . ':Comparisons,Edit')->name('update');
                Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':Comparisons,Delete')->name('destroy');
                Route::get('/{id}', 'show')->whereNumber('id')->middleware(RequirePermission::class . ':Comparisons,View')->name('show');
            });

       Route::controller(TestlabController::class)
            ->prefix('testlab')
            ->name('testlab.')
            ->group(function () {
                Route::get('/', 'index')->middleware(RequirePermission::class . ':AI Test Lab,View')->name('index');
                Route::get('/results', 'results')->middleware(RequirePermission::class . ':AI Test Lab,View')->name('results');
                Route::post('/', 'store')->middleware(RequirePermission::class . ':AI Test Lab,Add')->name('store');
                Route::get('/{id}', 'show')->whereNumber('id')->middleware(RequirePermission::class . ':AI Test Lab,View')->name('show');
                Route::put('/{id}', 'update')->whereNumber('id')->middleware(RequirePermission::class . ':AI Test Lab,Edit')->name('update');
                Route::post('/{id}/models', 'addModels')->whereNumber('id')->middleware(RequirePermission::class . ':AI Test Lab,Edit')->name('models.add');
                Route::get('/{id}/export', 'export')->whereNumber('id')->middleware(RequirePermission::class . ':AI Test Lab,Export')->name('export');
                Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':AI Test Lab,Delete')->name('destroy');
            });

        Route::put('/testlab/runs/{runId}', [TestlabController::class, 'updateRun'])
            ->whereNumber('runId')
            ->middleware(RequirePermission::class . ':AI Test Lab,Edit')
            ->name('testlab.runs.update');

        Route::put('/testlab/results/{resultId}', [TestlabController::class, 'updateResult'])
            ->whereNumber('resultId')
            ->middleware(RequirePermission::class . ':AI Test Lab,Edit')
            ->name('testlab.results.update');
        Route::delete('/testlab/results/{resultId}', [TestlabController::class, 'destroyResult'])
            ->whereNumber('resultId')
            ->middleware(RequirePermission::class . ':AI Test Lab,Delete')
            ->name('testlab.results.destroy');


        Route::controller(BenchmarkController::class)
            ->prefix('benchmarks')
            ->name('benchmarks.')
            ->group(function () {
                Route::get('/', 'index')->middleware(RequirePermission::class . ':Benchmarks,View')->name('index');
                Route::get('/create', 'create')->middleware(RequirePermission::class . ':Benchmarks,Add')->name('create');
                Route::get('/results', 'results')->middleware(RequirePermission::class . ':Benchmarks,View')->name('results');
                Route::post('/', 'store')->middleware(RequirePermission::class . ':Benchmarks,Add')->name('store');
                Route::delete('/results/{resultId}', 'destroyResult')->whereNumber('resultId')->middleware(RequirePermission::class . ':Benchmarks,Delete')->name('results.destroy');
            });

        /*
        |----------------------------------------------------------------
        | Pricing
        |----------------------------------------------------------------
        */
        Route::controller(ModelPricingController::class)
            ->prefix('pricing/models')
            ->name('pricing.models.')
            ->group(function () {
                Route::get('/', 'index')->middleware(RequirePermission::class . ':Pricing,View')->name('index');
                Route::get('/{model}/sources', 'sources')->middleware(RequirePermission::class . ':Pricing,View')->name('sources');
                Route::post('/{model}/sources', 'storeSource')->middleware(RequirePermission::class . ':Pricing,Edit')->name('sources.store');
                Route::post('/{model}/sources/{source}/verify', 'verify')->middleware(RequirePermission::class . ':Pricing,Edit')->name('sources.verify');
                Route::post('/{model}/sources/{source}/approve', 'approve')->middleware(RequirePermission::class . ':Pricing,Edit')->name('sources.approve');
                Route::delete('/{model}/sources/{source}', 'destroySource')->middleware(RequirePermission::class . ':Pricing,Edit')->name('sources.destroy');
            });

        Route::controller(PricingController::class)
            ->prefix('pricing')
            ->name('pricing.')
            ->group(function () {
                Route::get('/', 'index')->middleware(RequirePermission::class . ':Pricing,View')->name('index');
                Route::get('/api', 'api')->middleware(RequirePermission::class . ':Pricing,View')->name('api');
                Route::get('/history', 'history')->middleware(RequirePermission::class . ':Pricing,View')->name('history');
                Route::get('/changes', 'changes')->middleware(RequirePermission::class . ':Pricing,View')->name('changes');
                Route::post('/changes/{id}/approve', 'approveChange')->whereNumber('id')->middleware(RequirePermission::class . ':Pricing,Edit')->name('changes.approve');
                Route::post('/changes/{id}/reject', 'rejectChange')->whereNumber('id')->middleware(RequirePermission::class . ':Pricing,Edit')->name('changes.reject');
                Route::post('/scan', 'runDetection')->middleware(RequirePermission::class . ':Pricing,Edit')->name('scan');

                Route::get('/create', 'create')->middleware(RequirePermission::class . ':Pricing,Add')->name('create');
                Route::post('/', 'store')->middleware(RequirePermission::class . ':Pricing,Add')->name('store');

                Route::get('/{id}/sources', 'sources')->whereNumber('id')->middleware(RequirePermission::class . ':Pricing,View')->name('sources');
                Route::post('/{id}/sources', 'storeSource')->whereNumber('id')->middleware(RequirePermission::class . ':Pricing,Edit')->name('sources.store');
                Route::post('/{id}/sources/{sourceId}/check', 'checkSource')->whereNumber('id')->whereNumber('sourceId')->middleware(RequirePermission::class . ':Pricing,Edit')->name('sources.check');
                Route::delete('/{id}/sources/{sourceId}', 'destroySource')->whereNumber('id')->whereNumber('sourceId')->middleware(RequirePermission::class . ':Pricing,Edit')->name('sources.destroy');

                Route::get('/{id}/edit', 'edit')->whereNumber('id')->middleware(RequirePermission::class . ':Pricing,Edit')->name('edit');
                Route::put('/{id}', 'update')->whereNumber('id')->middleware(RequirePermission::class . ':Pricing,Edit')->name('update');
                Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':Pricing,Delete')->name('destroy');
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
                    Route::get('/', 'index')->middleware(RequirePermission::class . ':Content,View')->name('index');
                    Route::get('/drafts', 'drafts')->middleware(RequirePermission::class . ':Content,View')->name('drafts');
                    Route::get('/editor', 'editor')->middleware(RequirePermission::class . ':Content,Add')->name('editor.create');
                    Route::get('/editor/{id}', 'editor')->whereNumber('id')->middleware(RequirePermission::class . ':Content,Edit')->name('editor.edit');
                    Route::post('/', 'store')->middleware(RequirePermission::class . ':Content,Add')->name('store');
                    Route::put('/{id}', 'update')->whereNumber('id')->middleware(RequirePermission::class . ':Content,Edit')->name('update');
                    Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':Content,Delete')->name('destroy');
                    Route::get('/{id}', 'show')->whereNumber('id')->middleware(RequirePermission::class . ':Content,View')->name('show');
                });
            Route::get('/guides', [ArticleController::class, 'guides'])->middleware(RequirePermission::class . ':Content,View')->name('guides');

            Route::controller(ReviewController::class)
                ->prefix('reviews')
                ->name('reviews.')
                ->group(function () {
                    Route::get('/', 'index')->middleware(RequirePermission::class . ':Reviews,View')->name('index');
                    Route::get('/editor', 'editor')->middleware(RequirePermission::class . ':Reviews,Add')->name('editor');
                    Route::post('/', 'store')->middleware(RequirePermission::class . ':Reviews,Add')->name('store');
                    Route::get('/{id}', 'show')->whereNumber('id')->middleware(RequirePermission::class . ':Reviews,View')->name('show');
                    Route::post('/{id}/approve', 'approve')->whereNumber('id')->middleware(RequirePermission::class . ':Reviews,Publish')->name('approve');
                    Route::post('/{id}/flag', 'flag')->whereNumber('id')->middleware(RequirePermission::class . ':Reviews,Edit')->name('flag');
                    Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':Reviews,Delete')->name('destroy');
                });
            Route::controller(SocialController::class)
                ->prefix('social')
                ->name('social.')
                ->group(function () {
                    Route::get('/', 'index')->middleware(RequirePermission::class . ':Content,View')->name('index');
                    Route::get('/create', 'create')->middleware(RequirePermission::class . ':Content,Add')->name('create');
                    Route::post('/', 'store')->middleware(RequirePermission::class . ':Content,Add')->name('store');
                    Route::get('/{id}/edit', 'edit')->whereNumber('id')->middleware(RequirePermission::class . ':Content,Edit')->name('edit');
                    Route::put('/{id}', 'update')->whereNumber('id')->middleware(RequirePermission::class . ':Content,Edit')->name('update');
                    Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':Content,Delete')->name('destroy');
                });


            Route::get('/approval-workflow', [ApprovalWorkflowController::class, 'index'])->middleware(RequirePermission::class . ':Content,View')->name('approval-workflow');
            Route::post('/approval-workflow/{id}/submit', [ApprovalWorkflowController::class, 'submit'])->whereNumber('id')->middleware(RequirePermission::class . ':Content,Edit')->name('approval.submit');
            Route::post('/approval-workflow/{id}/request-changes', [ApprovalWorkflowController::class, 'requestChanges'])->whereNumber('id')->middleware(RequirePermission::class . ':Content,Edit')->name('approval.request-changes');
            Route::post('/approval-workflow/{id}/approve', [ApprovalWorkflowController::class, 'approve'])->whereNumber('id')->middleware(RequirePermission::class . ':Content,Publish')->name('approval.approve');
            Route::post('/approval-workflow/{id}/resubmit', [ApprovalWorkflowController::class, 'resubmit'])->whereNumber('id')->middleware(RequirePermission::class . ':Content,Edit')->name('approval.resubmit');
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
                Route::get('/', 'index')->middleware(RequirePermission::class . ':Users,View')->name('index');
                Route::post('/{id}/suspend', 'suspend')->whereNumber('id')->middleware(RequirePermission::class . ':Users,Edit')->name('suspend');
                Route::post('/{id}/activate', 'activate')->whereNumber('id')->middleware(RequirePermission::class . ':Users,Edit')->name('activate');
                Route::post('/{id}/assign-role', 'assignRole')->whereNumber('id')->middleware([
                    RequirePermission::class . ':Users,Edit',
                    RequirePermission::class . ':Roles & Permissions,Edit',
                ])->name('assign-role');
                Route::patch('/{id}/access', 'updateAccess')->whereNumber('id')->middleware([
                    RequirePermission::class . ':Users,Edit',
                    RequirePermission::class . ':Roles & Permissions,Edit',
                ])->name('access');
                Route::post('/{id}/restore', 'restore')->whereNumber('id')->middleware(RequirePermission::class . ':Users,Delete')->name('restore');
                Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':Users,Delete')->name('destroy');
                Route::get('/{id}', 'show')->whereNumber('id')->middleware(RequirePermission::class . ':Users,View')->name('show');
            });

        Route::controller(ContactMessageController::class)
            ->prefix('contact-messages')
            ->name('contact-messages.')
            ->group(function () {
                Route::get('/', 'index')->middleware(RequirePermission::class . ':Users,View')->name('index');
                Route::post('/{contactMessage}/reply', 'reply')->middleware(RequirePermission::class . ':Users,Edit')->name('reply');
                Route::patch('/{contactMessage}/status', 'updateStatus')->middleware(RequirePermission::class . ':Users,Edit')->name('status');
                Route::get('/{contactMessage}', 'show')->middleware(RequirePermission::class . ':Users,View')->name('show');
            });

        Route::controller(ReviewController::class)
            ->prefix('community/reviews')
            ->name('community.reviews.')
            ->group(function () {
                Route::get('/', 'communityIndex')->middleware(RequirePermission::class . ':Reviews,View')->name('index');
                Route::get('/{id}', 'communityShow')->whereNumber('id')->middleware(RequirePermission::class . ':Reviews,View')->name('show');
            });

        // Backward-compatible links from the older sidebar.
        Route::redirect('/users/reviews', '/admin/community/reviews')->name('users.reviews');
        Route::redirect('/users/reports', '/admin/community/reports')->name('users.reports');

        Route::controller(AdminSubmissionController::class)
            ->prefix('submissions')
            ->name('submissions.')
            ->group(function () {
                Route::get('/', 'index')->middleware(RequirePermission::class . ':Submissions,View')->name('index');
                Route::get('/{id}', 'show')->whereNumber('id')->middleware(RequirePermission::class . ':Submissions,View')->name('show');
                Route::post('/{id}/approve', 'approve')->whereNumber('id')->middleware(RequirePermission::class . ':Submissions,Edit')->name('approve');
                Route::post('/{id}/reject', 'reject')->whereNumber('id')->middleware(RequirePermission::class . ':Submissions,Edit')->name('reject');
                Route::post('/{id}/request-info', 'requestInfo')->whereNumber('id')->middleware(RequirePermission::class . ':Submissions,Edit')->name('request-info');
            });

        Route::redirect('/submissions/all', '/admin/submissions')->name('submissions.all');

        Route::controller(AdminReportController::class)
            ->prefix('community/reports')
            ->name('community.reports.')
            ->group(function () {
                Route::get('/', 'index')->middleware(RequirePermission::class . ':Reports,View')->name('index');
                Route::get('/{id}', 'show')->whereNumber('id')->middleware(RequirePermission::class . ':Reports,View')->name('show');
                Route::patch('/{id}/status', 'updateStatus')->whereNumber('id')->middleware(RequirePermission::class . ':Reports,Edit')->name('status');
        });

        /*
        |----------------------------------------------------------------
        | Media
        |----------------------------------------------------------------
        */
        Route::get('/media', [MediaController::class, 'index'])->middleware(RequirePermission::class . ':Content,View')->name('media');
        Route::delete('/media', [MediaController::class, 'destroy'])->middleware(RequirePermission::class . ':Content,Delete')->name('media.destroy');

        /*
        |----------------------------------------------------------------
        | Analytics
        |----------------------------------------------------------------
        */
        Route::controller(AnalyticsController::class)
            ->prefix('analytics')
            ->name('analytics.')
            ->group(function () {
                Route::get('/website', 'website')->middleware(RequirePermission::class . ':Analytics,View')->name('website');
                Route::get('/tools', 'tools')->middleware(RequirePermission::class . ':Analytics,View')->name('tools');
                Route::get('/search', 'search')->middleware(RequirePermission::class . ':Analytics,View')->name('search');
                Route::get('/comparisons', 'comparisons')->middleware(RequirePermission::class . ':Analytics,View')->name('comparisons');
                Route::get('/content', 'content')->middleware(RequirePermission::class . ':Analytics,View')->name('content');
                Route::get('/trending', 'trending')->middleware(RequirePermission::class . ':Analytics,View')->name('trending');
                Route::get('/{tab}/export', 'export')->whereIn('tab', ['website', 'tools', 'search', 'comparisons', 'content', 'trending'])->middleware(RequirePermission::class . ':Analytics,Export')->name('export');
            });

        /*
        |----------------------------------------------------------------
        | System
        |----------------------------------------------------------------
        */
        Route::name('system.')->prefix('system')->group(function () {
            Route::get('/', [SystemOverviewController::class, 'index'])->middleware(RequirePermission::class . ':Security,View')->name('overview');
            Route::controller(NotificationController::class)
                ->prefix('notifications')
                ->name('notifications.')
                ->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::post('/mark-all-read', 'markAllRead')->name('mark-all-read');
                    Route::post('/{id}/mark-read', 'markRead')->whereNumber('id')->name('mark-read');
                    Route::delete('/{id}', 'destroy')->whereNumber('id')->name('destroy');
                });
            Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
            Route::get('/email-deliveries', [\App\Http\Controllers\Admin\System\EmailDeliveryController::class, 'index'])
                ->middleware(RequirePermission::class . ':Notifications,View')->name('email-deliveries');
            Route::get('/activity-logs', [\App\Http\Controllers\Admin\System\ActivityLogController::class, 'index'])->middleware(RequirePermission::class . ':Security,View')->name('activity-logs');
            Route::controller(RoleController::class)
                ->prefix('roles')
                ->name('roles.')
                ->group(function () {
                    Route::post('/', 'store')->middleware(RequirePermission::class . ':Roles & Permissions,Add')->name('store');
                    Route::put('/{id}', 'update')->whereNumber('id')->middleware(RequirePermission::class . ':Roles & Permissions,Edit')->name('update');
                    Route::put('/{id}/permissions', 'updatePermissions')->whereNumber('id')->middleware(RequirePermission::class . ':Roles & Permissions,Edit')->name('permissions.update');
                    Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':Roles & Permissions,Delete')->name('destroy');

                });
            Route::get('/roles', [RoleController::class, 'index'])->middleware(RequirePermission::class . ':Roles & Permissions,View')->name('roles');
            Route::controller(\App\Http\Controllers\Admin\System\SecurityController::class)
                ->prefix('security')
                ->name('security.')
                ->group(function () {
                    Route::post('/sessions/{sessionId}/revoke', 'revokeSession')->middleware(RequirePermission::class . ':Security,Edit')->name('revoke-session');
                });
            Route::get('/security', [\App\Http\Controllers\Admin\System\SecurityController::class, 'index'])->middleware(RequirePermission::class . ':Security,View')->name('security');

            Route::get('/2fa', [TwoFactorController::class, 'index'])->name('2fa');
            Route::post('/2fa/setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
            Route::post('/2fa/confirm', [TwoFactorController::class, 'confirm'])->name('2fa.confirm');
            Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
            Route::get('/backups', [BackupController::class, 'index'])->middleware(RequirePermission::class . ':Backups,View')->name('backups');
            Route::post('/backups', [BackupController::class, 'store'])->middleware(RequirePermission::class . ':Backups,Add')->name('backups.store');
            Route::get('/backups/{filename}/download', [BackupController::class, 'download'])->middleware(RequirePermission::class . ':Backups,Export')->where('filename', '[A-Za-z0-9._-]+')->name('backups.download');
            Route::delete('/backups/{filename}', [BackupController::class, 'destroy'])->middleware(RequirePermission::class . ':Backups,Delete')->where('filename', '[A-Za-z0-9._-]+')->name('backups.destroy');
            Route::get('/api-monitoring', [ApiMonitoringController::class, 'index'])->middleware(RequirePermission::class . ':API Monitoring,View')->name('api-monitoring');
            Route::post('/api-monitoring/{provider}/test', [ApiMonitoringController::class, 'test'])->middleware(RequirePermission::class . ':API Monitoring,Edit')->where('provider', '[a-z0-9_-]+')->name('api-monitoring.test');

            Route::get('/automation-monitor', [AutomationMonitorController::class, 'index'])->name('automation-monitor');
            Route::put('/automation-monitor', [AutomationMonitorController::class, 'update'])->middleware(RequirePermission::class . ':Security,Edit')->name('automation-monitor.update');
            Route::post('/automation-monitor/run-now', [AutomationMonitorController::class, 'runNow'])->middleware(RequirePermission::class . ':Security,Edit')->name('automation-monitor.run-now');
            Route::get('/data-verification', [DataVerificationController::class, 'index'])->name('data-verification');
            Route::post('/data-verification/{id}/verify', [DataVerificationController::class, 'verify'])
                ->whereNumber('id')->middleware(RequirePermission::class . ':AI News,Edit')->name('data-verification.verify');
            Route::post('/data-verification/{id}/needs-verification', [DataVerificationController::class, 'needsVerification'])
                ->whereNumber('id')->middleware(RequirePermission::class . ':AI News,Edit')->name('data-verification.needs-verification');
            Route::get('/source-reliability', [SourceReliabilityController::class, 'index'])->name('source-reliability');
            Route::get('/health', [HealthController::class, 'index'])->middleware(RequirePermission::class . ':System Health,View')->name('health');

            Route::controller(ErrorController::class)
                ->prefix('errors')
                ->name('errors.')
                ->group(function () {
                    Route::get('/', 'index')->middleware(RequirePermission::class . ':Error Monitoring,View')->name('index');
                    Route::put('/{id}', 'updateStatus')->whereNumber('id')->middleware(RequirePermission::class . ':Error Monitoring,Edit')->name('update-status');
                    Route::get('/{id}', 'show')->whereNumber('id')->middleware(RequirePermission::class . ':Error Monitoring,View')->name('show');
                });
            Route::controller(FeatureFlagController::class)
                ->prefix('feature-flags')
                ->name('feature-flags.')
                ->group(function () {
                    Route::get('/', 'index')->middleware(RequirePermission::class . ':Feature Flags,View')->name('index');
                    Route::post('/', 'store')->middleware(RequirePermission::class . ':Feature Flags,Add')->name('store');
                    Route::post('/{id}/toggle', 'toggle')->whereNumber('id')->middleware(RequirePermission::class . ':Feature Flags,Edit')->name('toggle');
                    Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':Feature Flags,Delete')->name('destroy');
                });
            Route::get('/feature-flags', [FeatureFlagController::class, 'index'])->middleware(RequirePermission::class . ':Feature Flags,View')->name('feature-flags');

            Route::get('/seo', [SeoController::class, 'index'])->middleware(RequirePermission::class . ':SEO,View')->name('seo');
            Route::get('/integrations', [IntegrationController::class, 'index'])->middleware(RequirePermission::class . ':Integrations,View')->name('integrations');

            Route::get('/settings', [\App\Http\Controllers\Admin\System\SettingController::class, 'index'])->middleware(RequirePermission::class . ':Settings,View')->name('settings');
            Route::put('/settings', [\App\Http\Controllers\Admin\System\SettingController::class, 'update'])->middleware(RequirePermission::class . ':Settings,Edit')->name('settings.update');

            Route::get('/notification-rules', [\App\Http\Controllers\Admin\System\NotificationRuleController::class, 'index'])->name('notification-rules');
            Route::post('/notification-rules/{id}/toggle', [\App\Http\Controllers\Admin\System\NotificationRuleController::class, 'toggle'])
                ->whereNumber('id')
                ->middleware(RequirePermission::class . ':Settings,Edit')
                ->name('notification-rules.toggle');
        });

        // News source management lives under /system in the sidebar but
        // is its own controller since it's really a News concern.
        Route::controller(\App\Http\Controllers\Admin\System\NewsSourceController::class)
            ->prefix('system/news-sources')
            ->name('system.news-sources.')
            ->group(function () {
                Route::post('/', 'store')->middleware(RequirePermission::class . ':AI News,Add')->name('store');
                Route::post('/{id}/toggle', 'toggle')->whereNumber('id')->middleware(RequirePermission::class . ':AI News,Edit')->name('toggle');
                Route::post('/{id}/authority', 'authority')->whereNumber('id')->middleware(RequirePermission::class . ':AI News,Edit')->name('authority');
                Route::delete('/{id}', 'destroy')->whereNumber('id')->middleware(RequirePermission::class . ':AI News,Delete')->name('destroy');
            });

        Route::get('/system/news-sources', [\App\Http\Controllers\Admin\System\NewsSourceController::class, 'index'])
            ->name('system.news-sources');    });
Route::get('/suggest-tool', [PublicSubmissionController::class, 'create'])->name('submissions.create');
Route::post('/suggest-tool', [PublicSubmissionController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('submissions.store');

Route::middleware(['auth', 'verified', EnsureAccountIsActive::class])->group(function () {
    Route::get('/tools/{tool}/review', [PublicReviewController::class, 'create'])->name('reviews.create');
    Route::post('/tools/{tool}/review', [PublicReviewController::class, 'store'])
        ->middleware('throttle:10,1')->name('reviews.store');
    Route::post('/reports', [PublicReportController::class, 'store'])
        ->middleware('throttle:10,1')->name('reports.store');
});

Route::middleware('guest')->group(function () {
    Route::get('/login/2fa', [TwoFactorChallengeController::class, 'show'])->name('login.2fa');
    Route::post('/login/2fa', [TwoFactorChallengeController::class, 'verify'])->middleware('throttle:10,1')->name('login.2fa.verify');
});
