<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('benchmarks') || ! Schema::hasTable('benchmark_results')) {
            return;
        }

        DB::transaction(function (): void {
            $canonical = DB::table('benchmarks')->where('slug', 'mmlu-pro')->first();
            $duplicate = DB::table('benchmarks')->where('slug', 'mmlu-pro-2')->first();

            if (! $canonical || ! $duplicate || (int) $canonical->id === (int) $duplicate->id) {
                return;
            }

            $canonicalId = (int) $canonical->id;
            $duplicateId = (int) $duplicate->id;

            // Only merge the known punctuation-only duplicate. Do not collapse
            // genuinely versioned/variant benchmarks.
            $normalize = static fn (?string $value): string => preg_replace('/[^a-z0-9]+/', '', strtolower((string) $value)) ?? '';
            if ($normalize($canonical->name) !== 'mmlupro' || $normalize($duplicate->name) !== 'mmlupro') {
                return;
            }

            $results = DB::table('benchmark_results')->where('benchmark_id', $duplicateId)->orderBy('id')->get();

            foreach ($results as $result) {
                $testedAt = $result->tested_at ? substr((string) $result->tested_at, 0, 10) : null;
                $sourceUrl = strtolower(trim((string) ($result->source_url ?? '')));
                $fingerprint = hash('sha256', implode('|', [
                    $canonicalId,
                    (string) $result->benchmarkable_type,
                    (int) $result->benchmarkable_id,
                    $testedAt ?: 'undated',
                    $sourceUrl,
                    number_format((float) $result->score, 4, '.', ''),
                ]));

                $alreadyExists = DB::table('benchmark_results')
                    ->where('fingerprint', $fingerprint)
                    ->where('id', '<>', $result->id)
                    ->exists();

                if ($alreadyExists) {
                    DB::table('benchmark_results')->where('id', $result->id)->delete();
                    continue;
                }

                DB::table('benchmark_results')->where('id', $result->id)->update([
                    'benchmark_id' => $canonicalId,
                    'fingerprint' => $fingerprint,
                    'updated_at' => now(),
                ]);
            }

            DB::table('benchmarks')->where('id', $duplicateId)->delete();

            // Canonical benchmark-level metadata belongs to the benchmark itself;
            // provider/model evidence remains on each BenchmarkResult source_url.
            DB::table('benchmarks')->where('id', $canonicalId)->update([
                'name' => 'MMLU-Pro',
                'slug' => 'mmlu-pro',
                'category' => 'Reasoning',
                'benchmark_class' => 'technical_performance',
                'entity_scope' => 'model',
                'metric_type' => 'percentage',
                'unit' => '%',
                'min_score' => 0,
                'max_score' => 100,
                'higher_is_better' => true,
                'official_url' => 'https://github.com/TIGER-AI-Lab/MMLU-Pro',
                'methodology_url' => 'https://github.com/TIGER-AI-Lab/MMLU-Pro',
                'description' => 'MMLU-Pro is a robust multi-task language-understanding benchmark with a stronger emphasis on challenging reasoning questions.',
                'is_active' => true,
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        // Intentional no-op: this migration consolidates duplicate benchmark
        // identity and result history. Recreating the duplicate would reintroduce
        // the SEO/data-integrity defect and cannot be done losslessly.
    }
};
