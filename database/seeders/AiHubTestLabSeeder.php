<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\AiTest;
use App\Models\AiTestResult;
use Illuminate\Database\Seeder;

class AiHubTestLabSeeder extends Seeder
{
    public function run(): void
    {
        $test = AiTest::updateOrCreate(['name'=>'AI Reasoning Challenge'], [
            'prompt'=>'Solve a multi-step reasoning task and explain the final answer concisely.',
            'category'=>'Reasoning',
            'criteria'=>'Accuracy, reasoning quality, prompt adherence and speed',
            'expected_output'=>'A correct, concise answer with clear reasoning.',
        ]);

        foreach (AiModel::orderByDesc('benchmark_score')->take(4)->get() as $i => $model) {
            $base = max(70, 96 - $i * 2);
            AiTestResult::updateOrCreate(['ai_test_id'=>$test->id,'ai_model_id'=>$model->id], [
                'response_text'=>'Seeded test result for frontend preview and admin validation.',
                'score_quality'=>$base,
                'score_accuracy'=>$base-1,
                'score_prompt_adherence'=>$base-2,
                'score_creativity'=>$base-3,
                'score_speed'=>$base-4,
                'overall_score'=>round($base-2,1),
            ]);
        }
    }
}
