<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiTestResult extends Model
{
    protected $fillable = [
        'ai_test_id', 'ai_model_id', 'response_text',
        'score_quality', 'score_accuracy', 'score_prompt_adherence',
        'score_creativity', 'score_speed', 'overall_score',
    ];

    protected static function booted(): void
    {
        // Recalculate the overall score automatically whenever a result is
        // saved, so nobody has to average the 5 sub-scores by hand.
        static::saving(function (AiTestResult $result) {
            $scores = array_filter([
                $result->score_quality, $result->score_accuracy, $result->score_prompt_adherence,
                $result->score_creativity, $result->score_speed,
            ], fn ($v) => $v !== null);

            $result->overall_score = count($scores) ? round(array_sum($scores) / count($scores), 1) : 0;
        });
    }

    public function test()
    {
        return $this->belongsTo(AiTest::class, 'ai_test_id');
    }

    public function model()
    {
        return $this->belongsTo(AiModel::class, 'ai_model_id');
    }
}
