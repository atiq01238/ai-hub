<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\NewsBookmark;
use App\Models\NewsItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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
        NewsItem::create($this->fromRequest($request));
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
            'tags_input' => ['nullable', 'string'],
            'related_tools_input' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published,archived'],
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

        return $data;
    }
}
