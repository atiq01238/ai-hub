<?php

namespace App\Services\Seo;

use App\Models\AiModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ModelContentService
{
    public function build(AiModel $model, Collection $relatedModels, Collection $latestNews, Collection $relatedComparisons): array
    {
        $provider = $model->company?->name;
        $features = $model->featureTerms->pluck('name')->filter()->unique()->values();
        $legacyCapabilities = collect($model->capabilities ?? [])->filter()->unique()->values();
        $capabilities = ($features->isNotEmpty() ? $features : $legacyCapabilities)->take(8)->values();
        $useCases = $model->useCaseTerms->pluck('name')->filter()->unique()->take(8)->values();
        $verifiedBenchmarks = $model->benchmarkResults
            ->filter(fn ($result) => (bool) $result->verified && $result->status === 'verified' && $result->benchmark)
            ->unique('benchmark_id')
            ->values();

        $intro = $this->sentenceExcerpt($model->overview ?: $model->capability_notes, 2, 420)
            ?: $model->name.' is an AI model'.($provider ? ' from '.$provider : '').' tracked in the AI Orbit model directory.';

        $profileSummary = $this->compose([
            $provider ? $model->name.' is provided by '.$provider.'.' : null,
            $model->version ? 'The listed version is '.$model->version.'.' : null,
            $model->release_date ? 'AI Orbit records its release date as '.$model->release_date->format('F j, Y').'.' : null,
            $model->context_window ? 'The current profile lists a '.$model->context_window.' context window.' : null,
            $model->tool ? 'It is linked to the '.$model->tool->name.' product profile on AI Orbit.' : null,
        ]);

        $capabilitySummary = null;
        if ($capabilities->isNotEmpty() || $useCases->isNotEmpty()) {
            $capabilitySummary = $this->compose([
                $capabilities->isNotEmpty()
                    ? 'Cataloged capabilities include '.$this->naturalList($capabilities->take(6)->all()).'.'
                    : null,
                $useCases->isNotEmpty()
                    ? 'AI Orbit maps the model to use cases including '.$this->naturalList($useCases->take(5)->all()).'.'
                    : null,
            ]);
        }

        $performanceSummary = null;
        if ($verifiedBenchmarks->isNotEmpty() || $model->benchmark_score !== null) {
            $names = $verifiedBenchmarks->pluck('benchmark.name')->filter()->take(4)->values();
            $performanceSummary = $this->compose([
                $model->benchmark_score !== null
                    ? 'The current AI Orbit benchmark composite is '.number_format((float) $model->benchmark_score, 1).'/100.'
                    : null,
                $verifiedBenchmarks->isNotEmpty()
                    ? $verifiedBenchmarks->count().' verified benchmark result'.($verifiedBenchmarks->count() === 1 ? ' is' : 's are').' linked to this profile'.($names->isNotEmpty() ? ', including '.$this->naturalList($names->all()) : '').'.'
                    : null,
            ]);
        }

        $pricingSummary = $this->pricingSummary($model);

        $ecosystemSummary = $this->compose([
            $relatedModels->isNotEmpty()
                ? 'Related model coverage includes '.$this->naturalList($relatedModels->pluck('name')->take(4)->all()).'.'
                : null,
            $relatedComparisons->isNotEmpty()
                ? 'AI Orbit currently links this model to '.$relatedComparisons->count().' published comparison'.($relatedComparisons->count() === 1 ? '' : 's').'.'
                : null,
            $latestNews->isNotEmpty()
                ? 'The profile also connects to current provider news when published intelligence is available.'
                : null,
        ]);

        $facts = collect([
            $provider ? ['label' => 'Provider', 'value' => $provider] : null,
            $model->version ? ['label' => 'Version', 'value' => $model->version] : null,
            $model->release_date ? ['label' => 'Released', 'value' => $model->release_date->format('M j, Y')] : null,
            $model->context_window ? ['label' => 'Context window', 'value' => $model->context_window] : null,
            $model->benchmark_score !== null ? ['label' => 'Benchmark composite', 'value' => number_format((float) $model->benchmark_score, 1).'/100'] : null,
            $verifiedBenchmarks->isNotEmpty() ? ['label' => 'Verified benchmarks', 'value' => (string) $verifiedBenchmarks->count()] : null,
        ])->filter()->values();

        return [
            'intro' => $intro,
            'profile_summary' => $profileSummary,
            'capability_summary' => $capabilitySummary,
            'performance_summary' => $performanceSummary,
            'pricing_summary' => $pricingSummary,
            'ecosystem_summary' => $ecosystemSummary,
            'facts' => $facts,
            'capabilities' => $capabilities,
            'use_cases' => $useCases,
        ];
    }

    private function pricingSummary(AiModel $model): ?string
    {
        $parts = [];

        if ($model->input_price_per_million !== null) {
            $parts[] = '$'.number_format((float) $model->input_price_per_million, 2).' per 1M input tokens';
        }
        if ($model->output_price_per_million !== null) {
            $parts[] = '$'.number_format((float) $model->output_price_per_million, 2).' per 1M output tokens';
        }

        if ($parts !== []) {
            return 'AI Orbit currently lists '.$this->naturalList($parts).'. Provider pricing can change, so production decisions should be checked against the linked official pricing source.';
        }

        if (filled($model->pricing_basis) || filled($model->pricing_summary)) {
            $profile = collect([
                filled($model->pricing_type_label) ? 'AI Orbit classifies the commercial model as '.$model->pricing_type_label.'.' : null,
                filled($model->pricing_basis) ? 'Verified pricing basis: '.$model->pricing_basis.'.' : null,
                filled($model->pricing_summary) ? $model->pricing_summary : null,
            ])->filter()->join(' ');

            return trim($profile).' Provider terms can change, so production decisions should be checked against the linked official source.';
        }

        if ($model->pricingSources->isNotEmpty()) {
            return 'Official pricing sources are monitored for this model, but a current generic token price is not displayed in the profile. Check the provider source for the applicable commercial terms.';
        }

        return null;
    }

    private function compose(array $parts): ?string
    {
        $text = collect($parts)
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->join(' ');

        return $text !== '' ? $text : null;
    }

    private function sentenceExcerpt(?string $value, int $maxSentences, int $maxChars): ?string
    {
        $text = trim(strip_tags((string) $value));
        if ($text === '') {
            return null;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $excerpt = trim(implode(' ', array_slice($sentences, 0, max(1, $maxSentences))));

        return Str::limit($excerpt ?: $text, $maxChars, '');
    }

    private function naturalList(array $values): string
    {
        $values = array_values(array_filter(array_map(fn ($value) => trim((string) $value), $values)));
        $count = count($values);

        if ($count === 0) return '';
        if ($count === 1) return $values[0];
        if ($count === 2) return $values[0].' and '.$values[1];

        return implode(', ', array_slice($values, 0, -1)).', and '.$values[$count - 1];
    }
}
