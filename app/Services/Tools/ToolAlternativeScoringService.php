<?php

namespace App\Services\Tools;

use App\Models\Benchmark;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ToolAlternativeScoringService
{
    public function __construct(private readonly ToolCommercialProfileService $commercial) {}

    public function alternatives(Tool $tool, int $limit = 4): Collection
    {
        $tool->loadMissing(['featureTerms:id','useCaseTerms:id','tagTerms:id','platformTerms:id','pricingPlans','benchmarkResults.benchmark']);
        $featureIds = $tool->featureTerms->pluck('id');
        $useCaseIds = $tool->useCaseTerms->pluck('id');

        $query = Tool::query()
            ->with(['company','category','featureTerms:id','useCaseTerms:id','tagTerms:id','platformTerms:id','pricingPlans','benchmarkResults' => fn ($q) => $q->with('benchmark')->where('verified', true)->where('status', 'verified')->latest('tested_at')->latest('id')])
            ->where('status', 'published')
            ->where('id', '!=', $tool->id);

        if ($tool->category_id || $featureIds->isNotEmpty() || $useCaseIds->isNotEmpty()) {
            $query->where(function (Builder $q) use ($tool, $featureIds, $useCaseIds) {
                if ($tool->category_id) $q->orWhere('category_id', $tool->category_id);
                if ($featureIds->isNotEmpty()) $q->orWhereHas('featureTerms', fn ($features) => $features->whereIn('features.id', $featureIds));
                if ($useCaseIds->isNotEmpty()) $q->orWhereHas('useCaseTerms', fn ($cases) => $cases->whereIn('use_cases.id', $useCaseIds));
            });
        }

        $candidates = $query->orderByDesc('popularity')->limit(80)->get();
        if ($candidates->count() < $limit) {
            $fallback = Tool::query()
                ->with(['company','category','featureTerms:id','useCaseTerms:id','tagTerms:id','platformTerms:id','pricingPlans','benchmarkResults' => fn ($q) => $q->with('benchmark')->where('verified', true)->where('status', 'verified')->latest('tested_at')->latest('id')])
                ->where('status','published')->where('id', '!=', $tool->id)
                ->whereNotIn('id', $candidates->pluck('id'))
                ->orderByDesc('popularity')->limit(20)->get();
            $candidates = $candidates->concat($fallback);
        }

        return $candidates
            ->map(function (Tool $candidate) use ($tool) {
                [$score, $reasons] = $this->score($tool, $candidate);
                $candidate->setAttribute('alternative_match_score', $score);
                $candidate->setAttribute('alternative_match_reasons', $reasons);
                return $candidate;
            })
            ->filter(fn (Tool $candidate) => (float) $candidate->alternative_match_score > 0)
            ->sortByDesc('alternative_match_score')
            ->take($limit)
            ->values();
    }

    public function score(Tool $base, Tool $candidate): array
    {
        $useCases = $this->overlap($base->useCaseTerms->pluck('id'), $candidate->useCaseTerms->pluck('id'));
        $features = $this->overlap($base->featureTerms->pluck('id'), $candidate->featureTerms->pluck('id'));
        $pricing = $this->overlap(collect($this->commercial->expectedLabels($base)), collect($this->commercial->expectedLabels($candidate)));
        $platforms = $this->overlap($base->platformTerms->pluck('id'), $candidate->platformTerms->pluck('id'));
        $tags = $this->overlap($base->tagTerms->pluck('id'), $candidate->tagTerms->pluck('id'));
        $category = 0.0;
        if ($base->subcategory_id && (int) $base->subcategory_id === (int) $candidate->subcategory_id) $category = 1.0;
        elseif ($base->category_id && (int) $base->category_id === (int) $candidate->category_id) $category = .65;

        $benchmark = 0.0;
        $baseClass = $this->primaryClass($base);
        $candidateClass = $this->primaryClass($candidate);
        if ($baseClass && $baseClass === $candidateClass && $base->benchmark_score !== null && $candidate->benchmark_score !== null) {
            $benchmark = max(0, 1 - (abs((float) $base->benchmark_score - (float) $candidate->benchmark_score) / 100));
        }

        $score = ($useCases * 30) + ($features * 25) + ($pricing * 15) + ($platforms * 10) + ($tags * 10) + ($category * 5) + ($benchmark * 5);
        $reasons = [];
        if ($useCases >= .34) $reasons[] = 'similar use cases';
        if ($features >= .34) $reasons[] = 'capability overlap';
        if ($pricing > 0) $reasons[] = 'similar pricing model';
        if ($platforms >= .34) $reasons[] = 'platform match';
        if ($category > 0) $reasons[] = $category === 1.0 ? 'same subcategory' : 'same category';
        if ($benchmark > 0) $reasons[] = 'comparable benchmark profile';

        return [round(min(100, max(0, $score)), 1), array_slice($reasons, 0, 3)];
    }

    private function primaryClass(Tool $tool): ?string
    {
        $results = $tool->relationLoaded('benchmarkResults')
            ? $tool->benchmarkResults
            : $tool->benchmarkResults()->with('benchmark')->where('verified', true)->where('status', 'verified')->get();
        $classes = $results->filter(fn ($result) => $result->benchmark)->pluck('benchmark.benchmark_class')->filter()->unique();
        foreach ([Benchmark::CLASS_TECHNICAL, Benchmark::CLASS_AI_ORBIT_TESTED] as $class) {
            if ($classes->contains($class)) return $class;
        }
        return null;
    }

    private function overlap(Collection $a, Collection $b): float
    {
        $a = $a->filter()->map(fn ($v) => is_string($v) ? mb_strtolower($v) : $v)->unique();
        $b = $b->filter()->map(fn ($v) => is_string($v) ? mb_strtolower($v) : $v)->unique();
        if ($a->isEmpty() || $b->isEmpty()) return 0.0;
        $intersection = $a->intersect($b)->count();
        $union = $a->merge($b)->unique()->count();
        return $union ? $intersection / $union : 0.0;
    }
}
