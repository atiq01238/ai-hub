<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Benchmark;
use App\Models\BenchmarkResult;
use App\Models\Tool;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BenchmarkController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->string('type')->toString();
        $type = in_array($type, ['all', 'models', 'tools'], true) ? $type : 'all';
        $category = trim($request->string('category')->toString());
        $benchmarkClass = trim($request->string('class')->toString());
        $benchmarkClass = in_array($benchmarkClass, Benchmark::CLASSES, true) ? $benchmarkClass : '';
        $verifiedOnly = $request->boolean('verified', true);

        $query = Benchmark::query()
            ->where('is_active', true)
            ->with(['results' => function ($query) use ($verifiedOnly) {
                if ($verifiedOnly) {
                    $query->where('verified', true)->where('status', 'verified');
                }
                $query->with('benchmarkable');
            }])
            ->orderBy('benchmark_class')
            ->orderBy('category')
            ->orderBy('name');

        if ($benchmarkClass !== '') {
            $query->where('benchmark_class', $benchmarkClass);
        }
        if ($category !== '') {
            $query->where('category', $category);
        }

        $benchmarks = $query->get();

        // On the unfiltered landing page, the primary leaderboard is technical only.
        // If a semantic class is selected, its leaderboard remains class-specific.
        $leaderboardClass = $benchmarkClass !== '' && $benchmarkClass !== Benchmark::CLASS_UNCLASSIFIED
            ? $benchmarkClass
            : Benchmark::CLASS_TECHNICAL;

        $modelLeaderboard = $this->modelLeaderboard($benchmarks, $leaderboardClass);
        $toolLeaderboard = $this->toolLeaderboard($benchmarks, $leaderboardClass);

        $displayBenchmarks = $benchmarks->map(function (Benchmark $benchmark) use ($type) {
            $results = $this->latestResultsForBenchmark($benchmark)
                ->filter(function (BenchmarkResult $result) use ($type) {
                    if (! $result->benchmarkable) {
                        return false;
                    }
                    if ($type === 'models') {
                        return $result->benchmarkable_type === AiModel::class;
                    }
                    if ($type === 'tools') {
                        return $result->benchmarkable_type === Tool::class;
                    }
                    return in_array($result->benchmarkable_type, [AiModel::class, Tool::class], true);
                })
                ->sortBy(fn (BenchmarkResult $result) => $benchmark->higher_is_better ? -((float) $result->score) : (float) $result->score)
                ->values();

            return [
                'benchmark' => $benchmark,
                'results' => $results,
                'leader' => $results->first(),
            ];
        })->filter(fn ($row) => $row['results']->isNotEmpty())->values();

        $categories = Benchmark::query()
            ->where('is_active', true)
            ->when($benchmarkClass !== '', fn ($q) => $q->where('benchmark_class', $benchmarkClass))
            ->select('category')->distinct()->orderBy('category')->pluck('category');

        $verifiedCount = BenchmarkResult::query()->where('verified', true)->where('status', 'verified')->count();
        $latestTestedAt = BenchmarkResult::query()->whereNotNull('tested_at')->max('tested_at');
        $benchmarkClasses = collect(Benchmark::CLASSES)
            ->mapWithKeys(fn ($class) => [$class => Benchmark::classLabel($class)])
            ->all();

        return view('frontend.benchmarks.index', [
            'benchmarks' => $displayBenchmarks,
            'categories' => $categories,
            'benchmarkClasses' => $benchmarkClasses,
            'benchmarkClass' => $benchmarkClass,
            'leaderboardClass' => $leaderboardClass,
            'leaderboardClassLabel' => Benchmark::classLabel($leaderboardClass),
            'modelLeaderboard' => $modelLeaderboard,
            'toolLeaderboard' => $toolLeaderboard,
            'type' => $type,
            'category' => $category,
            'verifiedOnly' => $verifiedOnly,
            'stats' => [
                'benchmark_count' => Benchmark::query()->where('is_active', true)->count(),
                'verified_results' => $verifiedCount,
                'model_count' => $modelLeaderboard->count(),
                'tool_count' => $toolLeaderboard->count(),
                'latest_tested_at' => $latestTestedAt,
            ],
        ]);
    }

    public function show(Benchmark $benchmark)
    {
        abort_unless($benchmark->is_active, 404);

        $benchmark->load(['results' => fn ($q) => $q
            ->where('verified', true)
            ->where('status', 'verified')
            ->with('benchmarkable')
            ->orderByDesc('tested_at')
            ->orderByDesc('id')]);

        $results = $this->latestResultsForBenchmark($benchmark)
            ->sortBy(fn ($result) => $benchmark->higher_is_better ? -(float) $result->score : (float) $result->score)
            ->values();

        $title = $benchmark->name.' AI Benchmark Leaderboard'.($benchmark->version ? ' '.$benchmark->version : '').' (2026)';
        $description = 'Explore verified '.$benchmark->name.' '.Benchmark::classLabel($benchmark->benchmark_class).' results, rankings, methodology and sources on AI Orbit.';

        return view('frontend.benchmarks.show', compact('benchmark', 'results', 'title', 'description'));
    }

    private function modelLeaderboard(Collection $benchmarks, string $benchmarkClass): Collection
    {
        return $this->buildLeaderboard($benchmarks, AiModel::class, $benchmarkClass)
            ->map(function ($row) {
                $row['entity']->loadMissing(['company', 'tool']);
                return $row;
            });
    }

    private function toolLeaderboard(Collection $benchmarks, string $benchmarkClass): Collection
    {
        return $this->buildLeaderboard($benchmarks, Tool::class, $benchmarkClass)
            ->map(function ($row) {
                $row['entity']->loadMissing(['company', 'category']);
                return $row;
            });
    }

    private function buildLeaderboard(Collection $benchmarks, string $morphClass, string $benchmarkClass): Collection
    {
        if ($benchmarkClass === Benchmark::CLASS_UNCLASSIFIED) {
            return collect();
        }

        $rows = [];

        foreach ($benchmarks->where('benchmark_class', $benchmarkClass) as $benchmark) {
            foreach ($this->latestResultsForBenchmark($benchmark)->where('benchmarkable_type', $morphClass) as $result) {
                if (! $result->benchmarkable) {
                    continue;
                }

                $id = $result->benchmarkable_id;
                $normalized = $this->normalize($benchmark, (float) $result->score);
                $weight = max((float) $benchmark->weight, 0.01);

                $rows[$id] ??= [
                    'entity' => $result->benchmarkable,
                    'weighted_total' => 0.0,
                    'weight_total' => 0.0,
                    'result_count' => 0,
                    'verified_count' => 0,
                    'benchmark_class' => $benchmarkClass,
                ];

                $rows[$id]['weighted_total'] += $normalized * $weight;
                $rows[$id]['weight_total'] += $weight;
                $rows[$id]['result_count']++;
                $rows[$id]['verified_count'] += $result->verified ? 1 : 0;
            }
        }

        return collect($rows)
            ->map(function ($row) {
                $row['score'] = $row['weight_total'] > 0
                    ? round($row['weighted_total'] / $row['weight_total'], 1)
                    : 0.0;
                return $row;
            })
            ->sortByDesc('score')
            ->values();
    }

    private function latestResultsForBenchmark(Benchmark $benchmark): Collection
    {
        return $benchmark->results
            ->sortByDesc(fn ($result) => (($result->tested_at?->timestamp ?? 0) * 100000) + $result->id)
            ->unique(fn ($result) => $result->benchmarkable_type.'|'.$result->benchmarkable_id)
            ->values();
    }

    private function normalize(Benchmark $benchmark, float $score): float
    {
        $min = (float) ($benchmark->min_score ?? 0);
        $max = (float) ($benchmark->max_score ?? 100);
        $range = max($max - $min, .000001);
        $value = $benchmark->higher_is_better
            ? (($score - $min) / $range) * 100
            : (($max - $score) / $range) * 100;
        return min(100, max(0, $value));
    }
}
