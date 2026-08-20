<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiTest;
use App\Services\Frontend\UserInteractionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TestLabController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable','string','max:120'],
            'category' => ['nullable','string','max:50'],
            'sort' => ['nullable','in:newest,score,models,name'],
        ]);

        $query = AiTest::query()->withCount('results')->with(['results.model']);
        if ($q = trim($filters['q'] ?? '')) {
            $query->where(function (Builder $b) use ($q) {
                $b->where('name','like',"%{$q}%")->orWhere('prompt','like',"%{$q}%")
                  ->orWhere('criteria','like',"%{$q}%")->orWhere('category','like',"%{$q}%");
            });
        }
        if (!empty($filters['category'])) $query->where('category',$filters['category']);

        match ($filters['sort'] ?? 'newest') {
            'name' => $query->orderBy('name'),
            'models' => $query->orderByDesc('results_count')->latest('id'),
            default => $query->latest('id'),
        };

        $tests = $query->paginate(9)->withQueryString();
        if (($filters['sort'] ?? '') === 'score') {
            $tests->setCollection($tests->getCollection()->sortByDesc(fn ($t) => (float) $t->results->max('overall_score'))->values());
        }

        $categories = AiTest::whereNotNull('category')->selectRaw('category, COUNT(*) total')->groupBy('category')->orderByDesc('total')->pluck('total','category');
        $leaderboard = AiModel::query()->withAvg('testResults as lab_average','overall_score')->withCount('testResults')
            ->has('testResults')->orderByDesc('lab_average')->take(6)->get();
        $stats = [
            'tests' => AiTest::count(),
            'results' => \App\Models\AiTestResult::count(),
            'models' => AiModel::has('testResults')->count(),
            'categories' => $categories->count(),
        ];
        return view('frontend.testlab.index', compact('tests','categories','leaderboard','stats'));
    }

    public function show(Request $request, AiTest $test)
    {
        $test->load(['results.model.company']);

        if ($request->user()) {
            app(UserInteractionService::class)->recordTestView($request->user(), $test->id);
        }
        $results = $test->results->sortByDesc('overall_score')->values();
        $winner = $results->first();
        $related = AiTest::query()->withCount('results')->whereKeyNot($test->id)
            ->when($test->category, fn ($q) => $q->where('category',$test->category))->latest()->take(4)->get();
        return view('frontend.testlab.show', compact('test','results','winner','related'));
    }
}
