<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SavedSearch;
use App\Models\SearchEvent;
use App\Models\Tool;
use App\Services\Search\SearchIntelligenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function index(Request $request, SearchIntelligenceService $search)
    {
        $query = $search->cleanQuery((string) $request->query('q', ''));
        $type = (string) $request->query('type', 'all');
        $payload = $search->search($query, $type);

        $type = $payload['type'];
        $counts = $payload['counts'];
        $total = $payload['total'];
        $tools = $payload['results']['tools'];
        $models = $payload['results']['models'];
        $news = $payload['results']['news'];
        $companies = $payload['results']['companies'];
        $articles = $payload['results']['articles'];
        $comparisons = $payload['results']['comparisons'];
        $benchmarks = $payload['results']['benchmarks'];

        if ($query !== '') {
            SearchEvent::create([
                'user_id' => $request->user()?->id,
                'query' => Str::lower($query),
                'type' => $type,
                'result_count' => $total,
                'session_key' => $this->sessionFingerprint($request),
            ]);
        }

        $popularCategories = Category::product()->active()->where('is_indexable', true)
            ->withCount(['tools' => fn ($q) => $q->where('status', 'published')])
            ->orderByDesc('tools_count')->take(8)->get();

        $trendingTools = Tool::with('company')->where('status', 'published')
            ->orderByDesc('popularity')->orderByDesc('rating')->take(6)->get();

        $recentSearches = $request->user()
            ? SearchEvent::where('user_id', $request->user()->id)->latest()->pluck('query')->unique()->take(6)
            : collect();

        $savedSearches = $request->user()
            ? SavedSearch::where('user_id', $request->user()->id)->latest()->take(8)->get()
            : collect();

        $trendingSearches = $search->trendingSearches(8, 30);
        $discoveryPaths = $query !== '' ? $search->taxonomyDiscovery($query, 8) : collect();
        $correction = $query !== '' && $total === 0 ? $search->suggestCorrection($query) : null;

        return view('frontend.search.index', compact(
            'query', 'type', 'counts', 'tools', 'models', 'news', 'companies', 'articles', 'comparisons', 'benchmarks',
            'popularCategories', 'trendingTools', 'recentSearches', 'savedSearches', 'trendingSearches',
            'discoveryPaths', 'correction', 'total'
        ));
    }

    public function suggest(Request $request, SearchIntelligenceService $search): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:180'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:15'],
        ]);

        return response()->json(
            $search->suggestions((string) ($validated['q'] ?? ''), (int) ($validated['limit'] ?? 10))
        );
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'query' => 'required|string|max:180',
            'type' => 'nullable|in:all,tools,models,news,companies,articles,comparisons,benchmarks',
        ]);

        SavedSearch::firstOrCreate([
            'user_id' => $request->user()->id,
            'query' => trim($data['query']),
            'type' => $data['type'] ?? 'all',
        ]);

        return back()->with('status', 'Search saved.');
    }

    public function destroySaved(Request $request, SavedSearch $savedSearch)
    {
        abort_unless($savedSearch->user_id === $request->user()->id, 403);
        $savedSearch->delete();

        return back()->with('status', 'Saved search removed.');
    }

    public function click(Request $request): JsonResponse
    {
        $data = $request->validate([
            'query' => 'required|string|max:180',
            'target_type' => 'required|in:tool,model,news,company,article,comparison,benchmark',
            'target_id' => 'required|integer|min:1',
        ]);

        $normalized = Str::lower(trim($data['query']));
        $fingerprint = $this->sessionFingerprint($request);

        $eventQuery = SearchEvent::query()->where('query', $normalized);
        if ($request->user()) {
            $eventQuery->where('user_id', $request->user()->id);
        } else {
            $eventQuery->whereNull('user_id')->where('session_key', $fingerprint);
        }

        $event = $eventQuery->latest()->first();

        if (! $event) {
            $event = SearchEvent::create([
                'user_id' => $request->user()?->id,
                'query' => $normalized,
                'type' => 'all',
                'result_count' => 1,
                'session_key' => $fingerprint,
            ]);
        }

        $event->update([
            'clicked' => true,
            'clicked_type' => $data['target_type'],
            'clicked_id' => $data['target_id'],
        ]);

        return response()->json(['ok' => true]);
    }

    private function sessionFingerprint(Request $request): string
    {
        return hash_hmac('sha256', $request->session()->getId(), (string) config('app.key'));
    }
}
