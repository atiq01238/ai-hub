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
        $verifiedOnly = $request->boolean('verified', true);

        $benchmarks = Benchmark::query()
            ->where('is_active', true)
            ->with(['results' => function ($query) use ($verifiedOnly) {
                if ($verifiedOnly) {
                    $query->where('verified', true);
                }
                $query->with('benchmarkable');
            }])
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        if ($category !== '') {
            $benchmarks = $benchmarks->where('category', $category)->values();
        }

        $modelLeaderboard = $this->modelLeaderboard($benchmarks);
        $toolLeaderboard = $this->toolLeaderboard($benchmarks);

        $displayBenchmarks = $benchmarks->map(function (Benchmark $benchmark) use ($type) {
            $results = $benchmark->results
                ->filter(function (BenchmarkResult $result) use ($type) {
                    if (!$result->benchmarkable) {
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
                ->sortBy(function (BenchmarkResult $result) use ($benchmark) {
                    return $benchmark->higher_is_better ? -((float) $result->score) : (float) $result->score;
                })
                ->values();

            return [
                'benchmark' => $benchmark,
                'results' => $results,
                'leader' => $results->first(),
            ];
        })->filter(fn ($row) => $row['results']->isNotEmpty())->values();

        $categories = Benchmark::query()
            ->where('is_active', true)
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $verifiedCount = BenchmarkResult::query()->where('verified', true)->count();
        $latestTestedAt = BenchmarkResult::query()->whereNotNull('tested_at')->max('tested_at');

        return view('frontend.benchmarks.index', [
            'benchmarks' => $displayBenchmarks,
            'categories' => $categories,
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

    private function modelLeaderboard(Collection $benchmarks): Collection
    {
        return $this->buildLeaderboard($benchmarks, AiModel::class)
            ->map(function ($row) {
                $row['entity']->loadMissing(['company', 'tool']);
                return $row;
            });
    }

    private function toolLeaderboard(Collection $benchmarks): Collection
    {
        return $this->buildLeaderboard($benchmarks, Tool::class)
            ->map(function ($row) {
                $row['entity']->loadMissing(['company', 'category']);
                return $row;
            });
    }

    private function buildLeaderboard(Collection $benchmarks, string $morphClass): Collection
    {
        $rows = [];

        foreach ($benchmarks as $benchmark) {
            foreach ($benchmark->results->where('benchmarkable_type', $morphClass) as $result) {
                if (!$result->benchmarkable) {
                    continue;
                }

                $id = $result->benchmarkable_id;
                $max = max((float) $benchmark->max_score, 0.0001);
                $score = (float) $result->score;
                $normalized = $benchmark->higher_is_better
                    ? ($score / $max) * 100
                    : min(100, ($max / max($score, 0.0001)) * 100);
                $weight = max((float) $benchmark->weight, 0.01);

                $rows[$id] ??= [
                    'entity' => $result->benchmarkable,
                    'weighted_total' => 0.0,
                    'weight_total' => 0.0,
                    'result_count' => 0,
                    'verified_count' => 0,
                    'best_score' => null,
                ];

                $rows[$id]['weighted_total'] += $normalized * $weight;
                $rows[$id]['weight_total'] += $weight;
                $rows[$id]['result_count']++;
                $rows[$id]['verified_count'] += $result->verified ? 1 : 0;
                $rows[$id]['best_score'] = max($rows[$id]['best_score'] ?? 0, $normalized);
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
}
