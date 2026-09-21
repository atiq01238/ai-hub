<?php

namespace App\Services\AdSense;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\Benchmark;
use App\Models\BenchmarkResult;
use App\Models\Category;
use App\Models\Comparison;
use App\Models\NewsItem;
use App\Models\Review;
use App\Models\Tool;
use App\Services\Seo\SeoContentQualityService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AdSenseReadinessService
{
    public function __construct(
        private readonly SeoContentQualityService $contentQuality,
    ) {
    }

    /**
     * Run non-destructive application/database/file checks.
     */
    public function audit(bool $production = false): array
    {
        $checks = collect();
        $canonical = rtrim((string) config('seo.canonical_url'), '/');
        $canonicalScheme = strtolower((string) parse_url($canonical, PHP_URL_SCHEME));
        $canonicalHost = strtolower((string) parse_url($canonical, PHP_URL_HOST));

        $this->add($checks, 'Connection', 'Canonical URL configured',
            $canonical !== '' ? 'PASS' : 'BLOCKER',
            $canonical !== '' ? $canonical : 'SEO canonical URL is empty.');

        $httpsStatus = $canonicalScheme === 'https' ? 'PASS' : ($production ? 'BLOCKER' : 'WARN');
        $this->add($checks, 'Connection', 'HTTPS canonical', $httpsStatus,
            $canonicalScheme === 'https' ? $canonical : 'Current canonical scheme is '.($canonicalScheme ?: 'missing').'. Local HTTP is expected; production must use HTTPS.');

        $debugStatus = (bool) config('app.debug') ? ($production ? 'BLOCKER' : 'WARN') : 'PASS';
        $this->add($checks, 'Connection', 'Production debug mode', $debugStatus,
            (bool) config('app.debug') ? 'APP_DEBUG is enabled.' : 'APP_DEBUG is disabled.');

        $publisherId = trim((string) config('adsense.publisher_id'));
        $clientId = trim((string) config('adsense.client_id'));
        $publisherValid = (bool) preg_match('/^pub-\d+$/', $publisherId);
        $clientValid = (bool) preg_match('/^ca-pub-\d+$/', $clientId);
        $this->add($checks, 'AdSense', 'Publisher/client IDs', $publisherValid && $clientValid ? 'PASS' : 'BLOCKER',
            'Publisher: '.($publisherId ?: 'missing').' | Client: '.($clientId ?: 'missing'));

        $adsTxtPath = public_path('ads.txt');
        $adsTxt = is_file($adsTxtPath) ? trim((string) file_get_contents($adsTxtPath)) : '';
        $expectedAdsFragment = 'google.com, '.$publisherId.', DIRECT, f08c47fec0942fa0';
        $adsTxtOk = $publisherValid && str_contains($adsTxt, $expectedAdsFragment);
        $this->add($checks, 'AdSense', 'ads.txt authorization', $adsTxtOk ? 'PASS' : 'BLOCKER',
            $adsTxtOk ? $expectedAdsFragment : 'Expected Google DIRECT publisher record was not found in public/ads.txt.');

        $contentRoutes = (array) config('adsense.content_route_patterns', []);
        $routeScopeOk = in_array('home', $contentRoutes, true)
            && in_array('tools.show', $contentRoutes, true)
            && ! in_array('login', $contentRoutes, true)
            && ! in_array('saved.index', $contentRoutes, true)
            && ! in_array('search.index', $contentRoutes, true);
        $this->add($checks, 'AdSense', 'Auto-ads route scope', $routeScopeOk ? 'PASS' : 'BLOCKER',
            $routeScopeOk
                ? count($contentRoutes).' publisher-content route patterns; private/search routes excluded.'
                : 'AdSense loader route allow-list is missing core content routes or includes a private/search route.');

        $layoutPath = resource_path('views/frontend/layouts/app.blade.php');
        $layout = is_file($layoutPath) ? (string) file_get_contents($layoutPath) : '';
        $scopedLoader = str_contains($layout, "config('adsense.content_route_patterns'")
            && str_contains($layout, '$adsenseQuerySafe')
            && str_contains($layout, '$adsenseRobotsSafe')
            && str_contains($layout, "config('adsense.client_id')");
        $this->add($checks, 'AdSense', 'Loader restricted to indexable canonical content', $scopedLoader ? 'PASS' : 'BLOCKER',
            $scopedLoader
                ? 'Global frontend layout gates Auto ads by content route, canonical query state and noindex robots state.'
                : 'Scoped AdSense loader guard must include route, query-string and noindex robots checks.');

        $robotsPath = public_path('robots.txt');
        $robots = is_file($robotsPath) ? (string) file_get_contents($robotsPath) : '';
        $expectedSitemap = $canonical !== '' ? 'Sitemap: '.$canonical.'/sitemap.xml' : '';
        $robotsPresent = $robots !== '';
        $rootBlocked = (bool) preg_match('/^Disallow:\s*\/\s*$/mi', $robots);
        $robotsSitemapOk = $expectedSitemap !== '' && str_contains($robots, $expectedSitemap);
        $this->add($checks, 'Crawl', 'robots.txt present and root crawlable', $robotsPresent && ! $rootBlocked ? 'PASS' : 'BLOCKER',
            !$robotsPresent ? 'public/robots.txt is missing.' : ($rootBlocked ? 'robots.txt contains Disallow: /.' : 'Root is crawlable.'));
        $this->add($checks, 'Crawl', 'robots sitemap canonical', $robotsSitemapOk ? 'PASS' : ($production ? 'BLOCKER' : 'WARN'),
            $robotsSitemapOk ? $expectedSitemap : 'robots.txt sitemap line does not match the current canonical URL.');

        $coreRoutes = [
            'home', 'tools.index', 'models.index', 'news.index', 'comparisons.index',
            'pricing.index', 'benchmarks.index', 'articles.index', 'companies.index',
        ];
        $trustRoutes = [
            'about', 'methodology', 'editorial-guidelines', 'sourcing-verification',
            'corrections-policy', 'contact', 'privacy', 'terms', 'cookies', 'disclosures',
        ];
        $sitemapRoutes = [
            'sitemap.index', 'sitemap.companies', 'sitemap.tools', 'sitemap.models',
            'sitemap.news', 'sitemap.articles', 'sitemap.reviews', 'sitemap.pricing',
            'sitemap.comparisons', 'sitemap.benchmarks', 'sitemap.taxonomy', 'sitemap.pages',
        ];

        $missingCore = collect($coreRoutes)->reject(fn (string $name) => Route::has($name))->values();
        $missingTrust = collect($trustRoutes)->reject(fn (string $name) => Route::has($name))->values();
        $missingSitemaps = collect($sitemapRoutes)->reject(fn (string $name) => Route::has($name))->values();
        $this->add($checks, 'Navigation', 'Core public routes', $missingCore->isEmpty() ? 'PASS' : 'BLOCKER',
            $missingCore->isEmpty() ? count($coreRoutes).'/'.count($coreRoutes).' available.' : 'Missing: '.$missingCore->join(', '));
        $this->add($checks, 'Trust', 'Trust/legal routes', $missingTrust->isEmpty() ? 'PASS' : 'BLOCKER',
            $missingTrust->isEmpty() ? count($trustRoutes).'/'.count($trustRoutes).' available.' : 'Missing: '.$missingTrust->join(', '));
        $this->add($checks, 'Crawl', 'Sitemap routes', $missingSitemaps->isEmpty() ? 'PASS' : 'BLOCKER',
            $missingSitemaps->isEmpty() ? count($sitemapRoutes).'/'.count($sitemapRoutes).' available.' : 'Missing: '.$missingSitemaps->join(', '));

        $sourceLeakPatterns = [
            'Not yet verified@else',
            '@elseUnknown / not yet verified',
            '% match@if',
        ];
        $leaks = $this->scanFrontendViewsFor($sourceLeakPatterns);
        $this->add($checks, 'Quality', 'Known template-leak signatures', $leaks->isEmpty() ? 'PASS' : 'BLOCKER',
            $leaks->isEmpty() ? 'No known leaked Blade/error signatures found in frontend view source.' : $leaks->take(5)->join(' | '));

        $this->appendPublisherTemplateChecks($checks);

        try {
            $this->appendContentChecks($checks);
        } catch (\Throwable $e) {
            report($e);
            $this->add($checks, 'Content', 'Database/content audit', 'BLOCKER', 'Content audit failed: '.$e->getMessage());
        }

        if ($production && ($canonicalHost === '' || in_array($canonicalHost, ['127.0.0.1', 'localhost'], true))) {
            $this->add($checks, 'Connection', 'Production canonical host', 'BLOCKER', 'Production audit is still pointing at a local host.');
        }

        return $this->summarize($checks);
    }

    /**
     * Optional HTTP smoke test against localhost or the deployed site.
     */
    public function smokeTest(string $baseUrl): array
    {
        $baseUrl = rtrim($baseUrl, '/');
        $checks = collect();

        if (! preg_match('#^https?://#i', $baseUrl)) {
            $this->add($checks, 'HTTP', 'Base URL', 'BLOCKER', 'Use a full http:// or https:// URL.');
            return $this->summarize($checks);
        }

        $paths = [
            ['/', 'html', true],
            ['/ai-tools', 'html', true],
            ['/ai-models', 'html', true],
            ['/ai-news', 'html', true],
            ['/compare', 'html', true],
            ['/pricing-intelligence', 'html', true],
            ['/benchmarks', 'html', true],
            ['/articles', 'html', true],
            ['/companies', 'html', true],
            ['/about', 'html', false],
            ['/methodology', 'html', false],
            ['/editorial-guidelines', 'html', false],
            ['/sourcing-verification', 'html', false],
            ['/corrections-policy', 'html', false],
            ['/privacy', 'html', false],
            ['/terms', 'html', false],
            ['/contact', 'html', false],
            ['/ads.txt', 'ads', false],
            ['/robots.txt', 'robots', false],
            ['/sitemap.xml', 'sitemap', false],
        ];

        foreach ($this->sampleEntityPaths() as $path) {
            $paths[] = [$path, 'html', true];
        }

        foreach ($paths as [$path, $kind, $expectAdsense]) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'AIOrbit-AdSense-Readiness-Audit/1.0'])
                    ->withOptions(['allow_redirects' => true])
                    ->get($baseUrl.$path);

                $status = $response->status();
                $body = (string) $response->body();
                $ok = $status >= 200 && $status < 400;

                if (! $ok) {
                    $this->add($checks, 'HTTP', $path, 'BLOCKER', 'HTTP '.$status);
                    continue;
                }

                if ($kind === 'html') {
                    $fatal = $this->htmlFatalSignature($body);
                    if ($fatal !== null) {
                        $this->add($checks, 'HTTP', $path, 'BLOCKER', 'Rendered error/template leak: '.$fatal);
                        continue;
                    }

                    $hasTitle = (bool) preg_match('/<title>\s*[^<]+\s*<\/title>/i', $body);
                    $hasCanonical = (bool) preg_match('/<link[^>]+rel=["\']canonical["\'][^>]*>/i', $body)
                        || (bool) preg_match('/<link[^>]+href=["\'][^"\']+["\'][^>]+rel=["\']canonical["\']/i', $body);
                    $adsenseLoaded = str_contains($body, 'pagead2.googlesyndication.com/pagead/js/adsbygoogle.js');

                    if (! $hasTitle || ! $hasCanonical) {
                        $this->add($checks, 'HTTP', $path, 'BLOCKER', 'Missing '.(!$hasTitle ? '<title>' : 'canonical link').'.');
                        continue;
                    }

                    if ($expectAdsense && ! $adsenseLoaded) {
                        $this->add($checks, 'HTTP', $path, 'WARN', 'Page is healthy, but AdSense bootstrap was not detected on this publisher-content route.');
                        continue;
                    }

                    if (! $expectAdsense && $adsenseLoaded) {
                        $this->add($checks, 'HTTP', $path, 'WARN', 'AdSense bootstrap is present on a trust/legal route; Phase 9 expects it to be excluded there.');
                        continue;
                    }

                    $this->add($checks, 'HTTP', $path, 'PASS', 'HTTP '.$status.'; title/canonical healthy'.($adsenseLoaded ? '; AdSense loader present.' : '; no AdSense loader.'));
                    continue;
                }

                if ($kind === 'ads') {
                    $publisherId = (string) config('adsense.publisher_id');
                    $valid = str_contains($body, 'google.com, '.$publisherId.', DIRECT, f08c47fec0942fa0');
                    $this->add($checks, 'HTTP', $path, $valid ? 'PASS' : 'BLOCKER', $valid ? 'Authorized publisher record reachable.' : 'Expected publisher record not found.');
                    continue;
                }

                if ($kind === 'robots') {
                    $valid = str_contains($body, 'Sitemap:') && ! preg_match('/^Disallow:\s*\/\s*$/mi', $body);
                    $this->add($checks, 'HTTP', $path, $valid ? 'PASS' : 'BLOCKER', $valid ? 'Crawler access and sitemap declaration detected.' : 'robots.txt is missing sitemap or blocks the root.');
                    continue;
                }

                if ($kind === 'sitemap') {
                    $valid = str_contains($body, '<sitemapindex') || str_contains($body, '<urlset');
                    $this->add($checks, 'HTTP', $path, $valid ? 'PASS' : 'BLOCKER', $valid ? 'XML sitemap reachable.' : 'Sitemap XML root not detected.');
                }
            } catch (\Throwable $e) {
                $this->add($checks, 'HTTP', $path, 'BLOCKER', $e->getMessage());
            }
        }

        // Query-string smoke: filters/tracking variants should be noindex and should
        // not load Auto ads. Canonical pagination is handled separately by Phase 7.
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'AIOrbit-AdSense-Readiness-Audit/1.0'])
                ->withOptions(['allow_redirects' => true])
                ->get($baseUrl.'/ai-tools?utm_source=phase9-audit');
            $body = (string) $response->body();
            $noindex = (bool) preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex/i', $body)
                || (bool) preg_match('/<meta[^>]+content=["\'][^"\']*noindex[^"\']*["\'][^>]+name=["\']robots["\']/i', $body);
            $adsenseLoaded = str_contains($body, 'pagead2.googlesyndication.com/pagead/js/adsbygoogle.js');
            $status = $response->status();
            $valid = $status >= 200 && $status < 400 && $noindex && ! $adsenseLoaded;
            $this->add($checks, 'HTTP', '/ai-tools?utm_source=phase9-audit', $valid ? 'PASS' : 'BLOCKER',
                $valid ? 'Tracking variant is noindex and Auto ads loader is excluded.' : 'Expected noindex + no AdSense loader on tracking/filter URL.');
        } catch (\Throwable $e) {
            $this->add($checks, 'HTTP', '/ai-tools?utm_source=phase9-audit', 'BLOCKER', $e->getMessage());
        }

        // Phase 3.1: sample a quality-gated tool/news detail when available.
        // These pages must stay reachable for users while sending noindex,follow
        // and excluding the AdSense bootstrap script.
        foreach ($this->sampleNoindexEntityPaths() as $path) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'AIOrbit-AdSense-Readiness-Audit/1.0'])
                    ->withOptions(['allow_redirects' => true])
                    ->get($baseUrl.$path);
                $body = (string) $response->body();
                $status = $response->status();
                $noindex = (bool) preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex/i', $body)
                    || (bool) preg_match('/<meta[^>]+content=["\'][^"\']*noindex[^"\']*["\'][^>]+name=["\']robots["\']/i', $body);
                $adsenseLoaded = str_contains($body, 'pagead2.googlesyndication.com/pagead/js/adsbygoogle.js');
                $valid = $status >= 200 && $status < 400 && $noindex && ! $adsenseLoaded;
                $this->add($checks, 'HTTP', $path.' (quality containment)', $valid ? 'PASS' : 'BLOCKER',
                    $valid ? 'Thin detail is reachable, noindex and Auto ads are suppressed.' : 'Expected reachable + noindex + no AdSense loader for this thin detail page.');
            } catch (\Throwable $e) {
                $this->add($checks, 'HTTP', $path.' (quality containment)', 'BLOCKER', $e->getMessage());
            }
        }

        return $this->summarize($checks);
    }

    private function appendContentChecks(Collection $checks): void
    {
        $thresholds = (array) config('adsense.readiness', []);

        $publishedTools = Tool::query()->where('status', 'published')->count();
        $minTools = (int) ($thresholds['min_published_tools'] ?? 20);
        $this->add($checks, 'Content', 'Published AI tools', $publishedTools >= $minTools ? 'PASS' : 'BLOCKER',
            $publishedTools.' published tools; internal floor '.$minTools.'.');

        $articles = Article::query()
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->with(['relatedToolTerms:id', 'relatedModelTerms:id', 'tagTerms:id'])
            ->get();
        $indexableArticles = $articles->filter(fn (Article $article) => $this->contentQuality->article($article)['indexable'])->count();
        $articlePercent = $articles->isEmpty() ? 0 : (int) round(($indexableArticles / $articles->count()) * 100);
        $minArticles = (int) ($thresholds['min_approved_articles'] ?? 15);
        $minArticlePercent = (int) ($thresholds['min_indexable_article_percent'] ?? 80);
        $articleStatus = $articles->count() >= $minArticles && $articlePercent >= $minArticlePercent ? 'PASS' : 'BLOCKER';
        $this->add($checks, 'Content', 'Approved/indexable articles', $articleStatus,
            $indexableArticles.'/'.$articles->count().' indexable ('.$articlePercent.'%); internal floors '.$minArticles.' approved / '.$minArticlePercent.'% indexable.');

        $models = AiModel::query()
            ->whereIn('status', ['active', 'preview'])
            ->with([
                'company:id,name', 'featureTerms:id,name', 'useCaseTerms:id,name',
                'pricingSources', 'evidenceSources',
                'benchmarkResults' => fn ($query) => $query
                    ->with('benchmark')
                    ->where('verified', true)
                    ->where('status', 'verified'),
            ])
            ->get();
        $indexableModels = $models->filter(fn (AiModel $model) => $this->contentQuality->model($model)['indexable'])->count();
        $modelPercent = $models->isEmpty() ? 0 : (int) round(($indexableModels / $models->count()) * 100);
        $minModels = (int) ($thresholds['min_active_models'] ?? 20);
        $minModelPercent = (int) ($thresholds['min_indexable_model_percent'] ?? 70);
        $modelStatus = $models->count() >= $minModels && $modelPercent >= $minModelPercent ? 'PASS' : 'BLOCKER';
        $this->add($checks, 'Content', 'Active/indexable models', $modelStatus,
            $indexableModels.'/'.$models->count().' indexable ('.$modelPercent.'%); internal floors '.$minModels.' active / '.$minModelPercent.'% indexable.');

        $news = NewsItem::query()->publiclyVisible()->get();
        $qualityNews = $news->filter(fn (NewsItem $item) => $this->contentQuality->news($item)['indexable'])->count();
        $minNews = (int) ($thresholds['min_quality_news'] ?? 1);
        $this->add($checks, 'Content', 'Quality-gated AI news', $qualityNews >= $minNews ? 'PASS' : 'WARN',
            $qualityNews.'/'.$news->count().' public AI-relevant stories are indexable.');

        $comparisons = Comparison::query()->where('status', 'published')->get();
        $validComparisons = $comparisons->filter(function (Comparison $comparison) {
            try {
                return $comparison->publicItems()->count() >= 2;
            } catch (\Throwable $e) {
                report($e);
                return false;
            }
        })->count();
        $minComparisons = (int) ($thresholds['min_valid_comparisons'] ?? 2);
        $this->add($checks, 'Content', 'Valid published comparisons', $validComparisons >= $minComparisons ? 'PASS' : 'BLOCKER',
            $validComparisons.' valid comparisons; '.max(0, $comparisons->count() - $validComparisons).' invalid/incomplete withheld.');

        $pricedTools = Tool::query()->where('status', 'published')->whereHas('pricingPlans')->count();
        $minPricedTools = (int) ($thresholds['min_priced_tools'] ?? 10);
        $this->add($checks, 'Content', 'Tools with pricing intelligence', $pricedTools >= $minPricedTools ? 'PASS' : 'WARN',
            $pricedTools.' published tools have pricing-plan data.');

        $verifiedBenchmarks = BenchmarkResult::query()->where('verified', true)->count();
        $minBenchmarks = (int) ($thresholds['min_verified_benchmarks'] ?? 3);
        $this->add($checks, 'Content', 'Verified benchmark results', $verifiedBenchmarks >= $minBenchmarks ? 'PASS' : 'WARN',
            $verifiedBenchmarks.' verified results.');

        $publishedToolRows = Tool::query()
            ->where('status', 'published')
            ->with([
                'company:id,name', 'category:id,name', 'subcategoryTerm:id,name',
                'featureTerms:id,name', 'useCaseTerms:id,name', 'platformTerms:id,name',
                'integrationTerms:id,name', 'sources', 'factEvidence', 'pricingPlans.sources',
                'technicalProfile',
                'benchmarkResults' => fn ($query) => $query
                    ->with('benchmark')
                    ->where('verified', true)
                    ->where('status', 'verified'),
            ])
            ->get();

        $toolAssessments = $publishedToolRows->mapWithKeys(function (Tool $tool) {
            return [$tool->id => $this->contentQuality->tool($tool)];
        });
        $indexableToolDetails = $toolAssessments->filter(fn (array $assessment) => (bool) ($assessment['indexable'] ?? false))->count();
        $containedToolDetails = max(0, $publishedToolRows->count() - $indexableToolDetails);
        $strictToolGate = ! (bool) config('seo_content_quality.tool.index_published_by_default', true);

        $this->add($checks, 'Low-value guardrail', 'Thin tool profiles are quality-gated',
            $strictToolGate ? 'PASS' : 'BLOCKER',
            $strictToolGate
                ? $indexableToolDetails.' indexable tool details; '.$containedToolDetails.' thin/incomplete profiles automatically use noindex,follow until structured decision support improves.'
                : 'Published tools are still forced indexable by default. Enable the structured tool quality gate before AdSense review.');

        $minIndexableToolDetails = (int) ($thresholds['min_indexable_tool_details_warn'] ?? 10);
        $toolCoverageStatus = $indexableToolDetails >= $minIndexableToolDetails ? 'PASS' : 'WARN';
        $this->add($checks, 'Content', 'Indexable tool detail coverage', $toolCoverageStatus,
            $indexableToolDetails.'/'.$publishedToolRows->count().' published tool profiles currently pass the structured decision-support gate; internal review target '.$minIndexableToolDetails.'+.');

        $qualityNewsPercent = $news->isEmpty() ? 0 : (int) round(($qualityNews / $news->count()) * 100);
        $thinNews = max(0, $news->count() - $qualityNews);
        $newsTemplatePath = resource_path('views/frontend/news/show.blade.php');
        $newsTemplate = is_file($newsTemplatePath) ? (string) file_get_contents($newsTemplatePath) : '';
        $layoutPath = resource_path('views/frontend/layouts/app.blade.php');
        $layoutSource = is_file($layoutPath) ? (string) file_get_contents($layoutPath) : '';
        $newsRobotsGated = str_contains($newsTemplate, "\$seoQuality['robots']")
            && str_contains($layoutSource, '$adsenseRobotsSafe');

        $this->add($checks, 'Low-value guardrail', 'Thin news briefs are contained',
            $newsRobotsGated ? 'PASS' : 'BLOCKER',
            $newsRobotsGated
                ? $qualityNews.' quality-passing news briefs remain indexable; '.$thinNews.' thinner briefs are noindex,follow and Auto ads are suppressed on noindex pages.'
                : 'News detail robots output and/or the global noindex AdSense suppression gate is missing.');

        $minQualityNewsPercent = (int) ($thresholds['min_quality_news_percent'] ?? 60);
        $qualityNewsStatus = $news->isEmpty()
            ? 'WARN'
            : ($qualityNewsPercent >= $minQualityNewsPercent ? 'PASS' : 'WARN');
        $this->add($checks, 'Content', 'News editorial enrichment coverage', $qualityNewsStatus,
            $qualityNews.'/'.$news->count().' public news briefs pass the summary + why-it-matters quality gate ('.$qualityNewsPercent.'%). Lower coverage is an enrichment backlog, not an indexing/ads leak, because thin briefs are contained.');

        $indexableCategories = Category::query()->seoProductIndexable()->get(['id', 'name', 'slug']);
        $categoryGuides = collect((array) config('editorial_guides.categories', []));
        $categoriesWithGuides = $indexableCategories
            ->filter(fn (Category $category) => $categoryGuides->has($category->slug))
            ->count();
        $categoryGuideStatus = $indexableCategories->isEmpty() || $categoriesWithGuides === $indexableCategories->count()
            ? 'PASS'
            : 'BLOCKER';
        $this->add($checks, 'Low-value guardrail', 'Indexable category editorial guides', $categoryGuideStatus,
            $categoriesWithGuides.'/'.$indexableCategories->count().' indexable product categories have dedicated editorial guidance.');

        $activeBenchmarks = Benchmark::query()
            ->where('is_active', true)
            ->withCount(['results as verified_results_count' => fn ($query) => $query
                ->where('verified', true)
                ->where('status', 'verified')])
            ->get(['id', 'name', 'slug', 'description', 'official_url', 'methodology_url', 'is_active']);
        $contextRichBenchmarks = $activeBenchmarks->filter(function (Benchmark $benchmark) use ($thresholds) {
            $description = trim(strip_tags((string) $benchmark->description));
            $description = preg_replace('/\s+/u', ' ', $description) ?? $description;
            $hasSource = filled($benchmark->official_url) || filled($benchmark->methodology_url);

            return mb_strlen(trim($description)) >= (int) ($thresholds['min_benchmark_description_chars'] ?? 60)
                && $hasSource
                && (int) ($benchmark->verified_results_count ?? 0) >= 1;
        })->count();
        $benchmarkContextPercent = $activeBenchmarks->isEmpty()
            ? 0
            : (int) round(($contextRichBenchmarks / $activeBenchmarks->count()) * 100);
        $minBenchmarkContextPercent = (int) ($thresholds['min_benchmark_context_percent'] ?? 50);
        $benchmarkContextStatus = $activeBenchmarks->isEmpty()
            ? 'WARN'
            : ($benchmarkContextPercent >= $minBenchmarkContextPercent ? 'PASS' : 'WARN');
        $this->add($checks, 'Low-value guardrail', 'Benchmarks with methodology/source context', $benchmarkContextStatus,
            $contextRichBenchmarks.'/'.$activeBenchmarks->count().' active benchmarks have description + source/methodology + verified result context ('.$benchmarkContextPercent.'%).');

        $publicReviews = Review::query()
            ->publicContent()
            ->where(function ($query) {
                $query->whereHas('tool', fn ($tool) => $tool->where('status', 'published'))
                    ->orWhereHas('model', fn ($model) => $model->whereIn('status', ['active', 'preview']));
            })
            ->count();
        $this->add($checks, 'Content', 'Written review inventory', 'PASS',
            $publicReviews > 0
                ? $publicReviews.' public written/editorial reviews.'
                : '0 written reviews; Phase 9 keeps the empty reviews hub noindex and out of the static sitemap until content exists.');
    }

    private function appendPublisherTemplateChecks(Collection $checks): void
    {
        $requirements = [
            'resources/views/frontend/news/show.blade.php' => [
                'What is confirmed, and what is not',
                'SOURCE-GROUNDED CONTEXT',
                "\$seoQuality['robots']",
            ],
            'resources/views/frontend/tools/show.blade.php' => [
                "frontend.tools.partials.editorial-decision-brief",
                'Features & use cases',
                "\$seoQuality['robots']",
            ],
            'resources/views/frontend/categories/show.blade.php' => [
                'How to choose',
                'category-editorial-guide',
            ],
            'resources/views/frontend/categories/subcategory.blade.php' => [
                'How to evaluate',
                'category-editorial-guide',
            ],
            'resources/views/frontend/taxonomy/show.blade.php' => [
                'RESEARCH GUIDE',
                'category-editorial-guide',
            ],
            'resources/views/frontend/benchmarks/show.blade.php' => [
                'How to interpret this leaderboard',
                'Limitations',
            ],
            'resources/views/frontend/models/show.blade.php' => [
                'AI Orbit profile evidence coverage',
                'Verified benchmark profile',
            ],
            'resources/views/frontend/pricing/show.blade.php' => [
                '$meaningfulHistory',
                'Published pricing history',
            ],
            'resources/views/frontend/articles/show.blade.php' => [
                '$displayReviewer',
                'AI Orbit editorial standard',
            ],
            'resources/views/frontend/pages/about.blade.php' => [
                'Editorial responsibility',
                'AI Orbit is accountable for what it publishes.',
            ],
        ];

        $missingMarkers = collect();
        foreach ($requirements as $relative => $markers) {
            $path = base_path($relative);
            if (! is_file($path)) {
                $missingMarkers->push($relative.' is missing');
                continue;
            }

            $contents = (string) file_get_contents($path);
            foreach ($markers as $marker) {
                if (! str_contains($contents, $marker)) {
                    $missingMarkers->push($relative.' missing "'.$marker.'"');
                }
            }
        }

        $this->add($checks, 'Low-value guardrail', 'High-risk page templates contain decision/context sections',
            $missingMarkers->isEmpty() ? 'PASS' : 'BLOCKER',
            $missingMarkers->isEmpty()
                ? count($requirements).' high-risk publisher templates contain their expected editorial/context markers.'
                : $missingMarkers->take(6)->join(' | '));

        $publicTemplateFiles = [
            'resources/views/frontend/news/show.blade.php',
            'resources/views/frontend/tools/show.blade.php',
            'resources/views/frontend/categories/index.blade.php',
            'resources/views/frontend/categories/show.blade.php',
            'resources/views/frontend/categories/subcategory.blade.php',
            'resources/views/frontend/taxonomy/show.blade.php',
            'resources/views/frontend/benchmarks/show.blade.php',
            'resources/views/frontend/models/index.blade.php',
            'resources/views/frontend/models/show.blade.php',
            'resources/views/frontend/pricing/index.blade.php',
            'resources/views/frontend/pricing/show.blade.php',
            'resources/views/frontend/companies/index.blade.php',
            'resources/views/frontend/companies/show.blade.php',
            'resources/views/frontend/articles/show.blade.php',
        ];

        $unfinishedPatterns = [
            'Processing: Pending',
            'Evidence records not imported yet',
            '0 source records',
            'AI Hub users say',
            '0 popularity',
            '— → —',
            'Not rated',
        ];

        $unfinishedHits = collect();
        foreach ($publicTemplateFiles as $relative) {
            $path = base_path($relative);
            if (! is_file($path)) {
                continue;
            }

            $contents = (string) file_get_contents($path);
            foreach ($unfinishedPatterns as $pattern) {
                if (str_contains($contents, $pattern)) {
                    $unfinishedHits->push($relative.' contains "'.$pattern.'"');
                }
            }
        }

        $this->add($checks, 'Low-value guardrail', 'Unfinished/debug-style public copy',
            $unfinishedHits->isEmpty() ? 'PASS' : 'BLOCKER',
            $unfinishedHits->isEmpty()
                ? 'No known unfinished/debug-style low-value signatures remain in publisher templates.'
                : $unfinishedHits->take(6)->join(' | '));

        $bladeSanityHits = collect();
        foreach ($publicTemplateFiles as $relative) {
            $path = base_path($relative);
            if (! is_file($path)) {
                continue;
            }

            $contents = (string) file_get_contents($path);

            if (preg_match('/(?<=[A-Za-z0-9_])@(if|elseif|else|foreach|for|forelse|while|php)\b/', $contents, $match, PREG_OFFSET_CAPTURE)) {
                $offset = (int) ($match[0][1] ?? 0);
                $context = trim(substr($contents, max(0, $offset - 28), 80));
                $bladeSanityHits->push($relative.' has a glued Blade directive near "'.Str::limit($context, 70, '').'"');
            }

            $pairs = [
                ['if', 'endif'],
                ['foreach', 'endforeach'],
                ['for', 'endfor'],
                ['forelse', 'endforelse'],
                ['while', 'endwhile'],
                ['switch', 'endswitch'],
            ];

            foreach ($pairs as [$open, $close]) {
                preg_match_all('/@'.preg_quote($open, '/').'\b/', $contents, $openMatches);
                preg_match_all('/@'.preg_quote($close, '/').'\b/', $contents, $closeMatches);
                $openCount = count($openMatches[0] ?? []);
                $closeCount = count($closeMatches[0] ?? []);
                if ($openCount !== $closeCount) {
                    $bladeSanityHits->push($relative.' has @'.$open.'='.$openCount.' / @'.$close.'='.$closeCount);
                }
            }
        }

        $this->add($checks, 'Quality', 'Publisher Blade directive sanity',
            $bladeSanityHits->isEmpty() ? 'PASS' : 'BLOCKER',
            $bladeSanityHits->isEmpty()
                ? 'High-risk publisher templates have balanced control directives and no glued directive signatures.'
                : $bladeSanityHits->take(6)->join(' | '));

        $categoriesPath = Route::has('categories.index')
            ? (string) parse_url(route('categories.index'), PHP_URL_PATH)
            : '';
        $categoryPathClean = $categoriesPath === '/categories'
            || ($categoriesPath !== '' && ! str_contains($categoriesPath, '/public/'));
        $this->add($checks, 'Crawl', 'Categories canonical route path',
            $categoryPathClean ? 'PASS' : 'WARN',
            $categoryPathClean
                ? ($categoriesPath ?: 'Category route unavailable during path check.')
                : 'Category directory resolves through a /public/ URL. Prefer the clean /categories canonical route and redirect legacy /public/categories requests.');
    }

    private function scanFrontendViewsFor(array $patterns): Collection
    {
        $root = resource_path('views/frontend');
        if (! is_dir($root)) {
            return collect(['frontend views directory missing']);
        }

        $hits = collect();
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));
        foreach ($iterator as $file) {
            if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $contents = (string) file_get_contents($file->getPathname());
            foreach ($patterns as $pattern) {
                if (str_contains($contents, $pattern)) {
                    $relative = Str::after($file->getPathname(), base_path().DIRECTORY_SEPARATOR);
                    $hits->push($relative.' contains "'.$pattern.'"');
                }
            }
        }

        return $hits->values();
    }

    private function sampleNoindexEntityPaths(): array
    {
        $paths = collect();

        try {
            $tools = Tool::query()
                ->where('status', 'published')
                ->with([
                    'company:id,name', 'category:id,name', 'subcategoryTerm:id,name',
                    'featureTerms:id,name', 'useCaseTerms:id,name', 'platformTerms:id,name',
                    'integrationTerms:id,name', 'sources', 'factEvidence', 'pricingPlans.sources',
                    'technicalProfile',
                    'benchmarkResults' => fn ($query) => $query->with('benchmark')->where('verified', true)->where('status', 'verified'),
                ])
                ->orderBy('id')
                ->get();
            if ($tool = $tools->first(fn (Tool $row) => ! $this->contentQuality->tool($row)['indexable'])) {
                $paths->push((string) parse_url(route('tools.show', $tool), PHP_URL_PATH));
            }

            $news = NewsItem::query()->publiclyVisible()->orderBy('id')->get();
            if ($item = $news->first(fn (NewsItem $row) => ! $this->contentQuality->news($row)['indexable'])) {
                $paths->push((string) parse_url(route('news.show', $item), PHP_URL_PATH));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $paths->filter()->unique()->values()->all();
    }

    private function sampleEntityPaths(): array
    {
        $paths = collect();

        try {
            $tools = Tool::query()
                ->where('status', 'published')
                ->with([
                    'company:id,name', 'category:id,name', 'subcategoryTerm:id,name',
                    'featureTerms:id,name', 'useCaseTerms:id,name', 'platformTerms:id,name',
                    'integrationTerms:id,name', 'sources', 'factEvidence', 'pricingPlans.sources',
                    'technicalProfile',
                    'benchmarkResults' => fn ($query) => $query->with('benchmark')->where('verified', true)->where('status', 'verified'),
                ])
                ->orderBy('id')
                ->get();
            if ($tool = $tools->first(fn (Tool $row) => $this->contentQuality->tool($row)['indexable'])) {
                $paths->push((string) parse_url(route('tools.show', $tool), PHP_URL_PATH));
            }

            $models = AiModel::query()
                ->whereIn('status', ['active', 'preview'])
                ->with(['company:id,name', 'featureTerms:id,name', 'useCaseTerms:id,name', 'pricingSources', 'evidenceSources',
                    'benchmarkResults' => fn ($query) => $query->with('benchmark')->where('verified', true)->where('status', 'verified')])
                ->orderBy('id')->get();
            if ($model = $models->first(fn (AiModel $row) => $this->contentQuality->model($row)['indexable'])) {
                $paths->push((string) parse_url(route('models.show', $model), PHP_URL_PATH));
            }

            $articles = Article::query()->where('status', 'published')->where('approval_status', 'approved')
                ->with(['relatedToolTerms:id', 'relatedModelTerms:id', 'tagTerms:id'])->orderBy('id')->get();
            if ($article = $articles->first(fn (Article $row) => $this->contentQuality->article($row)['indexable'])) {
                $paths->push((string) parse_url(route('articles.show', $article), PHP_URL_PATH));
            }

            $news = NewsItem::query()->publiclyVisible()->orderBy('id')->get();
            if ($item = $news->first(fn (NewsItem $row) => $this->contentQuality->news($row)['indexable'])) {
                $paths->push((string) parse_url(route('news.show', $item), PHP_URL_PATH));
            }

            $comparison = Comparison::query()->where('status', 'published')->orderBy('id')->get()->first(function (Comparison $row) {
                try {
                    return $row->publicItems()->count() >= 2;
                } catch (\Throwable $e) {
                    return false;
                }
            });
            if ($comparison) {
                $paths->push((string) parse_url(route('comparisons.show', $comparison), PHP_URL_PATH));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $paths->filter()->unique()->values()->all();
    }

    private function htmlFatalSignature(string $body): ?string
    {
        $patterns = [
            'Internal Server Error',
            'ParseError',
            'SQLSTATE[',
            'Not yet verified@else',
            '@elseUnknown / not yet verified',
            '% match@if',
        ];
        foreach ($patterns as $pattern) {
            if (str_contains($body, $pattern)) {
                return $pattern;
            }
        }

        if (preg_match('/(?:@endif\b|@foreach\b|@else\b)/', $body, $match)) {
            return $match[0];
        }

        return null;
    }

    private function add(Collection $checks, string $area, string $check, string $status, string $detail): void
    {
        $checks->push([
            'area' => $area,
            'check' => $check,
            'status' => $status,
            'detail' => $detail,
        ]);
    }

    private function summarize(Collection $checks): array
    {
        return [
            'checks' => $checks->values(),
            'passes' => $checks->where('status', 'PASS')->count(),
            'warnings' => $checks->where('status', 'WARN')->count(),
            'blockers' => $checks->where('status', 'BLOCKER')->count(),
            'ready' => $checks->where('status', 'BLOCKER')->isEmpty(),
        ];
    }
}
