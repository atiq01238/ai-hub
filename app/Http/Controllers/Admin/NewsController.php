<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Article;
use App\Models\NewsBookmark;
use App\Models\NewsItem;
use App\Services\NewsEntityLinker;
use App\Services\NewsIntelligenceService;
use App\Services\NewsRelevanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        return $this->filteredIndex($request);
    }

    public function breaking(Request $request)
    {
        return $this->filteredIndex($request, null, 'breaking');
    }

    public function trending(Request $request)
    {
        return $this->filteredIndex($request, null, 'trending');
    }

    public function updates(Request $request)
    {
        return $this->filteredIndex($request, null, 'updates');
    }

    public function saved(Request $request)
    {
        $query = NewsItem::with(['company', 'newsSource'])
            ->whereHas('bookmarks', fn ($q) => $q->where('user_id', Auth::id()))
            ->latest('published_at');

        $this->applyCommonFilters($query, $request);

        $items = $query->paginate(20)->withQueryString();
        $companies = Company::orderBy('name')->get();

        return view('news.index', compact('items', 'companies'))
            ->with('notice', $items->total() . ' saved article(s).');
    }

    public function toggleSaved(int $id)
    {
        $item = NewsItem::findOrFail($id);

        $bookmark = NewsBookmark::where('user_id', Auth::id())
            ->where('news_item_id', $item->id)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            $message = 'Removed from Saved Intelligence.';
        } else {
            NewsBookmark::firstOrCreate([
                'user_id' => Auth::id(),
                'news_item_id' => $item->id,
            ]);
            $message = 'Added to Saved Intelligence.';
        }

        return back()->with('status', $message);
    }

    public function create()
    {
        $companies = Company::orderBy('name')->get();
        return view('news.form', compact('companies'));
    }

    public function store(Request $request)
    {
        $item = NewsItem::create($this->fromRequest($request));
        $this->refreshRelevanceIntelligence($item);

        return redirect()->route('admin.news.index')->with('status', 'News item created.');
    }

    public function show(int $id)
    {
        $item = NewsItem::with(['company', 'newsSource', 'duplicateOf'])->findOrFail($id);
        return view('news.show', ['item' => $item]);
    }

    public function edit(int $id)
    {
        $item = NewsItem::findOrFail($id);
        $companies = Company::orderBy('name')->get();
        return view('news.form', ['item' => $item] + compact('companies'));
    }

    public function update(Request $request, int $id)
    {
        $item = NewsItem::findOrFail($id);
        $item->update($this->fromRequest($request, $item));
        $this->refreshRelevanceIntelligence($item->fresh());

        return redirect()->route('admin.news.show', $item->id)->with('status', 'News item updated.');
    }

    public function destroy(int $id)
    {
        NewsItem::findOrFail($id)->delete();
        return redirect()->route('admin.news.index')->with('status', 'News item deleted.');
    }

    public function fetchNow()
    {
        $exitCode = Artisan::call('news:pipeline', ['--limit' => 100]);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return redirect()->route('admin.news.index')
                ->with('error', $output ?: 'News pipeline failed. Check the news pipeline log.');
        }

        return redirect()->route('admin.news.index')
            ->with('status', $output ?: 'News pipeline completed successfully.');
    }

    public function auditRelevance()
    {
        $exitCode = Artisan::call('news:audit-relevance', [
            '--all' => true,
            '--apply' => true,
            '--unpublish' => true,
            '--only-published' => true,
        ]);
        $output = trim(Artisan::output());

        if ($exitCode !== 0) {
            return redirect()->route('admin.news.index')
                ->with('error', $output ?: 'AI relevance audit failed.');
        }

        $lines = array_values(array_filter(preg_split('/\R/', $output) ?: []));
        $summary = implode(' | ', array_slice($lines, -3));

        return redirect()->route('admin.news.index')
            ->with('status', $summary ?: 'Published news relevance audit completed.');
    }

    public function duplicates(Request $request)
    {
        if (! Schema::hasColumn('news_items', 'duplicate_status')) {
            return view('news.duplicates', [
                'groups' => collect(),
                'stats' => ['total' => 0, 'possible' => 0, 'confirmed' => 0, 'unique' => NewsItem::count()],
                'notice' => 'Duplicate detection fields are not installed. Run php artisan migrate.',
            ]);
        }

        $stats = [
            'total' => NewsItem::whereIn('duplicate_status', ['possible', 'duplicate'])->count(),
            'possible' => NewsItem::where('duplicate_status', 'possible')->count(),
            'confirmed' => NewsItem::where('duplicate_status', 'duplicate')->count(),
            'unique' => NewsItem::where('duplicate_status', 'unique')->count(),
        ];

        // The active detector stores parent/child links directly on news_items.
        // Prefer that source of truth instead of the legacy group table.
        $groups = NewsItem::with(['duplicateOf', 'duplicates'])
            ->whereIn('duplicate_status', ['possible', 'duplicate'])
            ->latest('duplicate_checked_at')
            ->paginate(20)
            ->withQueryString();

        return view('news.duplicates', compact('groups', 'stats'));
    }

    private function filteredIndex(Request $request, ?string $forceCategory = null, string $mode = 'recent')
    {
        $query = NewsItem::with(['company', 'newsSource']);

        $this->applyCommonFilters($query, $request, $forceCategory);

        switch ($mode) {
            case 'breaking':
                $query->where(function (Builder $q) {
                    $q->where('category', 'Breaking News')
                        ->orWhere(function (Builder $q) {
                            $q->where('importance', '>=', 75)
                                ->where('published_at', '>=', now()->subHours(72));
                        });
                })
                ->where(function (Builder $q) {
                    $q->whereNull('duplicate_status')
                        ->orWhere('duplicate_status', '!=', 'duplicate');
                })
                ->orderByDesc('importance')
                ->orderByDesc('published_at');
                break;

            case 'trending':
                $query->where(function (Builder $q) {
                    $q->where('published_at', '>=', now()->subDays(7))
                        ->orWhere(function (Builder $q) {
                            $q->whereNull('published_at')
                                ->where('created_at', '>=', now()->subDays(7));
                        });
                })
                ->where(function (Builder $q) {
                    $q->whereNull('duplicate_status')
                        ->orWhere('duplicate_status', '!=', 'duplicate');
                })
                ->withCount(['bookmarks', 'duplicates'])
                ->orderByDesc('duplicates_count')
                ->orderByDesc('bookmarks_count')
                ->orderByDesc('importance')
                ->orderByDesc('published_at');
                break;

            case 'updates':
                $query->where(function (Builder $q) {
                    $q->where('published_at', '>=', now()->subHours(72))
                        ->orWhere('created_at', '>=', now()->subHours(72));
                })
                ->where(function (Builder $q) {
                    $q->whereNull('duplicate_status')
                        ->orWhere('duplicate_status', '!=', 'duplicate');
                })
                ->orderByDesc('published_at')
                ->orderByDesc('created_at');
                break;

            default:
                $query->latest('published_at')->latest('id');
        }

        $items = $query->paginate(20)->withQueryString();
        $companies = Company::orderBy('name')->get();

        return view('news.index', compact('items', 'companies'));
    }

    private function applyCommonFilters(Builder $query, Request $request, ?string $forceCategory = null): void
    {
        if ($search = trim((string) $request->query('search'))) {
            $escaped = addcslashes($search, '%_');
            $query->where(function (Builder $q) use ($escaped) {
                $q->where('headline', 'like', "%{$escaped}%")
                    ->orWhere('summary', 'like', "%{$escaped}%")
                    ->orWhere('source', 'like', "%{$escaped}%");
            });
        }

        if ($category = $forceCategory ?? $request->query('category')) {
            $query->where('category', $category);
        }

        if ($companyId = $request->query('company_id')) {
            $query->where('company_id', $companyId);
        }

        if ($relevance = $request->query('relevance')) {
            if (in_array($relevance, ['accepted', 'review', 'rejected', 'pending'], true)) {
                $query->where('ai_relevance_status', $relevance);
            }
        }
    }


    public function createArticleDraft(int $id)
    {
        $item = NewsItem::with(['relatedToolTerms','relatedModelTerms'])->findOrFail($id);
        $existing = Article::where('origin_news_item_id',$item->id)->first();
        if ($existing) return redirect()->route('admin.content.articles.editor.edit',$existing->id)->with('status','An article draft already exists for this news item.');
        $article = DB::transaction(function() use ($item) {
            $article=Article::create([
                'user_id'=>Auth::id(),'company_id'=>$item->company_id,'origin_news_item_id'=>$item->id,
                'title'=>$item->headline,'slug'=>Str::slug($item->headline).'-'.Str::lower(Str::random(6)),
                'summary'=>$item->ai_summary ?: $item->summary,
                'content'=>trim(($item->summary ?: '')."\n\nWhy it matters\n".($item->ai_why_it_matters ?: $item->why_it_matters ?: '')),
                'category'=>$item->category,'tags'=>$item->ai_tags ?: $item->tags ?: [],
                'status'=>'draft','approval_status'=>'draft',
                'seo_title'=>Str::limit($item->headline,60,''),
                'meta_description'=>Str::limit($item->ai_summary ?: $item->summary ?: $item->headline,155,''),
            ]);
            $article->relatedToolTerms()->sync($item->relatedToolTerms->pluck('id'));
            $article->relatedModelTerms()->sync($item->relatedModelTerms->pluck('id'));
            return $article;
        });
        return redirect()->route('admin.content.articles.editor.edit',$article->id)->with('status','Article draft created from News Intelligence. Review and expand it before publishing.');
    }

    private function fromRequest(Request $request, ?NewsItem $item = null): array
    {
        $data = $request->validate([
            'headline' => ['required', 'string', 'max:255'],
            'company_id' => ['nullable', 'exists:companies,id'],
            'summary' => ['nullable', 'string'],
            'why_it_matters' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'source' => ['nullable', 'string', 'max:150'],
            'source_url' => ['nullable', 'url', 'max:2048'],
            'sentiment' => ['required', 'in:positive,neutral,negative'],
            'importance' => ['required', 'integer', 'min:0', 'max:100'],
            'verification_status' => ['required', 'in:unverified,needs_verification,verified'],
            'ai_relevance_override' => ['nullable', 'in:auto,include,exclude'],
            'tags_input' => ['nullable', 'string'],
            'related_tools_input' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published,archived'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        foreach (['tags' => 'tags_input', 'related_tools' => 'related_tools_input'] as $column => $input) {
            $data[$column] = collect(explode(',', $data[$input] ?? ''))
                ->map(fn ($v) => trim($v))->filter()->values()->all();
            unset($data[$input]);
        }

        if (! $item || $item->headline !== $data['headline']) {
            $base = Str::slug($data['headline']) ?: 'news';
            do {
                $slug = $base . '-' . Str::lower(Str::random(6));
            } while (NewsItem::where('slug', $slug)->when($item, fn ($q) => $q->whereKeyNot($item->id))->exists());
            $data['slug'] = $slug;
        }

        if ($data['status'] === 'published' && ! ($item?->published_at)) {
            $data['published_at'] = now();
        }

        $data['ai_relevance_override'] = $data['ai_relevance_override'] ?? 'auto';

        $candidate = $item ? clone $item : new NewsItem();
        $candidate->forceFill($data);
        if ($item?->relationLoaded('newsSource')) {
            $candidate->setRelation('newsSource', $item->getRelation('newsSource'));
        } elseif ($item?->news_source_id) {
            $candidate->setRelation('newsSource', $item->newsSource);
        }

        $result = app(NewsRelevanceService::class)->evaluate($candidate);
        $data['ai_relevance_score'] = $result['score'];
        $data['ai_relevance_status'] = $result['status'];
        $data['ai_relevance_reasons'] = $result['reasons'];
        $data['ai_relevance_version'] = $result['version'];
        $data['ai_relevance_checked_at'] = now();

        if ($data['status'] === 'published' && $result['status'] !== 'accepted') {
            throw ValidationException::withMessages([
                'status' => 'This story does not pass the AI relevance gate (' . $result['score'] . '/100, ' . $result['status'] . '). Keep it as a draft, improve the AI context, or choose Force include after editorial verification.',
            ]);
        }

        // File mutations happen only after validation and relevance checks pass.
        if ($request->boolean('remove_image')) {
            if ($item?->image_path) {
                $this->deleteNewsImage($item->image_path);
            }
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($item?->image_path) {
                $this->deleteNewsImage($item->image_path);
            }
            $data['image_path'] = $request->file('image')->store('news', 'public');
        }

        unset($data['image'], $data['remove_image']);

        return $data;
    }

    private function refreshRelevanceIntelligence(NewsItem $item): void
    {
        $item->loadMissing(['company', 'newsSource.company']);
        $relevance = app(NewsRelevanceService::class);
        $relevance->apply($item);

        if ($relevance->isAccepted($item)) {
            app(NewsEntityLinker::class)->link($item);
            app(NewsIntelligenceService::class)->refresh($item->fresh());
            return;
        }

        $item->relatedToolTerms()->sync([]);
        $item->relatedModelTerms()->sync([]);
        $item->forceFill(['related_tools' => []])->saveQuietly();
    }

    private function deleteNewsImage(?string $path): void
    {
        $path = ltrim((string) $path, '/');
        if ($path === '') return;

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        Storage::disk('public')->delete($path);
    }
}
