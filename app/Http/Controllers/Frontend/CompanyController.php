<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Category;
use App\Models\Company;
use App\Models\NewsItem;
use App\Models\Tool;
use App\Services\Seo\CompanySeoService;
use App\Services\Seo\CompanyContentService;
use App\Services\Seo\InternalLinkingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,acquired'],
            'era' => ['nullable', 'in:before2000,2000s,2010s,2020s'],
            'sort' => ['nullable', 'in:featured,tools,models,newest,name'],
        ]);

        $query = Company::query()
            // Keep the canonical company directory aligned with the company sitemap.
            // Thin placeholder profiles remain accessible by direct URL, but are not
            // promoted through crawlable discovery surfaces until they have enough value.
            ->seoIndexable()
            ->withCount([
                'tools as published_tools_count' => fn ($q) => $q->where('status', 'published'),
                'models as active_models_count' => fn ($q) => $q->whereIn('status', ['active', 'preview']),
                'newsItems as published_news_count' => fn ($q) => $q->where('status', 'published')->whereNull('duplicate_of_id')->where(fn ($news) => $news->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate')),
            ]);

        if ($q = trim($filters['q'] ?? '')) {
            $query->where(function (Builder $builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('website', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        match ($filters['era'] ?? null) {
            'before2000' => $query->whereNotNull('founded_year')->where('founded_year', '<', 2000),
            '2000s' => $query->whereBetween('founded_year', [2000, 2009]),
            '2010s' => $query->whereBetween('founded_year', [2010, 2019]),
            '2020s' => $query->whereBetween('founded_year', [2020, 2029]),
            default => null,
        };

        match ($filters['sort'] ?? 'featured') {
            'tools' => $query->orderByDesc('published_tools_count')->orderBy('name'),
            'models' => $query->orderByDesc('active_models_count')->orderBy('name'),
            'newest' => $query->orderByDesc('founded_year')->orderBy('name'),
            'name' => $query->orderBy('name'),
            default => $query->orderByRaw('(published_tools_count + active_models_count * 2 + published_news_count) DESC')->orderBy('name'),
        };

        $companies = $query->paginate(12)->withQueryString();

        $stats = [
            'companies' => Company::query()->seoIndexable()->count(),
            'tools' => Tool::where('status', 'published')->count(),
            'models' => AiModel::whereIn('status', ['active', 'preview'])->count(),
            'news' => NewsItem::where('status', 'published')->whereNull('duplicate_of_id')->where(fn ($news) => $news->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate'))->count(),
        ];

        $leaders = Company::query()
            ->seoIndexable()
            ->withCount([
                'tools as published_tools_count' => fn ($q) => $q->where('status', 'published'),
                'models as active_models_count' => fn ($q) => $q->whereIn('status', ['active', 'preview']),
            ])
            ->where('status', 'active')
            ->orderByDesc('active_models_count')
            ->orderByDesc('published_tools_count')
            ->take(5)
            ->get();

        return view('frontend.companies.index', compact('companies', 'stats', 'leaders'));
    }

    public function show(Company $company, CompanySeoService $seoService, CompanyContentService $contentService, InternalLinkingService $internalLinks)
    {
        abort_unless($company->status !== 'inactive', 404);

        $isSeoIndexable = Company::query()
            ->seoIndexable()
            ->whereKey($company->id)
            ->exists();

        $company->loadCount([
            'tools as published_tools_count' => fn ($q) => $q->where('status', 'published'),
            'models as active_models_count' => fn ($q) => $q->whereIn('status', ['active', 'preview']),
            'newsItems as published_news_count' => fn ($q) => $q->where('status', 'published')->whereNull('duplicate_of_id')->where(fn ($news) => $news->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate')),
        ]);

        $tools = $company->tools()->with('category')->where('status', 'published')
            ->orderByDesc('rating')->orderByDesc('popularity')->take(6)->get();

        $models = $company->models()->with('tool')->whereIn('status', ['active', 'preview'])
            ->orderByDesc('benchmark_score')->orderByDesc('release_date')->take(6)->get();

        $news = $company->newsItems()
            ->where('status', 'published')
            ->whereNull('duplicate_of_id')
            ->where(fn ($query) => $query->whereNull('duplicate_status')->orWhere('duplicate_status', '!=', 'duplicate'))
            ->latest('published_at')->take(6)->get();

        $articles = $company->articles()
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->latest('published_at')
            ->take(4)
            ->get();

        $categoryIds = $company->tools()
            ->where('status', 'published')
            ->whereNotNull('category_id')
            ->distinct()
            ->pluck('category_id');

        $focusCategories = Category::query()
            ->product()
            ->active()
            ->whereIn('id', $categoryIds)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(5)
            ->get();

        $relatedComparisons = $internalLinks->comparisonsForCompany($company, 6);

        $relatedCompanies = Company::query()
            ->seoIndexable()
            ->withCount([
                'tools as published_tools_count' => fn ($q) => $q->where('status', 'published'),
                'models as active_models_count' => fn ($q) => $q->whereIn('status', ['active', 'preview']),
            ])
            ->when($categoryIds->isNotEmpty(), fn ($query) => $query->withCount([
                'tools as shared_category_tools_count' => fn ($q) => $q
                    ->where('status', 'published')
                    ->whereIn('category_id', $categoryIds),
            ]))
            ->where('status', 'active')
            ->whereKeyNot($company->id)
            ->when(
                $categoryIds->isNotEmpty(),
                fn ($query) => $query
                    ->orderByDesc('shared_category_tools_count')
                    ->orderByDesc('active_models_count')
                    ->orderByDesc('published_tools_count'),
                fn ($query) => $query
                    ->orderByDesc('active_models_count')
                    ->orderByDesc('published_tools_count')
            )
            ->take(4)
            ->get();

        $lastUpdated = collect([
            $company->updated_at,
            $tools->max('updated_at'),
            $models->max('updated_at'),
            $news->max('updated_at'),
            $articles->max('updated_at'),
        ])->filter()->sortDesc()->first();

        $contentSeo = $contentService->build($company, $tools, $models, $news, $articles, $relatedComparisons);

        $seo = $seoService->build(
            $company,
            (int) $company->published_tools_count,
            (int) $company->active_models_count,
            (int) $company->published_news_count,
            $lastUpdated
        );

        return view('frontend.companies.show', compact(
            'company', 'tools', 'models', 'news', 'articles', 'relatedCompanies', 'relatedComparisons', 'focusCategories', 'lastUpdated', 'seo', 'contentSeo', 'isSeoIndexable'
        ));
    }
}
