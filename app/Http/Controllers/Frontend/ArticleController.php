<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Article;
use App\Models\Company;
use App\Models\Category;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Services\Frontend\QuickFeedbackService;
use App\Services\Seo\InternalLinkingService;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:80'],
            'company' => ['nullable', 'integer', 'exists:companies,id'],
            'sort' => ['nullable', 'in:newest,oldest,title'],
        ]);

        $query = Article::query()
            ->with(['author', 'company', 'categoryTerm'])
            ->where('status', 'published')
            ->where('approval_status', 'approved');

        if ($q = trim($filters['q'] ?? '')) {
            $query->where(function (Builder $builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('summary', 'like', "%{$q}%")
                    ->orWhere('content', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%");
            });
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (!empty($filters['company'])) {
            $query->where('company_id', $filters['company']);
        }

        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->orderBy('published_at')->orderBy('title'),
            'title' => $query->orderBy('title'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        $articles = $query->paginate(12)->withQueryString();

        $featured = Article::query()
            ->with(['author', 'company'])
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->latest('published_at')
            ->first();

        $categoryCounts = Article::query()
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->whereNotNull('category')
            ->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->pluck('total', 'category');

        $companies = Company::query()
            ->whereHas('articles', fn ($q) => $q->where('status', 'published')->where('approval_status', 'approved'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $guideTopicId = Category::content()->active()->where('slug', 'guides-tutorials')->value('id');
        $stats = [
            'articles' => Article::where('status', 'published')->where('approval_status', 'approved')->count(),
            'guides' => Article::where('status', 'published')->where('approval_status', 'approved')
                ->when($guideTopicId, fn ($q, $id) => $q->where('category_id', $id), fn ($q) => $q->whereIn('category', ['Guide', 'Guides & Tutorials']))
                ->count(),
            'companies' => Company::whereHas('articles', fn ($q) => $q->where('status', 'published')->where('approval_status', 'approved'))->count(),
        ];

        return view('frontend.articles.index', compact('articles', 'featured', 'categoryCounts', 'companies', 'stats'));
    }

    public function show(Article $article, QuickFeedbackService $feedback, InternalLinkingService $internalLinks)
    {
        abort_unless($article->status === 'published' && $article->approval_status === 'approved', 404);

        $article->load(['author', 'reviewer', 'company', 'categoryTerm', 'relatedToolTerms.company', 'relatedModelTerms.company', 'tagTerms']);

        // Phase 3: relationship evidence drives article discovery. Existing
        // pivots/legacy links win; provider/category context is a secondary signal.
        $relatedTools = $internalLinks->toolsForArticle($article, 6);
        $relatedModels = $internalLinks->modelsForArticle($article, 6);
        $relatedArticles = $internalLinks->relatedArticles($article, 4);

        $previous = Article::where('status', 'published')->where('approval_status', 'approved')
            ->where('published_at', '<', $article->published_at)->latest('published_at')->first();
        $next = Article::where('status', 'published')->where('approval_status', 'approved')
            ->where('published_at', '>', $article->published_at)->oldest('published_at')->first();

        $articleFeedback = $feedback->voteSummary('article', $article->id, auth()->user());

        return view('frontend.articles.show', compact('article', 'relatedTools', 'relatedModels', 'relatedArticles', 'previous', 'next', 'articleFeedback'));
    }
}
