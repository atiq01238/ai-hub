<?php

namespace App\Services;

use App\Models\Benchmark;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BenchmarkSemanticsService
{
    /**
     * Infer a semantic class without changing the benchmark.
     * The service is deliberately conservative: uncertain records remain unclassified.
     *
     * @return array{class:string,label:string,confidence:string,reason:string}
     */
    public function infer(Benchmark $benchmark): array
    {
        $benchmark->loadMissing('results:id,benchmark_id,source_type,source_name,source_url');

        $name = Str::lower((string) $benchmark->name);
        $category = Str::lower((string) $benchmark->category);
        $description = Str::lower((string) $benchmark->description);
        $officialUrl = Str::lower((string) $benchmark->official_url);
        $methodologyUrl = Str::lower((string) $benchmark->methodology_url);
        $results = $benchmark->results;

        $sourceTypes = $results->pluck('source_type')->filter()->map(fn ($value) => Str::lower((string) $value))->unique();
        $sourceNames = Str::lower($results->pluck('source_name')->filter()->implode(' | '));
        $sourceUrls = Str::lower($results->pluck('source_url')->filter()->implode(' | '));
        $haystack = implode(' | ', [$name, $category, $description, $officialUrl, $methodologyUrl, $sourceNames, $sourceUrls]);

        if ($this->containsAny($haystack, [
            'product experience', 'user rating', 'user review', 'g2.com', 'g2 verified',
            'gartner peer insights', 'capterra', 'trustradius', 'peer review rating',
        ]) || $sourceTypes->contains('community')) {
            return $this->result(Benchmark::CLASS_PRODUCT_EXPERIENCE, 'high', 'Review/user-experience evidence detected.');
        }

        if ($this->containsAny($haystack, ['ai orbit tested', 'ai orbit test lab', 'ai-orbit tested', 'ai orbit benchmark'])
            || $sourceTypes->contains('ai_hub')) {
            return $this->result(Benchmark::CLASS_AI_ORBIT_TESTED, 'high', 'AI Orbit first-party testing evidence detected.');
        }

        if ($this->isKnownTechnicalBenchmark($name)
            || $this->containsAny($category, [
                'knowledge & reasoning', 'reasoning', 'coding', 'mathematics', 'software engineering',
                'accuracy', 'latency', 'throughput', 'performance', 'retrieval', 'vision', 'speech',
            ])) {
            return $this->result(Benchmark::CLASS_TECHNICAL, 'high', 'Technical benchmark name/category detected.');
        }

        if ($this->containsAny($haystack, [
            'vals.ai', 'vlair', 'academic study', 'research study', 'research report', 'independent study',
        ])) {
            return $this->result(Benchmark::CLASS_INDEPENDENT_RESEARCH, 'high', 'Independent research/evaluation source detected.');
        }

        if ($sourceTypes->contains('research_paper')) {
            return $this->result(Benchmark::CLASS_INDEPENDENT_RESEARCH, 'medium', 'Research-paper source type detected.');
        }

        if ($sourceTypes->contains('benchmark_org') && $results->isNotEmpty()) {
            return $this->result(Benchmark::CLASS_TECHNICAL, 'medium', 'Benchmark-organization source detected.');
        }

        return $this->result(Benchmark::CLASS_UNCLASSIFIED, 'low', 'No safe semantic classification rule matched.');
    }

    /**
     * Used by imports when a new definition does not explicitly provide benchmark_class.
     */
    public function inferFromMetadata(array $metadata): array
    {
        $benchmark = new Benchmark([
            'name' => $metadata['name'] ?? '',
            'category' => $metadata['category'] ?? '',
            'description' => $metadata['description'] ?? '',
            'official_url' => $metadata['official_url'] ?? '',
            'methodology_url' => $metadata['methodology_url'] ?? '',
        ]);

        $benchmark->setRelation('results', collect([(object) [
            'source_type' => $metadata['source_type'] ?? '',
            'source_name' => $metadata['source_name'] ?? '',
            'source_url' => $metadata['source_url'] ?? '',
        ]]));

        return $this->infer($benchmark);
    }

    public function normalize(?string $class): string
    {
        $value = Str::of((string) $class)->lower()->replace([' ', '-'], '_')->toString();

        return in_array($value, Benchmark::CLASSES, true) ? $value : Benchmark::CLASS_UNCLASSIFIED;
    }

    public function isCompositeEligible(string $class): bool
    {
        return in_array($class, [Benchmark::CLASS_TECHNICAL, Benchmark::CLASS_AI_ORBIT_TESTED], true);
    }

    private function result(string $class, string $confidence, string $reason): array
    {
        return [
            'class' => $class,
            'label' => Benchmark::classLabel($class),
            'confidence' => $confidence,
            'reason' => $reason,
        ];
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($haystack, Str::lower($needle))) {
                return true;
            }
        }

        return false;
    }

    private function isKnownTechnicalBenchmark(string $name): bool
    {
        return $this->containsAny($name, [
            'mmlu', 'mmmu', 'humaneval', 'human eval', 'gpqa', 'swe-bench', 'swe bench', 'math',
            'livebench', 'big-bench', 'big bench', 'gsm8k', 'hellaswag', 'arc challenge',
            'truthfulqa', 'mbpp', 'aider polyglot', 'terminal-bench', 'terminal bench',
        ]);
    }
}
