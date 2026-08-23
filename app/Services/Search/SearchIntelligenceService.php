<?php

namespace App\Services\Search;

use App\Models\AiModel;
use App\Models\AiTest;
use App\Models\Article;
use App\Models\Category;
use App\Models\Comparison;
use App\Models\Company;
use App\Models\Benchmark;
use App\Models\Feature;
use App\Models\NewsItem;
use App\Models\SearchEvent;
use App\Models\Tool;
use App\Models\UseCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SearchIntelligenceService
{
    public const TYPES = ['tools', 'models', 'news', 'companies', 'articles', 'comparisons', 'benchmarks', 'tests'];

    public function search(string $query, string $type = 'all'): array
    {
        $query = $this->cleanQuery($query);
        $type = in_array($type, array_merge(['all'], self::TYPES), true) ? $type : 'all';
        $tokens = $this->expandedTokens($query);

        $counts = array_fill_keys(self::TYPES, 0);
        $results = collect(self::TYPES)->mapWithKeys(fn ($key) => [$key => collect()])->all();

        if ($query === '') {
            return compact('query', 'type', 'tokens', 'counts', 'results') + ['total' => 0];
        }

        $builders = $this->builders($tokens);
        $limit = (int) config('search_intelligence.candidate_limit', 240);
        $displayLimit = $type === 'all'
            ? (int) config('search_intelligence.all_results_limit', 6)
            : (int) config('search_intelligence.single_type_limit', 30);

        foreach ($builders as $key => $builder) {
            $counts[$key] = (clone $builder)->count();

            if ($type !== 'all' && $type !== $key) {
                continue;
            }

            $candidates = (clone $builder)->limit($limit)->get();
            $results[$key] = $this->rank($key, $candidates, $query, $tokens)
                ->take($displayLimit)
                ->values();
        }

        return compact('query', 'type', 'tokens', 'counts', 'results') + ['total' => array_sum($counts)];
    }

    public function suggestions(string $query, int $limit = 10): array
    {
        $query = $this->cleanQuery($query);
        if (mb_strlen($query) < 2) {
            return ['query' => $query, 'suggestions' => [], 'correction' => null];
        }

        $tokens = $this->expandedTokens($query);
        $builders = $this->suggestionBuilders($query);
        $pool = collect();

        foreach ($builders as $type => $builder) {
            $items = (clone $builder)->limit(18)->get();
            $ranked = $this->rank($type, $items, $query, $tokens)->take(3);

            foreach ($ranked as $item) {
                $pool->push($this->suggestionPayload($type, $item));
            }
        }

        foreach ($this->taxonomyMatches($query, 4) as $taxonomy) {
            $pool->push($taxonomy);
        }

        $suggestions = $pool
            ->filter(fn ($item) => filled($item['url'] ?? null))
            ->sortByDesc('score')
            ->unique(fn ($item) => ($item['type'] ?? '').'|'.($item['url'] ?? ''))
            ->take(max(1, min(15, $limit)))
            ->values()
            ->map(fn ($item) => collect($item)->except('score')->all())
            ->all();

        return [
            'query' => $query,
            'suggestions' => $suggestions,
            'correction' => count($suggestions) < 2 ? $this->suggestCorrection($query) : null,
        ];
    }

    public function taxonomyDiscovery(string $query, int $limit = 8): Collection
    {
        return collect($this->taxonomyMatches($query, $limit));
    }

    public function trendingSearches(int $limit = 8, int $days = 30): Collection
    {
        return SearchEvent::query()
            ->select('query')
            ->selectRaw('COUNT(*) as searches')
            ->selectRaw('SUM(CASE WHEN clicked = 1 THEN 1 ELSE 0 END) as clicks')
            ->where('created_at', '>=', now()->subDays(max(1, $days)))
            ->where('result_count', '>', 0)
            ->whereRaw('CHAR_LENGTH(query) >= 2')
            ->groupBy('query')
            ->orderByDesc('searches')
            ->orderByDesc('clicks')
            ->limit(max(1, min(20, $limit)))
            ->get();
    }

    public function suggestCorrection(string $query): ?string
    {
        $query = $this->normalize($query);
        if (mb_strlen($query) < 3 || mb_strlen($query) > 70) {
            return null;
        }

        $vocabulary = Cache::remember('search-intelligence:correction-vocabulary', now()->addMinutes(30), function () {
            return collect()
                ->merge(Tool::where('status', 'published')->pluck('name'))
                ->merge(AiModel::whereIn('status', ['active', 'preview'])->pluck('name'))
                ->merge(Company::where('status', 'active')->pluck('name'))
                ->merge(Comparison::where('status', 'published')->pluck('title'))
                ->merge(Benchmark::where('is_active', true)->pluck('name'))
                ->merge(Category::active()->where('is_indexable', true)->pluck('name'))
                ->merge(Feature::active()->where('is_indexable', true)->pluck('name'))
                ->merge(UseCase::active()->where('is_indexable', true)->pluck('name'))
                ->filter()
                ->unique()
                ->values();
        });

        $best = null;
        $bestScore = 0.0;

        foreach ($vocabulary as $term) {
            $candidate = $this->normalize((string) $term);
            if ($candidate === $query) {
                return null;
            }

            $maxLen = max(strlen($query), strlen($candidate));
            if ($maxLen === 0 || abs(strlen($query) - strlen($candidate)) > 5) {
                continue;
            }

            $distance = levenshtein($query, $candidate);
            $similarity = 1 - ($distance / $maxLen);

            if ($distance <= 3 && $similarity > $bestScore) {
                $best = (string) $term;
                $bestScore = $similarity;
            }
        }

        return $bestScore >= 0.62 ? $best : null;
    }

    public function cleanQuery(string $query): string
    {
        $query = strip_tags($query);
        $query = preg_replace('/\s+/u', ' ', $query) ?? $query;
        return Str::limit(trim($query), (int) config('search_intelligence.max_query_length', 180), '');
    }

    private function suggestionBuilders(string $query): array
    {
        $needle = '%'.$this->escapeLike($query).'%';

        return [
            'tools' => Tool::query()->with(['company', 'category', 'subcategoryTerm', 'featureTerms', 'useCaseTerms', 'tagTerms'])
                ->where('status', 'published')
                ->where(fn ($q) => $q->where('name', 'like', $needle)
                    ->orWhereHas('company', fn ($company) => $company->where('name', 'like', $needle))),

            'models' => AiModel::query()->with(['company', 'tool', 'featureTerms', 'useCaseTerms', 'tagTerms'])
                ->whereIn('status', ['active', 'preview'])
                ->where(fn ($q) => $q->where('name', 'like', $needle)->orWhere('version', 'like', $needle)
                    ->orWhereHas('company', fn ($company) => $company->where('name', 'like', $needle))
                    ->orWhereHas('tool', fn ($tool) => $tool->where('name', 'like', $needle))),

            'news' => NewsItem::query()->with('company')
                ->where('status', 'published')
                ->where(fn ($q) => $q->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate'))
                ->where(fn ($q) => $q->where('headline', 'like', $needle)->orWhere('source', 'like', $needle)),

            'companies' => Company::query()->withCount(['tools', 'models'])->with(['tools:id,company_id,name', 'models:id,company_id,name'])
                ->where('status', 'active')->where('name', 'like', $needle),

            'articles' => Article::query()->with(['author', 'company', 'categoryTerm', 'tagTerms', 'relatedToolTerms', 'relatedModelTerms'])
                ->where('status', 'published')->where('approval_status', 'approved')
                ->where('title', 'like', $needle),

            'comparisons' => Comparison::query()->where('status', 'published')
                ->where('title', 'like', $needle),

            'benchmarks' => Benchmark::query()->where('is_active', true)
                ->where('name', 'like', $needle),

            'tests' => AiTest::query()->with(['feature', 'useCase'])->withCount(['completedResults as results_count'])
                ->published()->where('name', 'like', $needle),
        ];
    }

    private function builders(Collection $tokens): array
    {
        return [
            'tools' => Tool::query()
                ->with(['company', 'category', 'subcategoryTerm', 'featureTerms', 'useCaseTerms', 'tagTerms'])
                ->where('status', 'published')
                ->where(fn (Builder $q) => $this->match($q, $tokens, ['name', 'short_description', 'description', 'subcategory', 'pricing_models', 'tags', 'capabilities', 'platforms'], ['company', 'category', 'subcategoryTerm', 'featureTerms', 'useCaseTerms', 'tagTerms'])),

            'models' => AiModel::query()
                ->with(['company', 'tool', 'featureTerms', 'useCaseTerms', 'tagTerms'])
                ->whereIn('status', ['active', 'preview'])
                ->where(fn (Builder $q) => $this->match($q, $tokens, ['name', 'version', 'capability_notes', 'capabilities'], ['company', 'tool', 'featureTerms', 'useCaseTerms', 'tagTerms'])),

            'news' => NewsItem::query()
                ->with('company')
                ->where('status', 'published')
                ->where(fn ($q) => $q->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate'))
                ->where(fn (Builder $q) => $this->match($q, $tokens, ['headline', 'summary', 'ai_summary', 'source', 'category', 'ai_topic', 'ai_tags', 'tags'], ['company'])),

            'companies' => Company::query()
                ->withCount([
                    'tools' => fn ($q) => $q->where('status', 'published'),
                    'models' => fn ($q) => $q->whereIn('status', ['active', 'preview']),
                ])
                ->with(['tools:id,company_id,name', 'models:id,company_id,name'])
                ->where('status', 'active')
                ->where(fn (Builder $q) => $this->match($q, $tokens, ['name', 'description'], ['tools', 'models'])),

            'articles' => Article::query()
                ->with(['author', 'company', 'categoryTerm', 'tagTerms', 'relatedToolTerms', 'relatedModelTerms'])
                ->where('status', 'published')
                ->where('approval_status', 'approved')
                ->where(fn (Builder $q) => $this->match($q, $tokens, ['title', 'summary', 'content', 'category', 'tags'], ['company', 'categoryTerm', 'tagTerms', 'relatedToolTerms', 'relatedModelTerms'])),

            'comparisons' => Comparison::query()
                ->where('status', 'published')
                ->where(fn (Builder $q) => $this->match($q, $tokens, ['title', 'summary', 'primary_intent', 'comparable_type'])),

            'benchmarks' => Benchmark::query()
                ->where('is_active', true)
                ->where(fn (Builder $q) => $this->match($q, $tokens, ['name', 'category', 'description', 'version', 'variant'])),

            'tests' => AiTest::query()
                ->with(['feature', 'useCase'])
                ->withCount(['completedResults as results_count'])
                ->published()
                ->where(fn (Builder $q) => $this->match($q, $tokens, ['name', 'short_description', 'prompt', 'criteria', 'category', 'test_type'], ['feature', 'useCase'])),
        ];
    }

    private function match(Builder $query, Collection $tokens, array $columns, array $relations = []): void
    {
        foreach ($tokens as $token) {
            $needle = '%'.$this->escapeLike($token).'%';

            foreach ($columns as $column) {
                $query->orWhere($column, 'like', $needle);
            }

            foreach ($relations as $relation) {
                $query->orWhereHas($relation, function (Builder $related) use ($needle) {
                    $related->where(function (Builder $inner) use ($needle) {
                        $inner->where('name', 'like', $needle);
                        // Taxonomy/provider descriptions improve discovery when available.
                        if (in_array('description', $inner->getModel()->getFillable(), true)) {
                            $inner->orWhere('description', 'like', $needle);
                        }
                        if (in_array('short_description', $inner->getModel()->getFillable(), true)) {
                            $inner->orWhere('short_description', 'like', $needle);
                        }
                    });
                });
            }
        }
    }

    private function rank(string $type, Collection $items, string $query, Collection $tokens): Collection
    {
        return $items->map(function ($item) use ($type, $query, $tokens) {
            $item->setAttribute('_search_score', $this->score($type, $item, $query, $tokens));
            return $item;
        })->sortByDesc('_search_score');
    }

    private function score(string $type, $item, string $query, Collection $tokens): float
    {
        $queryN = $this->normalize($query);
        $title = $this->normalize($this->titleFor($type, $item));
        $primary = $this->normalize($this->primaryTextFor($type, $item));
        $related = $this->normalize($this->relatedTextFor($type, $item));
        $score = 0.0;

        if ($title === $queryN) $score += 1200;
        elseif (Str::startsWith($title, $queryN)) $score += 760;
        elseif (Str::contains($title, $queryN)) $score += 520;

        if ($queryN !== '' && Str::contains($primary, $queryN)) $score += 250;
        if ($queryN !== '' && Str::contains($related, $queryN)) $score += 180;

        foreach ($tokens as $token) {
            $token = $this->normalize($token);
            if ($token === '') continue;
            if ($title === $token) $score += 220;
            elseif (Str::contains($title, $token)) $score += 140;
            if (Str::contains($primary, $token)) $score += 34;
            if (Str::contains($related, $token)) $score += 48;
        }

        // Small quality/freshness tie-breakers. Relevance always dominates.
        if ($type === 'tools') {
            $score += min(25, ((float) ($item->rating ?? 0)) * 3);
            $score += min(20, log10(max(1, (int) ($item->popularity ?? 0))) * 5);
        } elseif ($type === 'models') {
            $score += min(24, ((float) ($item->benchmark_score ?? 0)) / 5);
        } elseif ($type === 'news') {
            $score += min(18, ((float) ($item->trending_score ?? 0)) / 5);
            if ($item->published_at) $score += max(0, 16 - min(16, $item->published_at->diffInDays(now())));
        } elseif ($type === 'comparisons') {
            $score += min(20, log10(max(1, (int) ($item->views ?? 0))) * 5);
        } elseif ($type === 'tests') {
            $score += $item->is_featured ? 16 : 0;
            $score += $item->is_verified ? 14 : 0;
        }

        return round($score, 3);
    }

    private function titleFor(string $type, $item): string
    {
        return match ($type) {
            'news' => (string) $item->headline,
            'articles', 'comparisons' => (string) $item->title,
            default => (string) $item->name,
        };
    }

    private function primaryTextFor(string $type, $item): string
    {
        return match ($type) {
            'tools' => implode(' ', array_filter([$item->name, $item->short_description, $item->description, $item->subcategory, json_encode($item->pricing_models), json_encode($item->tags), json_encode($item->capabilities), json_encode($item->platforms)])),
            'models' => implode(' ', array_filter([$item->name, $item->version, $item->capability_notes, json_encode($item->capabilities)])),
            'news' => implode(' ', array_filter([$item->headline, $item->summary, $item->ai_summary, $item->source, $item->category, $item->ai_topic, json_encode($item->ai_tags), json_encode($item->tags)])),
            'companies' => implode(' ', array_filter([$item->name, $item->description])),
            'articles' => implode(' ', array_filter([$item->title, $item->summary, strip_tags((string) $item->content), $item->category, json_encode($item->tags)])),
            'comparisons' => implode(' ', array_filter([$item->title, $item->summary, $item->primary_intent, $item->comparable_type])),
            'benchmarks' => implode(' ', array_filter([$item->name, $item->category, $item->description, $item->version, $item->variant])),
            'tests' => implode(' ', array_filter([$item->name, $item->short_description, $item->prompt, $item->criteria, $item->category, $item->test_type])),
            default => '',
        };
    }

    private function relatedTextFor(string $type, $item): string
    {
        $names = match ($type) {
            'tools' => collect([$item->company?->name, $item->category?->name, $item->subcategoryTerm?->name])
                ->merge($item->featureTerms?->pluck('name') ?? [])
                ->merge($item->useCaseTerms?->pluck('name') ?? [])
                ->merge($item->tagTerms?->pluck('name') ?? []),
            'models' => collect([$item->company?->name, $item->tool?->name])
                ->merge($item->featureTerms?->pluck('name') ?? [])
                ->merge($item->useCaseTerms?->pluck('name') ?? [])
                ->merge($item->tagTerms?->pluck('name') ?? []),
            'news' => collect([$item->company?->name]),
            'companies' => collect()->merge($item->tools?->pluck('name') ?? [])->merge($item->models?->pluck('name') ?? []),
            'articles' => collect([$item->company?->name, $item->categoryTerm?->name])
                ->merge($item->tagTerms?->pluck('name') ?? [])
                ->merge($item->relatedToolTerms?->pluck('name') ?? [])
                ->merge($item->relatedModelTerms?->pluck('name') ?? []),
            'tests' => collect([$item->feature?->name, $item->useCase?->name]),
            default => collect(),
        };

        return $names->filter()->implode(' ');
    }

    private function suggestionPayload(string $type, $item): array
    {
        return [
            'type' => $type,
            'label' => $this->titleFor($type, $item),
            'meta' => $this->suggestionMeta($type, $item),
            'url' => $this->urlFor($type, $item),
            'image' => $this->imageFor($type, $item),
            'score' => (float) $item->getAttribute('_search_score'),
            'target_type' => Str::singular($type === 'tests' ? 'test' : $type),
            'target_id' => (int) $item->getKey(),
        ];
    }

    private function suggestionMeta(string $type, $item): string
    {
        return match ($type) {
            'tools' => $item->company?->name ?: ($item->category?->name ?: 'AI Tool'),
            'models' => $item->company?->name ?: 'AI Model',
            'news' => $item->company?->name ?: ($item->source ?: 'AI News'),
            'companies' => 'AI Company',
            'articles' => $item->categoryTerm?->name ?: ($item->category ?: 'Article'),
            'comparisons' => Str::headline((string) $item->comparable_type).' comparison',
            'benchmarks' => ($item->category ?: 'AI').' benchmark',
            'tests' => ($item->category ?: 'Test Lab').' · '.$item->testTypeLabel(),
            default => Str::headline($type),
        };
    }

    private function urlFor(string $type, $item): string
    {
        return match ($type) {
            'tools' => route('tools.show', $item),
            'models' => route('models.show', $item),
            'news' => route('news.show', $item),
            'companies' => route('companies.show', $item),
            'articles' => route('articles.show', $item),
            'comparisons' => route('comparisons.show', $item),
            'benchmarks' => route('benchmarks.show', $item),
            'tests' => route('testlab.show', $item),
            default => route('search.index'),
        };
    }

    private function imageFor(string $type, $item): ?string
    {
        return match ($type) {
            'tools', 'models', 'companies' => $item->logo_url,
            'news' => $item->image_url,
            'articles' => $item->featured_image_url,
            default => null,
        };
    }

    private function taxonomyMatches(string $query, int $limit): array
    {
        $needle = '%'.$this->escapeLike($query).'%';
        $items = collect();

        Category::product()->active()->where('is_indexable', true)
            ->where(fn ($q) => $q->where('name', 'like', $needle)->orWhere('short_description', 'like', $needle)->orWhere('description', 'like', $needle))
            ->limit($limit)->get()->each(function ($term) use ($items, $query) {
                $items->push([
                    'type' => 'category', 'label' => $term->name, 'meta' => 'Browse category',
                    'url' => route('categories.show', $term), 'image' => null,
                    'score' => $this->taxonomyScore($term->name, $query) + 120,
                    'target_type' => null, 'target_id' => null,
                ]);
            });

        Feature::active()->where('is_indexable', true)
            ->where(fn ($q) => $q->where('name', 'like', $needle)->orWhere('short_description', 'like', $needle)->orWhere('description', 'like', $needle))
            ->limit($limit)->get()->each(function ($term) use ($items, $query) {
                $items->push([
                    'type' => 'feature', 'label' => $term->name, 'meta' => 'Explore feature',
                    'url' => route('features.show', $term), 'image' => null,
                    'score' => $this->taxonomyScore($term->name, $query) + 115,
                    'target_type' => null, 'target_id' => null,
                ]);
            });

        UseCase::active()->where('is_indexable', true)
            ->where(fn ($q) => $q->where('name', 'like', $needle)->orWhere('short_description', 'like', $needle)->orWhere('description', 'like', $needle))
            ->limit($limit)->get()->each(function ($term) use ($items, $query) {
                $items->push([
                    'type' => 'use-case', 'label' => $term->name, 'meta' => 'Explore use case',
                    'url' => route('use-cases.show', $term), 'image' => null,
                    'score' => $this->taxonomyScore($term->name, $query) + 110,
                    'target_type' => null, 'target_id' => null,
                ]);
            });

        return $items->sortByDesc('score')->take($limit)->values()->all();
    }

    private function taxonomyScore(string $name, string $query): float
    {
        $name = $this->normalize($name);
        $query = $this->normalize($query);
        if ($name === $query) return 900;
        if (Str::startsWith($name, $query)) return 600;
        if (Str::contains($name, $query)) return 400;
        return 100;
    }

    private function expandedTokens(string $query): Collection
    {
        $normalized = $this->normalize($query);
        $raw = collect(preg_split('/\s+/u', $normalized) ?: [])->filter()->values();
        $meaningful = $raw;

        if ($raw->count() > 1) {
            $stop = collect(config('search_intelligence.stop_words', []));
            $meaningful = $raw->reject(fn ($token) => $stop->contains($token))->values();
            if ($meaningful->isEmpty()) $meaningful = $raw;
        }

        $tokens = $meaningful->take(8);
        $groups = config('search_intelligence.synonym_groups', []);

        foreach ($groups as $group) {
            $group = collect($group)->map(fn ($term) => $this->normalize((string) $term))->filter();
            $matched = $group->contains(fn ($term) => $term === $normalized || Str::contains($normalized, $term) || $meaningful->contains($term));
            if ($matched) {
                $tokens = $tokens->merge($group);
            }
        }

        // Keep the full query for phrase-level matches when it is short enough.
        if (mb_strlen($normalized) <= 60 && str_contains($normalized, ' ')) {
            $tokens->prepend($normalized);
        }

        return $tokens->filter()->unique()->take(18)->values();
    }

    private function normalize(string $value): string
    {
        $value = Str::lower(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $value = preg_replace('/[^\p{L}\p{N}\s\-\.]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return trim($value);
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
