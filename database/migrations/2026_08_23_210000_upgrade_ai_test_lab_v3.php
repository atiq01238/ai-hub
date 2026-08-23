<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_tests', function (Blueprint $table) {
            $table->string('test_type', 40)->default('reasoning')->after('category')->index();
            $table->json('evaluation_rubric')->nullable()->after('scoring_weights');
            $table->string('run_mode', 20)->default('quick')->after('evaluation_rubric')->index();
            $table->unsignedTinyInteger('required_runs')->default(1)->after('run_mode');
            $table->timestamp('prompt_locked_at')->nullable()->after('required_runs');
        });

        Schema::table('ai_test_results', function (Blueprint $table) {
            $table->json('score_breakdown')->nullable()->after('score_speed');
            $table->string('verification_level', 30)->default('unverified')->after('is_verified')->index();
            $table->unsignedTinyInteger('run_count')->default(0)->after('verification_level');
            $table->decimal('score_min', 4, 1)->nullable()->after('run_count');
            $table->decimal('score_max', 4, 1)->nullable()->after('score_min');
            $table->decimal('score_stddev', 5, 2)->nullable()->after('score_max');
            $table->unsignedInteger('avg_latency_ms')->nullable()->after('score_stddev');
            $table->decimal('avg_estimated_cost_usd', 12, 6)->nullable()->after('avg_latency_ms');
        });

        Schema::create('ai_test_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_test_result_id')->constrained('ai_test_results')->cascadeOnDelete();
            $table->unsignedTinyInteger('run_number')->default(1);
            $table->longText('response_text')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('model_version', 120)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->decimal('estimated_cost_usd', 12, 6)->nullable();
            $table->json('score_breakdown')->nullable();
            $table->decimal('overall_score', 4, 1)->nullable();
            $table->text('evaluator_summary')->nullable();
            $table->string('source_label', 160)->nullable();
            $table->text('source_url')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('verification_level', 30)->default('unverified')->index();
            $table->timestamp('tested_at')->nullable()->index();
            $table->timestamp('verified_at')->nullable();
            $table->text('exclude_reason')->nullable();
            $table->timestamps();

            $table->unique(['ai_test_result_id', 'run_number']);
        });

        $typeMap = [
            'Coding' => 'coding', 'Writing' => 'writing', 'Research' => 'research', 'Math' => 'objective',
            'Long Context' => 'long_context', 'Multimodal' => 'multimodal', 'Image' => 'multimodal',
            'Audio' => 'multimodal', 'Video' => 'multimodal', 'Instruction Following' => 'instruction_following',
            'Safety & Reliability' => 'safety_reliability', 'Reasoning' => 'reasoning', 'Text' => 'writing',
        ];

        $legacyDefs = config('test_lab.criteria', []);
        $rubricLibrary = config('test_lab.rubric_library', []);
        $legacyKeyMap = [
            'quality' => 'quality',
            'accuracy' => 'correctness',
            'prompt_adherence' => 'instruction_following',
            'creativity' => 'creativity',
            'speed' => 'speed',
        ];
        DB::table('ai_tests')->orderBy('id')->get()->each(function ($test) use ($typeMap, $legacyDefs, $rubricLibrary, $legacyKeyMap) {
            $weights = json_decode((string) $test->scoring_weights, true) ?: config('test_lab.default_weights', []);
            $rubric = [];
            foreach ($legacyDefs as $key => $definition) {
                $weight = max(0, (int) ($weights[$key] ?? 0));
                if ($weight <= 0) continue;
                $newKey = $legacyKeyMap[$key] ?? $key;
                $newDefinition = $rubricLibrary[$newKey] ?? $definition;
                $rubric[] = [
                    'key' => $newKey,
                    'label' => $newDefinition['label'],
                    'description' => $newDefinition['description'],
                    'weight' => $weight,
                    'auto_strategy' => $newDefinition['auto_strategy'] ?? 'manual',
                ];
            }

            DB::table('ai_tests')->where('id', $test->id)->update([
                'category' => $test->category === 'Text' ? 'Writing' : $test->category,
                'test_type' => $typeMap[$test->category] ?? 'reasoning',
                'evaluation_rubric' => json_encode($rubric),
                'run_mode' => 'quick',
                'required_runs' => 1,
                'prompt_locked_at' => DB::table('ai_test_results')->where('ai_test_id', $test->id)->whereNotNull('response_text')->exists()
                    ? ($test->updated_at ?: now()) : null,
            ]);
        });

        DB::table('ai_test_results')->orderBy('id')->get()->each(function ($result) {
            $scores = array_filter([
                'quality' => $result->score_quality,
                'correctness' => $result->score_accuracy,
                'instruction_following' => $result->score_prompt_adherence,
                'creativity' => $result->score_creativity,
                'speed' => $result->score_speed,
            ], fn ($value) => $value !== null);

            $level = $result->is_verified ? 'verified' : ($result->status === 'complete' ? 'reviewed' : 'unverified');

            $runId = DB::table('ai_test_runs')->insertGetId([
                'ai_test_result_id' => $result->id,
                'run_number' => 1,
                'response_text' => $result->response_text,
                'status' => $result->status,
                'model_version' => $result->model_version,
                'latency_ms' => $result->latency_ms,
                'input_tokens' => $result->input_tokens,
                'output_tokens' => $result->output_tokens,
                'estimated_cost_usd' => $result->estimated_cost_usd,
                'score_breakdown' => $scores ? json_encode($scores) : null,
                'overall_score' => $result->status === 'complete' ? $result->overall_score : null,
                'evaluator_summary' => $result->evaluator_summary,
                'source_label' => $result->source_label,
                'source_url' => $result->source_url,
                'evidence_path' => $result->evidence_path,
                'verification_level' => $level,
                'tested_at' => $result->tested_at,
                'verified_at' => $result->verified_at,
                'exclude_reason' => $result->exclude_reason,
                'created_at' => $result->created_at ?: now(),
                'updated_at' => $result->updated_at ?: now(),
            ]);

            DB::table('ai_test_results')->where('id', $result->id)->update([
                'score_breakdown' => $scores ? json_encode($scores) : null,
                'verification_level' => $level,
                'run_count' => $result->status === 'complete' ? 1 : 0,
                'score_min' => $result->status === 'complete' ? $result->overall_score : null,
                'score_max' => $result->status === 'complete' ? $result->overall_score : null,
                'score_stddev' => $result->status === 'complete' ? 0 : null,
                'avg_latency_ms' => $result->latency_ms,
                'avg_estimated_cost_usd' => $result->estimated_cost_usd,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_test_runs');

        Schema::table('ai_test_results', function (Blueprint $table) {
            $table->dropIndex(['verification_level']);
            $table->dropColumn([
                'score_breakdown', 'verification_level', 'run_count', 'score_min', 'score_max',
                'score_stddev', 'avg_latency_ms', 'avg_estimated_cost_usd',
            ]);
        });

        Schema::table('ai_tests', function (Blueprint $table) {
            $table->dropIndex(['test_type']);
            $table->dropIndex(['run_mode']);
            $table->dropColumn(['test_type', 'evaluation_rubric', 'run_mode', 'required_runs', 'prompt_locked_at']);
        });
    }
};
