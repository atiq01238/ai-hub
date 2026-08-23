<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_tests', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->string('short_description', 500)->nullable()->after('slug');
            $table->string('difficulty', 20)->default('standard')->after('category')->index();
            $table->foreignId('feature_id')->nullable()->after('difficulty')->constrained('features')->nullOnDelete();
            $table->foreignId('use_case_id')->nullable()->after('feature_id')->constrained('use_cases')->nullOnDelete();
            $table->json('scoring_weights')->nullable()->after('criteria');
            $table->text('methodology')->nullable()->after('scoring_weights');
            $table->string('status', 20)->default('draft')->after('expected_output')->index();
            $table->boolean('is_featured')->default(false)->after('status')->index();
            $table->boolean('is_verified')->default(false)->after('is_featured')->index();
            $table->string('source_note', 500)->nullable()->after('is_verified');
            $table->string('seo_title', 80)->nullable()->after('source_note');
            $table->string('meta_description', 180)->nullable()->after('seo_title');
            $table->timestamp('published_at')->nullable()->after('meta_description')->index();
            $table->timestamp('last_verified_at')->nullable()->after('published_at');
        });

        $seen = [];
        DB::table('ai_tests')->orderBy('id')->get(['id', 'name', 'created_at'])->each(function ($test) use (&$seen) {
            $base = Str::slug((string) $test->name) ?: 'test-'.$test->id;
            $slug = $base;
            if (isset($seen[$slug])) {
                $slug .= '-'.$test->id;
            }
            $seen[$slug] = true;

            $hasEvidence = DB::table('ai_test_results')
                ->where('ai_test_id', $test->id)
                ->where(function ($q) {
                    $q->whereNotNull('score_quality')
                        ->orWhereNotNull('score_accuracy')
                        ->orWhereNotNull('score_prompt_adherence')
                        ->orWhereNotNull('score_creativity')
                        ->orWhereNotNull('score_speed');
                })->count() >= 2;

            DB::table('ai_tests')->where('id', $test->id)->update([
                'slug' => $slug,
                'scoring_weights' => json_encode(config('test_lab.default_weights', [
                    'quality' => 25,
                    'accuracy' => 30,
                    'prompt_adherence' => 25,
                    'creativity' => 10,
                    'speed' => 10,
                ])),
                'status' => $hasEvidence ? 'published' : 'draft',
                'published_at' => $hasEvidence ? ($test->created_at ?: now()) : null,
            ]);
        });

        Schema::table('ai_tests', function (Blueprint $table) {
            $table->unique('slug');
        });

        Schema::table('ai_test_results', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->after('response_text')->index();
            $table->string('model_version', 120)->nullable()->after('status');
            $table->unsignedInteger('latency_ms')->nullable()->after('model_version');
            $table->unsignedInteger('input_tokens')->nullable()->after('latency_ms');
            $table->unsignedInteger('output_tokens')->nullable()->after('input_tokens');
            $table->decimal('estimated_cost_usd', 12, 6)->nullable()->after('output_tokens');
            $table->text('evaluator_summary')->nullable()->after('overall_score');
            $table->string('source_label', 160)->nullable()->after('evaluator_summary');
            $table->text('source_url')->nullable()->after('source_label');
            $table->string('evidence_path')->nullable()->after('source_url');
            $table->boolean('is_verified')->default(false)->after('evidence_path')->index();
            $table->timestamp('tested_at')->nullable()->after('is_verified')->index();
            $table->timestamp('verified_at')->nullable()->after('tested_at');
            $table->text('exclude_reason')->nullable()->after('verified_at');
        });

        $weights = config('test_lab.default_weights', [
            'quality' => 25, 'accuracy' => 30, 'prompt_adherence' => 25, 'creativity' => 10, 'speed' => 10,
        ]);
        $scoreFields = [
            'quality' => 'score_quality', 'accuracy' => 'score_accuracy',
            'prompt_adherence' => 'score_prompt_adherence', 'creativity' => 'score_creativity', 'speed' => 'score_speed',
        ];

        DB::table('ai_test_results')->orderBy('id')->get()->each(function ($result) use ($weights, $scoreFields) {
            $weighted = 0.0;
            $weightTotal = 0;
            foreach ($scoreFields as $key => $field) {
                $score = $result->{$field};
                $weight = (int) ($weights[$key] ?? 0);
                if ($score !== null && $weight > 0) {
                    $weighted += ((float) $score) * $weight;
                    $weightTotal += $weight;
                }
            }
            $hasScore = $weightTotal > 0;

            DB::table('ai_test_results')->where('id', $result->id)->update([
                'status' => $hasScore ? 'complete' : 'pending',
                'overall_score' => $hasScore ? round($weighted / $weightTotal, 1) : 0,
                'tested_at' => $hasScore ? ($result->updated_at ?: $result->created_at ?: now()) : null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('ai_tests', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropConstrainedForeignId('feature_id');
            $table->dropConstrainedForeignId('use_case_id');
            $table->dropColumn([
                'slug', 'short_description', 'difficulty', 'scoring_weights', 'methodology',
                'status', 'is_featured', 'is_verified', 'source_note', 'seo_title',
                'meta_description', 'published_at', 'last_verified_at',
            ]);
        });

        Schema::table('ai_test_results', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'model_version', 'latency_ms', 'input_tokens', 'output_tokens',
                'estimated_cost_usd', 'evaluator_summary', 'source_label', 'source_url',
                'evidence_path', 'is_verified', 'tested_at', 'verified_at', 'exclude_reason',
            ]);
        });
    }
};
