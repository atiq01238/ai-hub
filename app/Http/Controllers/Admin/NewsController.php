<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\NewsItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        return $this->filteredIndex($request);
    }

    // "Breaking" = same list, pre-filtered to the Breaking News category.
    public function breaking(Request $request)
    {
        $request->merge(['category' => 'Breaking News']);

        return $this->filteredIndex($request, 'Breaking News');
    }

    // "Trending" = same list, sorted by importance instead of recency.
    public function trending(Request $request)
    {
        return $this->filteredIndex($request, null, 'importance');
    }

    // "Updates" = same list, most recently published first (the default sort anyway).
    public function updates(Request $request)
    {
        return $this->filteredIndex($request);
    }

    // "Saved" needs a per-user bookmarks table, which doesn't exist yet —
    // showing an empty state instead of pretending this works.
    public function saved()
    {
        return view('news.index', [
            'items' => NewsItem::whereRaw('1 = 0')->paginate(20),
            'companies' => Company::orderBy('name')->get(),
            'notice' => "Saved articles aren't tracked yet — this needs a per-user bookmarks table, which isn't built yet.",
        ]);
    }

    public function create()
    {
        $companies = Company::orderBy('name')->get();

        return view('news.form', compact('companies'));
    }

    public function store(Request $request)
    {
        NewsItem::create($this->fromRequest($request));

        return redirect()
            ->route('admin.news.index')
            ->with('status', 'News item created.');
    }

    public function show(int $id)
    {
        $item = NewsItem::with('company')->findOrFail($id);

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

        return redirect()
            ->route('admin.news.show', $item->id)
            ->with('status', 'News item updated.');
    }

    public function destroy(int $id)
    {
        NewsItem::findOrFail($id)->delete();

        return redirect()
            ->route('admin.news.index')
            ->with('status', 'News item deleted.');
    }

    public function duplicates()
    {
        // Real duplicate detection needs text-similarity/NLP, which isn't built yet.
        // This just shows the (still static/demo) page so the link doesn't 404.
        return view('news.duplicates');
    }

    /**
     * Shared query builder for index()/breaking()/trending()/updates() —
     * reads search + filter fields from the URL's query string.
     */
    private function filteredIndex(Request $request, ?string $forceCategory = null, string $sort = 'recent')
    {
        $query = NewsItem::with('company');

        if ($search = $request->query('search')) {
            $query->where('headline', 'like', "%{$search}%");
        }

        if ($category = $forceCategory ?? $request->query('category')) {
            $query->where('category', $category);
        }

        if ($companyId = $request->query('company_id')) {
            $query->where('company_id', $companyId);
        }

        $query = $sort === 'importance'
            ? $query->orderByDesc('importance')
            : $query->latest('published_at');

        $items = $query->paginate(20)->withQueryString();
        $companies = Company::orderBy('name')->get();

        return view('news.index', compact('items', 'companies'));
    }

    private function fromRequest(Request $request, ?NewsItem $item = null): array
    {
        $data = $request->validate([
            'headline'             => ['required', 'string', 'max:255'],
            'company_id'           => ['nullable', 'exists:companies,id'],
            'summary'              => ['nullable', 'string'],
            'why_it_matters'       => ['nullable', 'string'],
            'category'             => ['nullable', 'string', 'max:100'],
            'source'               => ['nullable', 'string', 'max:150'],
            'source_url'           => ['nullable', 'url', 'max:255'],
            'sentiment'            => ['required', 'in:positive,neutral,negative'],
            'importance'           => ['required', 'integer', 'min:0', 'max:100'],
            'verification_status'  => ['required', 'in:unverified,needs_verification,verified'],
            'tags_input'           => ['nullable', 'string'],
            'related_tools_input'  => ['nullable', 'string'],
            'status'               => ['required', 'in:draft,published,archived'],
        ]);

        foreach (['tags' => 'tags_input', 'related_tools' => 'related_tools_input'] as $column => $input) {
            $data[$column] = collect(explode(',', $data[$input] ?? ''))
                ->map(fn ($v) => trim($v))
                ->filter()
                ->values()
                ->all();
            unset($data[$input]);
        }

        $data['slug'] = Str::slug($data['headline']) . '-' . Str::random(6);

        if ($data['status'] === 'published' && ! ($item?->published_at)) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
