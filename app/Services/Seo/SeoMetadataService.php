<?php

namespace App\Services\Seo;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\Benchmark;
use App\Models\Category;
use App\Models\Company;
use App\Models\Comparison;
use App\Models\Feature;
use App\Models\NewsItem;
use App\Models\Review;
use App\Models\SeoTarget;
use App\Models\Subcategory;
use App\Models\Tool;
use App\Models\UseCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeoMetadataService
{
    private array $targetCache = [];

    /**
     * Resolve the Phase 1 intent owner for the current public route and turn it
     * into live title/description metadata. Routes without a persisted target
     * keep their existing metadata untouched.
     */
    public function forRequest(Request $request, ?string $fallbackTitle = null, ?string $fallbackDescription = null): array
    {
        $route = $request->route();
        $routeName = $route?->getName();

        if (! $routeName || ! Schema::hasTable('seo_targets')) {
            return $this->fallback($fallbackTitle, $fallbackDescription);
        }

        $staticKey = 'static:'.$routeName;
        $staticTarget = $this->targetCache[$staticKey] ??= SeoTarget::query()
            ->where('target_key', $staticKey)
            ->first();

        if ($staticTarget) {
            return $this->forTarget($staticTarget, $fallbackTitle, $fallbackDescription, [
                'page' => max(1, (int) $request->query('page', 1)),
            ]);
        }

        $preferredParameter = match ($routeName) {
            'tools.show', 'pricing.show' => 'tool',
            'models.show' => 'model',
            'companies.show' => 'company',
            'comparisons.show' => 'comparison',
            'articles.show' => 'article',
            'news.show' => 'news',
            'reviews.show' => 'review',
            'benchmarks.show' => 'benchmark',
            'categories.show' => 'category',
            'categories.subcategories.show' => 'subcategory',
            'features.show' => 'feature',
            'use-cases.show' => 'useCase',
            'topics.show' => 'category',
            default => null,
        };

        $parameter = $preferredParameter ? $route->parameter($preferredParameter) : null;
        if ($parameter instanceof Model) {
            $targetKey = $routeName.':'.$parameter->getKey();
            return $this->forKey($targetKey, $fallbackTitle, $fallbackDescription, [
                'page' => max(1, (int) $request->query('page', 1)),
            ]);
        }

        return $this->fallback($fallbackTitle, $fallbackDescription);
    }

    /**
     * Resolve metadata by Phase 1 target key. This is used by entity SEO
     * services so page schemas and head metadata can share the same wording.
     */
    public function forKey(
        string $targetKey,
        ?string $fallbackTitle = null,
        ?string $fallbackDescription = null,
        array $context = [],
    ): array {
        if (! Schema::hasTable('seo_targets')) {
            return $this->fallback($fallbackTitle, $fallbackDescription, $context);
        }

        $target = $this->targetCache[$targetKey] ??= SeoTarget::query()
            ->where('target_key', $targetKey)
            ->first();

        return $target
            ? $this->forTarget($target, $fallbackTitle, $fallbackDescription, $context)
            : $this->fallback($fallbackTitle, $fallbackDescription, $context);
    }

    public function forTarget(
        SeoTarget $target,
        ?string $fallbackTitle = null,
        ?string $fallbackDescription = null,
        array $context = [],
    ): array {
        $target->loadMissing('targetable');

        $primary = $this->clean($target->primary_keyword);
        $secondary = collect($target->secondary_keywords ?? [])
            ->map(fn ($keyword) => $this->clean((string) $keyword))
            ->filter()
            ->values()
            ->all();

        $entity = $target->targetable;
        $titleCore = $this->titleCore($target->page_type, $primary, $entity, $fallbackTitle);
        $description = $this->descriptionFor(
            $target->page_type,
            $primary,
            $secondary,
            $entity,
            $fallbackDescription,
        );

        $page = max(1, (int) ($context['page'] ?? 1));
        if ($page > 1 && Str::endsWith($target->page_type, '_directory')) {
            $titleCore = $this->stripBrand($titleCore).' — Page '.$page;
        }

        $includeBrand = (bool) ($context['include_brand'] ?? true);
        $title = $includeBrand
            ? $this->brandTitle($titleCore)
            : $this->fitTitle($this->stripBrand($titleCore), 64);

        return [
            'title' => $title,
            'description' => $this->fitDescription($description),
            'primary_keyword' => $primary,
            'secondary_keywords' => $secondary,
            'search_intent' => $target->search_intent,
            'topic_cluster' => $target->topic_cluster,
            'target_key' => $target->target_key,
            'source' => $target->source,
            'is_locked' => (bool) $target->is_locked,
        ];
    }

    public function normalized(string $value): string
    {
        $value = Str::lower($this->clean($value));
        $value = str_replace('+', ' plus ', $value);
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * Planning keywords are concepts, not exact-match stuffing requirements.
     * This check tolerates harmless presentation cleanup such as "OpenAI AI
     * company" becoming the natural "OpenAI Company" in the title.
     */
    public function titleRepresentsPrimary(string $title, string $primaryKeyword): bool
    {
        $titleCore = $this->normalized($this->stripBrand($title));
        $primary = $this->normalized($primaryKeyword);

        if ($primary === '' || str_contains($titleCore, $primary)) {
            return true;
        }

        $naturalPrimary = preg_replace('/\bai ai\b/u', 'ai', $primary) ?? $primary;
        $tokens = preg_split('/\s+/u', $naturalPrimary, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (count($tokens) >= 3 && str_ends_with($tokens[0], 'ai') && ($tokens[1] ?? null) === 'ai' && ($tokens[2] ?? null) === 'company') {
            unset($tokens[1]);
            $naturalPrimary = implode(' ', array_values($tokens));
        }

        if ($naturalPrimary !== '' && str_contains($titleCore, $naturalPrimary)) {
            return true;
        }

        // Long editorial/benchmark names may need a shorter SERP title. Treat a
        // clean, substantial prefix as the same intent instead of requiring the
        // entire long headline to be repeated verbatim.
        if (mb_strlen($naturalPrimary) > 52 && mb_strlen($titleCore) >= 32) {
            $primaryTokens = preg_split('/\s+/u', $naturalPrimary, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $titleTokens = preg_split('/\s+/u', $titleCore, -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $titleWithoutBrand = implode(' ', array_values(array_filter(
                $titleTokens,
                fn ($token) => ! in_array($token, ['ai', 'orbit'], true)
            )));

            if ($titleWithoutBrand !== '' && str_starts_with($naturalPrimary, $titleWithoutBrand)) {
                return true;
            }

            // Require several leading words in the same order so this cannot
            // turn a merely related title into a false positive.
            $prefixLength = min(count($titleTokens), count($primaryTokens));
            if ($prefixLength >= 5 && array_slice($primaryTokens, 0, $prefixLength) === array_slice($titleTokens, 0, $prefixLength)) {
                return true;
            }
        }

        return false;
    }

    private function titleCore(string $pageType, string $primary, ?Model $entity, ?string $fallbackTitle): string
    {
        $rawPrimary = $this->clean($primary);
        $primary = in_array($pageType, ['article_detail', 'news_detail', 'review_detail'], true)
            ? $rawPrimary
            : $this->displayKeyword($rawPrimary);

        return match ($pageType) {
            'home' => $primary.': Discover, Compare & Track AI',
            'tools_directory' => $primary.': Discover and Compare AI Tools',
            'models_directory' => $primary.': Compare Leading AI Models',
            'news_directory' => $primary.': Latest Model, Product & Research Updates',
            'comparisons_directory' => $primary.': Compare AI Tools and Models',
            'companies_directory' => $primary.': Explore Labs, Models and Tools',
            'articles_directory' => $primary.': Articles, Research & How-Tos',
            'reviews_directory' => $primary,
            'pricing_directory' => $primary.': Compare Tool Plans and API Costs',
            'categories_directory' => $primary.': Browse Tools by Category',
            'features_directory' => $primary.': Browse Tools by Capability',
            'use_cases_directory' => $primary.': Find Tools for Every Workflow',
            'topics_directory' => $primary.': Guides, Research and Analysis',
            'benchmarks_directory' => $primary.': Scores and Leaderboards',
            'trending_directory' => $primary.': What Is Popular Now',

            'tool_detail' => $primary.': Features, Use Cases & Alternatives',
            'tool_pricing' => $primary.': Plans, Costs & Billing',
            'model_detail' => $this->modelTitle($primary, $entity),
            'company_detail' => $this->companyTitle($primary, $entity),
            'comparison_detail' => $primary.': Features, Pricing & Key Differences',
            'benchmark_detail' => $primary.': Scores, Results & Leaderboard',
            'category_detail', 'subcategory_detail' => $primary.': Compare Top Products',
            'feature_detail', 'use_case_detail' => $primary.': Compare Tools & Models',
            'topic_detail' => $primary.': Guides & Updates',
            'article_detail', 'news_detail', 'review_detail' => $this->preferredOrPrimary($fallbackTitle, $primary),

            default => $this->preferredOrPrimary($fallbackTitle, $primary),
        };
    }

    private function modelTitle(string $primary, ?Model $entity): string
    {
        if (! $entity instanceof AiModel) {
            return $primary.': Specs, Pricing & Benchmarks';
        }

        $hasPricing = $entity->input_price_per_million !== null
            || $entity->output_price_per_million !== null
            || filled($entity->pricing_type)
            || filled($entity->pricing_basis)
            || filled($entity->pricing_summary);

        $hasBenchmarks = $entity->benchmark_score !== null
            || $entity->benchmarkResults()
                ->where('verified', true)
                ->where('status', 'verified')
                ->exists();

        $signals = ['Specs'];
        if ($hasPricing) {
            $signals[] = 'Pricing';
        }
        $signals[] = $hasBenchmarks ? 'Benchmarks' : 'Capabilities';

        $lastSignal = array_pop($signals);

        return $primary.': '.($signals ? implode(', ', $signals).' & ' : '').$lastSignal;
    }

    private function companyTitle(string $primary, ?Model $entity): string
    {
        if ($entity instanceof Company) {
            $generatedOwner = $this->normalized($entity->name.' AI company');
            if ($this->normalized($primary) === $generatedOwner && Str::endsWith(Str::lower($entity->name), 'ai')) {
                $primary = $entity->name.' Company';
            }
        }

        $primary = preg_replace('/\bAI AI Company\b/i', 'AI Company', $primary) ?? $primary;

        if (! $entity instanceof Company) {
            return $primary.': Models, Tools & News';
        }

        $signals = [];
        if ($entity->models()->whereIn('status', ['active', 'preview'])->exists()) {
            $signals[] = 'Models';
        }
        if ($entity->tools()->where('status', 'published')->exists()) {
            $signals[] = 'Tools';
        }
        if ($entity->newsItems()->where('status', 'published')->whereNull('duplicate_of_id')->exists()) {
            $signals[] = 'News';
        }
        if (! $signals) {
            $signals[] = 'Profile';
        }

        $lastSignal = array_pop($signals);

        return $primary.': '.($signals ? implode(', ', $signals).' & ' : '').$lastSignal;
    }

    private function descriptionFor(
        string $pageType,
        string $primary,
        array $secondary,
        ?Model $entity,
        ?string $fallbackDescription,
    ): string {
        $fallback = $this->clean($fallbackDescription);

        return match ($pageType) {
            'home' => 'Discover AI tools and models on AI Orbit. Compare products, explore pricing and benchmarks, follow AI news, and find the right AI for your workflow.',
            'tools_directory' => 'Explore the AI tools directory by category, company, capability, platform and rating. Compare leading AI products and find tools for your workflow.',
            'models_directory' => 'Explore AI models by provider, capabilities, context window, pricing and verified benchmark performance. Compare leading models on AI Orbit.',
            'news_directory' => 'Follow AI news covering model releases, product updates, research, funding, pricing changes and security developments with source context.',
            'comparisons_directory' => 'Browse AI comparisons for tools and models. Compare pricing, capabilities, benchmarks, ratings and practical product differences side by side.',
            'companies_directory' => 'Explore AI companies, research labs and product providers through their public tools, active models, company profiles and latest AI activity.',
            'articles_directory' => 'Read AI guides and analysis covering tools, models, benchmarks, pricing, workflows, research and practical product decisions.',
            'reviews_directory' => 'Browse AI tool and model reviews with ratings, verdicts and product context to help compare AI products before choosing one.',
            'pricing_directory' => 'Compare AI pricing across tool plans, free and paid options, API costs, verification dates and published price changes on AI Orbit.',
            'categories_directory' => 'Browse AI tool categories with structured subcategories, capabilities and use cases, then compare relevant tools and models.',
            'features_directory' => 'Browse AI tool features and capabilities, then find tools and models that support the functions you need.',
            'use_cases_directory' => 'Explore AI use cases and find tools or models suited to common workflows, tasks and practical business needs.',
            'topics_directory' => 'Explore AI topics with guides, research, model releases, benchmark explainers, pricing analysis and industry intelligence.',
            'benchmarks_directory' => 'Compare verified AI model and tool benchmark scores across reasoning, coding, product quality and other evaluation categories.',
            'trending_directory' => 'Explore trending AI tools and models, important AI news, active companies and popular comparisons across AI Orbit.',

            'tool_detail' => $this->toolDescription($primary, $entity),
            'tool_pricing' => $this->pricingDescription($primary, $entity),
            'model_detail' => $this->modelDescription($primary, $entity),
            'company_detail' => $this->companyDescription($primary, $entity),
            'comparison_detail' => $this->comparisonDescription($primary, $entity, $fallback),
            'benchmark_detail' => $this->benchmarkDescription($primary, $entity, $fallback),
            'category_detail' => $this->taxonomyDescription($primary, $entity, 'category', $fallback),
            'subcategory_detail' => $this->taxonomyDescription($primary, $entity, 'subcategory', $fallback),
            'feature_detail' => $this->taxonomyDescription($primary, $entity, 'feature', $fallback),
            'use_case_detail' => $this->taxonomyDescription($primary, $entity, 'use case', $fallback),
            'topic_detail' => $this->topicDescription($primary, $entity, $fallback),
            'article_detail' => $this->articleDescription($entity, $fallback),
            'news_detail' => $this->newsDescription($entity, $fallback),
            'review_detail' => $this->reviewDescription($entity, $fallback),

            default => $fallback !== '' ? $fallback : $this->genericDescription($primary, $secondary),
        };
    }

    private function toolDescription(string $primary, ?Model $entity): string
    {
        if (! $entity instanceof Tool) {
            return 'Read the '.$primary.' on AI Orbit. Explore features, use cases, provider details, supported platforms, alternatives and related AI models.';
        }

        $entity->loadMissing(['company', 'category', 'featureTerms', 'useCaseTerms', 'models']);
        $provider = $entity->company?->name;
        $category = $entity->category?->name;

        $parts = ['Read the '.$primary.' on AI Orbit'];
        if ($provider) {
            $parts[] = 'See '.$provider.' provider context';
        }
        if ($category) {
            $parts[] = 'Explore its '.$category.' category fit';
        }
        if ($entity->featureTerms->isNotEmpty()) {
            $parts[] = 'verified or structured features';
        }
        if ($entity->useCaseTerms->isNotEmpty()) {
            $parts[] = 'use cases';
        }
        if ($entity->models->whereIn('status', ['active', 'preview'])->isNotEmpty()) {
            $parts[] = 'related AI models';
        }
        $parts[] = 'alternatives';

        return implode('. ', array_slice($parts, 0, 6)).'.';
    }

    private function pricingDescription(string $primary, ?Model $entity): string
    {
        $name = $entity instanceof Tool ? $entity->name : preg_replace('/\s+pricing$/i', '', $primary);
        $planCount = $entity instanceof Tool ? $entity->pricingPlans()->count() : 0;

        return 'Compare '.$name.' pricing'.($planCount > 0 ? ' across '.$planCount.' listed plan'.($planCount === 1 ? '' : 's') : '').
            ', costs, billing units, limits, verification dates and official pricing evidence on AI Orbit.';
    }

    private function modelDescription(string $primary, ?Model $entity): string
    {
        if (! $entity instanceof AiModel) {
            return 'Explore '.$primary.' with provider details, specifications, capabilities, context window, pricing and verified benchmark results on AI Orbit.';
        }

        $entity->loadMissing(['company', 'featureTerms']);
        $provider = $entity->company?->name;
        $signals = [];
        if ($entity->context_window) {
            $signals[] = 'context window '.$entity->context_window;
        }
        if ($entity->featureTerms->isNotEmpty() || collect($entity->capabilities ?? [])->isNotEmpty()) {
            $signals[] = 'capabilities';
        }
        if ($entity->input_price_per_million !== null || $entity->output_price_per_million !== null || filled($entity->pricing_type)) {
            $signals[] = 'pricing';
        }
        if ($entity->benchmark_score !== null || $entity->benchmarkResults()->where('verified', true)->where('status', 'verified')->exists()) {
            $signals[] = 'verified benchmarks';
        }

        return 'Explore '.$primary.($provider ? ' by '.$provider : '').' on AI Orbit with '.
            (collect($signals)->take(4)->join(', ', ' and ') ?: 'specifications, capabilities and provider context').'.';
    }

    private function companyDescription(string $primary, ?Model $entity): string
    {
        if (! $entity instanceof Company) {
            return 'Explore the '.$primary.' profile on AI Orbit with linked AI models, tools, company information and latest public updates.';
        }

        $models = $entity->models()->whereIn('status', ['active', 'preview'])->count();
        $tools = $entity->tools()->where('status', 'published')->count();
        $news = $entity->newsItems()->where('status', 'published')->whereNull('duplicate_of_id')->count();

        $signals = collect([
            $models ? $models.' active AI model'.($models === 1 ? '' : 's') : null,
            $tools ? $tools.' published tool'.($tools === 1 ? '' : 's') : null,
            $news ? $news.' public news update'.($news === 1 ? '' : 's') : null,
        ])->filter()->values();

        return 'Research '.$entity->name.' on AI Orbit'.($signals->isNotEmpty() ? ' with '.$signals->join(', ', ' and ') : '').
            '. Explore its AI products, provider profile and related intelligence.';
    }

    private function comparisonDescription(string $primary, ?Model $entity, string $fallback): string
    {
        if ($entity instanceof Comparison) {
            $summary = $this->clean($entity->summary);
            if ($summary !== '') {
                return $summary;
            }
        }

        return $fallback !== ''
            ? $fallback
            : 'Compare '.$primary.' across pricing, capabilities, benchmarks and practical product differences on AI Orbit.';
    }

    private function benchmarkDescription(string $primary, ?Model $entity, string $fallback): string
    {
        if ($entity instanceof Benchmark) {
            $count = $entity->results()->where('verified', true)->where('status', 'verified')->count();
            return 'View '.$primary.' scores'.($count ? ' from '.$count.' verified result'.($count === 1 ? '' : 's') : '').
                ', methodology context, source links and the latest leaderboard on AI Orbit.';
        }

        return $fallback !== '' ? $fallback : 'View '.$primary.' scores, verified results, methodology context and leaderboard data on AI Orbit.';
    }

    private function taxonomyDescription(string $primary, ?Model $entity, string $kind, string $fallback): string
    {
        $base = $fallback;
        if ($entity instanceof Category || $entity instanceof Subcategory || $entity instanceof Feature || $entity instanceof UseCase) {
            $base = $this->clean($entity->meta_description ?? null)
                ?: $this->clean($entity->short_description ?? null)
                ?: $this->clean($entity->description ?? null);
        }

        if ($base !== '') {
            return $base;
        }

        return 'Explore '.$primary.' on AI Orbit. Compare relevant tools and models, browse related '.$kind.' intelligence and find options for your workflow.';
    }

    private function topicDescription(string $primary, ?Model $entity, string $fallback): string
    {
        if ($entity instanceof Category) {
            $base = $this->clean($entity->meta_description)
                ?: $this->clean($entity->short_description)
                ?: $this->clean($entity->description);
            if ($base !== '') {
                return $base;
            }
        }

        return $fallback !== '' ? $fallback : 'Explore '.$primary.' with recent AI Orbit articles, guides, research and related updates.';
    }

    private function articleDescription(?Model $entity, string $fallback): string
    {
        if ($entity instanceof Article) {
            return $this->clean($entity->meta_description)
                ?: $this->clean($entity->summary)
                ?: $this->clean($entity->content)
                ?: $fallback;
        }

        return $fallback;
    }

    private function newsDescription(?Model $entity, string $fallback): string
    {
        if ($entity instanceof NewsItem) {
            return $this->clean($entity->meta_description ?? null)
                ?: $this->clean($entity->ai_summary)
                ?: $this->clean($entity->summary)
                ?: $this->clean($entity->headline)
                ?: $fallback;
        }

        return $fallback;
    }

    private function reviewDescription(?Model $entity, string $fallback): string
    {
        if ($entity instanceof Review) {
            $item = $entity->reviewedItem();
            $body = $this->clean($entity->body ?? null) ?: $this->clean($entity->verdict ?? null);
            if ($body !== '') {
                return $body;
            }
            if ($item) {
                return 'Read this '.$item->name.' review on AI Orbit with rating, verdict and product context.';
            }
        }

        return $fallback;
    }

    private function genericDescription(string $primary, array $secondary): string
    {
        $related = collect($secondary)->take(2)->join(' and ');
        return 'Explore '.$primary.' on AI Orbit'.($related ? ', including '.$related : '').'.';
    }

    private function preferredOrPrimary(?string $fallbackTitle, string $primary): string
    {
        $fallback = $this->stripBrand($this->clean($fallbackTitle));

        if ($fallback !== '' && $this->titleRepresentsPrimary($fallback, $primary)) {
            return $fallback;
        }

        return $primary;
    }

    private function displayKeyword(string $keyword): string
    {
        $keyword = $this->clean($keyword);
        $keyword = preg_replace('/\bai\b/i', 'AI', $keyword) ?? $keyword;
        $keyword = preg_replace('/\bapi\b/i', 'API', $keyword) ?? $keyword;
        $keyword = preg_replace('/\bllm\b/i', 'LLM', $keyword) ?? $keyword;
        $keyword = preg_replace_callback('/\b(review|pricing|benchmark|company|model|models|tool|tools|directory|news|analysis)\b/iu', function ($match) {
            return ucfirst(Str::lower($match[0]));
        }, $keyword) ?? $keyword;
        $keyword = preg_replace('/\bvs\b/i', 'vs', $keyword) ?? $keyword;

        return ucfirst($keyword);
    }

    private function brandTitle(string $core): string
    {
        $core = $this->stripBrand($this->clean($core));
        $suffix = ' | AI Orbit';
        $budget = max(30, 68 - mb_strlen($suffix));

        return $this->fitTitle($core, $budget).$suffix;
    }

    private function fitTitle(string $title, int $max): string
    {
        $title = $this->clean($title);
        if (mb_strlen($title) <= $max) {
            return $title;
        }

        // Preserve as much of the real intent/headline as possible. The older
        // colon shortcut could collapse a descriptive article title such as
        // "Prompt Engineering: A Practical Framework..." to only
        // "Prompt Engineering", which was too lossy for Phase 1 ownership.
        $budget = max(12, $max - 1);
        $candidate = mb_substr($title, 0, $budget);

        // Never leave a half word such as "Benchm" or "new d" in the SERP
        // title. Back up to the previous whitespace boundary when possible.
        if (mb_strlen($title) > $budget && preg_match('/^(.+?)\s+\S*$/us', $candidate, $match)) {
            $wordSafe = trim($match[1]);
            if (mb_strlen($wordSafe) >= (int) floor($budget * 0.65)) {
                $candidate = $wordSafe;
            }
        }

        $candidate = rtrim($candidate, " \t\n\r\0\x0B:;,.-–—");

        return $candidate.'…';
    }

    private function fitDescription(string $description): string
    {
        $description = $this->clean($description);
        if ($description === '') {
            return '';
        }

        return Str::limit($description, 160, '');
    }

    private function stripBrand(?string $title): string
    {
        $title = $this->clean($title);
        $title = preg_replace('/\s*[|·—-]\s*AI Orbit\s*$/iu', '', $title) ?? $title;
        return trim($title);
    }

    private function clean(?string $value): string
    {
        $value = trim((string) $value);

        for ($i = 0; $i < 5; $i++) {
            $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $value) {
                break;
            }
            $value = $decoded;
        }

        $value = trim(strip_tags($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function fallback(?string $title, ?string $description, array $context = []): array
    {
        $includeBrand = (bool) ($context['include_brand'] ?? true);
        $cleanTitle = $this->clean($title);

        return [
            'title' => $includeBrand ? $cleanTitle : $this->stripBrand($cleanTitle),
            'description' => $this->fitDescription((string) $description),
            'primary_keyword' => null,
            'secondary_keywords' => [],
            'search_intent' => null,
            'topic_cluster' => null,
            'target_key' => null,
            'source' => null,
            'is_locked' => false,
        ];
    }
}
