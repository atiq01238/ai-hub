<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Company;
use App\Models\NewsItem;
use App\Models\NewsSource;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'integer'],
            'sentiment' => ['nullable', 'in:positive,neutral,negative'],
            'verification' => ['nullable', 'in:verified,unverified,needs_verification'],
            'period' => ['nullable', 'in:24h,7d,30d'],
            'sort' => ['nullable', 'in:newest,importance,oldest'],
            'tab' => ['nullable', 'in:latest,breaking,trending,research'],
        ]);

        $base = NewsItem::query()
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(function (Builder $query) {
                $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate');
            });

        $query = (clone $base)->with(['company', 'newsSource']);

        if ($search = trim($filters['q'] ?? '')) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('headline', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%")
                    ->orWhere('ai_summary', 'like', "%{$search}%")
                    ->orWhere('ai_topic', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhereHas('company', fn (Builder $company) => $company->where('name', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['company'])) {
            $query->whereHas('company', fn (Builder $company) => $company->where('slug', $filters['company']));
        }

        if (! empty($filters['source'])) {
            $query->where('news_source_id', (int) $filters['source']);
        }

        if (! empty($filters['sentiment'])) {
            $query->where('sentiment', $filters['sentiment']);
        }

        if (! empty($filters['verification'])) {
            $query->where('verification_status', $filters['verification']);
        }

        match ($filters['period'] ?? null) {
            '24h' => $query->where('published_at', '>=', now()->subDay()),
            '7d' => $query->where('published_at', '>=', now()->subDays(7)),
            '30d' => $query->where('published_at', '>=', now()->subDays(30)),
            default => null,
        };

        match ($filters['tab'] ?? 'latest') {
            'breaking' => $query->where(function (Builder $q) {
                $q->where('category', 'Breaking News')->orWhere('importance', '>=', 90);
            }),
            'trending' => $query->where('importance', '>=', 75),
            'research' => $query->where(function (Builder $q) {
                $q->where('category', 'Research')->orWhere('ai_topic', 'Research');
            }),
            default => null,
        };

        match ($filters['sort'] ?? 'newest') {
            'importance' => $query->orderByDesc('importance')->orderByDesc('published_at'),
            'oldest' => $query->orderBy('published_at'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        $news = $query->paginate(12)->withQueryString();

        $featured = (clone $base)
            ->with(['company', 'newsSource'])
            ->where('published_at', '>=', now()->subDays(30))
            ->orderByDesc('importance')
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        if ($featured->count() < 3) {
            $featured = (clone $base)->with(['company', 'newsSource'])
                ->orderByDesc('importance')->orderByDesc('published_at')->take(3)->get();
        }

        $categories = (clone $base)
            ->whereNotNull('category')
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $companies = Company::query()
            ->withCount(['newsItems' => fn ($q) => $q->where('status', 'published')->whereNull('duplicate_of_id')])
            ->whereHas('newsItems', fn ($q) => $q->where('status', 'published')->whereNull('duplicate_of_id'))
            ->orderByDesc('news_items_count')
            ->get();

        $sources = NewsSource::query()
            ->withCount(['newsItems' => fn ($q) => $q->where('status', 'published')->whereNull('duplicate_of_id')])
            ->whereHas('newsItems', fn ($q) => $q->where('status', 'published')->whereNull('duplicate_of_id'))
            ->orderByDesc('news_items_count')
            ->take(12)
            ->get();

        $trending = (clone $base)->with('company')
            ->orderByDesc('trending_score')->orderByDesc('importance')->orderByDesc('published_at')->take(6)->get();

        $stats = [
            'published' => (clone $base)->count(),
            'verified' => (clone $base)->where('verification_status', 'verified')->count(),
            'today' => (clone $base)->where('published_at', '>=', now()->startOfDay())->count(),
            'sources' => NewsItem::query()->where('status', 'published')->whereNotNull('source')->distinct('source')->count('source'),
        ];

        return view('frontend.news.index', compact(
            'news', 'featured', 'categories', 'companies', 'sources', 'trending', 'stats'
        ));
    }

    public function show(NewsItem $news)
    {
        abort_unless($news->status === 'published', 404);
        abort_if($news->duplicate_of_id || $news->duplicate_status === 'duplicate', 404);

        $news->load(['company', 'newsSource', 'relatedToolTerms.company', 'relatedModelTerms.company']);

        $relatedNews = NewsItem::query()
            ->with(['company', 'newsSource'])
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(function (Builder $query) {
                $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate');
            })
            ->whereKeyNot($news->id)
            ->where(function (Builder $query) use ($news) {
                if ($news->company_id) {
                    $query->where('company_id', $news->company_id);
                }
                if ($news->category) {
                    $news->company_id ? $query->orWhere('category', $news->category) : $query->where('category', $news->category);
                }
            })
            ->orderByDesc('published_at')
            ->take(4)
            ->get();

        if ($relatedNews->count() < 4) {
            $extra = NewsItem::query()->with('company')
                ->where('status', 'published')
                ->whereNull('duplicate_of_id')
                ->where(function (Builder $query) {
                    $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate');
                })
                ->whereKeyNot($news->id)
                ->whereNotIn('id', $relatedNews->pluck('id'))
                ->orderByDesc('published_at')->take(4 - $relatedNews->count())->get();
            $relatedNews = $relatedNews->concat($extra);
        }

        $toolNames = collect($news->related_tools ?? [])->filter()->values();
        $relatedTools = $news->relatedToolTerms->merge($this->resolveTools($toolNames))->unique('id')->take(6)->values();

        $relatedModels = $news->relatedModelTerms;
        if ($relatedModels->isEmpty()) {
            $relatedModels = AiModel::query()->with('company')->whereIn('status', ['active', 'preview'])
                ->when($news->company_id, fn (Builder $query) => $query->where('company_id', $news->company_id))
                ->orderByDesc('benchmark_score')->take(3)->get();
        }

        $previous = NewsItem::query()
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(function (Builder $query) {
                $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate');
            })
            ->where('published_at', '<', $news->published_at ?? $news->created_at)
            ->orderByDesc('published_at')->first(['id', 'headline', 'slug', 'published_at']);

        $next = NewsItem::query()
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(function (Builder $query) {
                $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate');
            })
            ->where('published_at', '>', $news->published_at ?? $news->created_at)
            ->orderBy('published_at')->first(['id', 'headline', 'slug', 'published_at']);

        $tags = collect($news->ai_tags ?? [])->merge($news->tags ?? [])->filter()->unique()->values();

        return view('frontend.news.show', compact(
            'news', 'relatedNews', 'relatedTools', 'relatedModels', 'previous', 'next', 'tags'
        ));
    }

    private function resolveTools(Collection $names): Collection
    {
        if ($names->isEmpty()) {
            return collect();
        }

        return Tool::query()->with('company')->where('status', 'published')
            ->where(function (Builder $query) use ($names) {
                foreach ($names as $name) {
                    $query->orWhere('name', $name)->orWhere('name', 'like', '%' . $name . '%');
                }
            })
            ->take(4)->get();
    }
}
