<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\Benchmark;
use App\Models\BenchmarkResult;
use Illuminate\Support\Collection;

class BenchmarkScoringService
{
    public function normalized(BenchmarkResult $result): float
    {
        $benchmark = $result->benchmark;
        $min = (float) ($benchmark->min_score ?? 0);
        $max = (float) ($benchmark->max_score ?? 100);
        $range = max($max - $min, .000001);

        $value = $benchmark->higher_is_better
            ? (((float) $result->score - $min) / $range) * 100
            : (($max - (float) $result->score) / $range) * 100;

        return round(min(100, max(0, $value)), 2);
    }

    public function compositeForClass(object $item, string $benchmarkClass): ?float
    {
        $latest = $this->latestVerified($item, $benchmarkClass);
        if ($latest->isEmpty()) {
            return null;
        }

        $weighted = 0.0;
        $weights = 0.0;

        foreach ($latest as $result) {
            if (! $result->benchmark || ! $result->benchmark->is_active) {
                continue;
            }

            $weight = max((float) $result->benchmark->weight, .01);
            $weighted += $this->normalized($result) * $weight;
            $weights += $weight;
        }

        return $weights > 0 ? round($weighted / $weights, 1) : null;
    }

    /**
     * Compatibility method. It now returns one class-specific composite only;
     * incompatible semantic classes are never mixed together.
     */
    public function composite(object $item): float
    {
        $class = $this->primaryCompositeClass($item);
        return $class ? (float) ($this->compositeForClass($item, $class) ?? 0.0) : 0.0;
    }

    public function primaryCompositeClass(object $item): ?string
    {
        foreach ([Benchmark::CLASS_TECHNICAL, Benchmark::CLASS_AI_ORBIT_TESTED] as $class) {
            if ($this->latestVerified($item, $class)->isNotEmpty()) {
                return $class;
            }
        }

        return null;
    }

    /** @return array<string,float> */
    public function classComposites(object $item): array
    {
        $scores = [];
        foreach (Benchmark::CLASSES as $class) {
            if ($class === Benchmark::CLASS_UNCLASSIFIED) {
                continue;
            }
            $score = $this->compositeForClass($item, $class);
            if ($score !== null) {
                $scores[$class] = $score;
            }
        }
        return $scores;
    }

    public function sync(object $item): void
    {
        $latest = $this->latestVerified($item);

        // Legacy JSON mirror retains raw benchmark names/scores for compatibility.
        $item->benchmarks = $latest
            ->filter(fn ($result) => $result->benchmark)
            ->mapWithKeys(fn ($result) => [$result->benchmark->name => (float) $result->score])
            ->all();

        // Legacy benchmark_score now mirrors ONE compatible class only.
        $primaryClass = $this->primaryCompositeClass($item);
        $item->benchmark_score = $primaryClass
            ? $this->compositeForClass($item, $primaryClass)
            : null;

        $item->saveQuietly();
    }

    public function fingerprint(int $benchmarkId, string $type, int $id, ?string $date, ?string $url, float $score): string
    {
        return hash('sha256', implode('|', [
            $benchmarkId,
            $type,
            $id,
            $date ?: 'undated',
            strtolower(trim($url ?: '')),
            number_format($score, 4, '.', ''),
        ]));
    }

    private function latestVerified(object $item, ?string $benchmarkClass = null): Collection
    {
        $query = BenchmarkResult::query()
            ->with('benchmark')
            ->where('benchmarkable_type', $item::class)
            ->where('benchmarkable_id', $item->id)
            ->where('verified', true)
            ->where('status', 'verified')
            ->whereHas('benchmark', function ($benchmarkQuery) use ($benchmarkClass) {
                $benchmarkQuery->where('is_active', true);
                if ($benchmarkClass !== null) {
                    $benchmarkQuery->where('benchmark_class', $benchmarkClass);
                }
            })
            ->orderByDesc('tested_at')
            ->orderByDesc('id');

        return $query->get()->unique('benchmark_id')->values();
    }
}
