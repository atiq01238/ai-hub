<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\AiTest;
use App\Models\AiTestResult;
use App\Services\Frontend\UserInteractionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TestLabController extends Controller
{
    public function index(Request $request)
    {
        $this->ensurePublic();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:80'],
            'difficulty' => ['nullable', 'string', 'max:20'],
            'verified' => ['nullable', 'in:1'],
            'sort' => ['nullable', 'in:newest,score,models,name'],
        ]);

        $query = AiTest::query()
            ->published()
            ->with(['feature:id,name,slug', 'useCase:id,name,slug'])
            ->withCount(['completedResults as results_count'])
            ->withMax('completedResults as best_score', 'overall_score')
            ->with(['completedResults' => fn ($q) => $q
                ->with(['model.company'])
                ->orderByDesc('overall_score')]);

        if ($q = trim((string) ($filters['q'] ?? ''))) {
            $query->where(function (Builder $builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('short_description', 'like', "%{$q}%")
                    ->orWhere('prompt', 'like', "%{$q}%")
                    ->orWhere('criteria', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhereHas('feature', fn ($feature) => $feature->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('useCase', fn ($useCase) => $useCase->where('name', 'like', "%{$q}%"));
            });
        }

        if ($category = $filters['category'] ?? null) $query->where('category', $category);
        if ($difficulty = $filters['difficulty'] ?? null) $query->where('difficulty', $difficulty);
        if (($filters['verified'] ?? null) === '1') $query->where('is_verified', true);

        match ($filters['sort'] ?? 'newest') {
            'score' => $query->orderByDesc('best_score')->latest('published_at'),
            'models' => $query->orderByDesc('results_count')->latest('published_at'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')->latest('published_at'),
        };

        $tests = $query->paginate(9)->withQueryString();
        $categories = AiTest::query()->published()->whereNotNull('category')
            ->selectRaw('category, COUNT(*) total')
            ->groupBy('category')->orderByDesc('total')->pluck('total', 'category');
        $difficulties = config('test_lab.difficulties', []);
        $leaderboard = $this->leaderboardQuery()->take(6)->get();

        $stats = [
            'tests' => AiTest::published()->count(),
            'results' => AiTestResult::complete()->whereHas('test', fn ($q) => $q->published())->count(),
            'verified_results' => AiTestResult::verified()->whereHas('test', fn ($q) => $q->published())->count(),
            'models' => AiModel::whereHas('testResults', fn ($q) => $q->complete()->whereHas('test', fn ($t) => $t->published()))->count(),
            'categories' => $categories->count(),
        ];

        return view('frontend.testlab.index', compact('tests', 'categories', 'difficulties', 'leaderboard', 'stats'));
    }

    public function show(Request $request, AiTest $test)
    {
        $this->ensurePublic();
        abort_unless($test->status === 'published' && $test->published_at?->lte(now()), 404);

        $test->load([
            'feature:id,name,slug', 'useCase:id,name,slug',
            'completedResults' => fn ($q) => $q->with(['model.company', 'runs' => fn ($runs) => $runs->where('status', 'complete')->orderBy('run_number')])->orderByDesc('overall_score'),
        ]);

        if ($request->user()) {
            app(UserInteractionService::class)->recordTestView($request->user(), $test->id);
        }

        $results = $test->completedResults->values();
        $winner = $results->first();
        $related = AiTest::query()->published()->withCount(['completedResults as results_count'])
            ->whereKeyNot($test->id)
            ->when($test->use_case_id, fn ($q) => $q->where('use_case_id', $test->use_case_id))
            ->when(! $test->use_case_id && $test->category, fn ($q) => $q->where('category', $test->category))
            ->latest('published_at')->take(4)->get();
        $criteria = $test->evaluationRubric();
        $weights = $test->scoreWeights();

        return view('frontend.testlab.show', compact('test', 'results', 'winner', 'related', 'criteria', 'weights'));
    }

    public function leaderboard(Request $request)
    {
        $this->ensurePublic();
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:80'],
            'verified' => ['nullable', 'in:1'],
        ]);

        $category = $filters['category'] ?? null;
        $verifiedOnly = ($filters['verified'] ?? null) === '1';
        $models = $this->leaderboardQuery($category, $verifiedOnly)->paginate(24)->withQueryString();
        $categories = AiTest::query()->published()->whereNotNull('category')
            ->selectRaw('category, COUNT(*) total')->groupBy('category')->orderByDesc('total')->pluck('total', 'category');

        return view('frontend.testlab.leaderboard', compact('models', 'categories', 'category', 'verifiedOnly'));
    }

    private function leaderboardQuery(?string $category = null, bool $verifiedOnly = false): Builder
    {
        $scope = function ($query) use ($category, $verifiedOnly) {
            $query->where('status', 'complete')
                ->when($verifiedOnly, fn ($q) => $q->where('is_verified', true))
                ->whereHas('test', fn ($test) => $test->published()
                    ->when($category, fn ($q) => $q->where('category', $category)));
        };

        return AiModel::query()
            ->with('company:id,name')
            ->whereIn('status', ['active', 'preview'])
            ->whereHas('testResults', $scope)
            ->withAvg(['testResults as lab_average' => $scope], 'overall_score')
            ->withCount(['testResults as lab_tests' => $scope])
            ->withCount(['testResults as verified_lab_tests' => fn ($q) => $q
                ->where('status', 'complete')->whereIn('verification_level', ['verified', 'high_confidence'])
                ->whereHas('test', fn ($test) => $test->published()
                    ->when($category, fn ($t) => $t->where('category', $category)))])
            ->withSum(['testResults as lab_runs' => $scope], 'run_count')
            ->orderByRaw('CASE WHEN lab_tests >= 3 THEN 0 ELSE 1 END')
            ->orderByDesc('lab_average')
            ->orderByDesc('lab_tests')
            ->orderBy('name');
    }
    private function ensurePublic(): void
    {
        abort_unless((bool) config('brand.features.public_test_lab', false), 404);
    }

}
