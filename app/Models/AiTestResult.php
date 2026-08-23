<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AiTestResult extends Model
{
    protected $fillable = [
        'ai_test_id', 'ai_model_id', 'response_text', 'status', 'model_version',
        'latency_ms', 'input_tokens', 'output_tokens', 'estimated_cost_usd',
        'score_quality', 'score_accuracy', 'score_prompt_adherence',
        'score_creativity', 'score_speed', 'score_breakdown', 'overall_score', 'evaluator_summary',
        'source_label', 'source_url', 'evidence_path', 'is_verified', 'verification_level',
        'run_count', 'score_min', 'score_max', 'score_stddev', 'avg_latency_ms',
        'avg_estimated_cost_usd', 'tested_at', 'verified_at', 'exclude_reason',
    ];

    protected $casts = [
        'estimated_cost_usd' => 'float',
        'score_breakdown' => 'array',
        'overall_score' => 'float',
        'is_verified' => 'boolean',
        'run_count' => 'integer',
        'score_min' => 'float',
        'score_max' => 'float',
        'score_stddev' => 'float',
        'avg_latency_ms' => 'integer',
        'avg_estimated_cost_usd' => 'float',
        'tested_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (AiTestResult $result) {
            // V3 aggregates intentionally keep overall_score as the average of completed run
            // overalls. Recomputing from averaged criteria can change the answer whenever a
            // criterion is N/A in only some runs. Only legacy, zero-run records are derived here.
            if ((int) ($result->run_count ?? 0) === 0) {
                $scores = $result->scores();
                $rubric = $result->test?->evaluationRubric() ?: [];
                $weighted = 0.0;
                $weightTotal = 0;

                foreach ($rubric as $criterion) {
                    $key = $criterion['key'];
                    $score = $scores[$key] ?? null;
                    $weight = max(0, (int) ($criterion['weight'] ?? 0));
                    if ($score === null || $score === '' || $weight <= 0) continue;
                    $weighted += max(0, min(100, (float) $score)) * $weight;
                    $weightTotal += $weight;
                }

                if ($weightTotal > 0) {
                    $result->overall_score = round($weighted / $weightTotal, 1);
                }
            }

            $result->is_verified = in_array($result->verification_level, ['verified', 'high_confidence'], true);
            if ($result->is_verified && blank($result->verified_at)) {
                $result->verified_at = now();
            }
            if (! $result->is_verified) {
                $result->verified_at = null;
            }
            if ($result->status === 'complete' && blank($result->tested_at)) {
                $result->tested_at = now();
            }
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

    public function runs()
    {
        return $this->hasMany(AiTestRun::class, 'ai_test_result_id')->orderBy('run_number');
    }

    public function completedRuns()
    {
        return $this->hasMany(AiTestRun::class, 'ai_test_result_id')->where('status', 'complete');
    }

    public function scopeComplete(Builder $query): Builder
    {
        return $query->where('status', 'complete');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', 'complete')->whereIn('verification_level', ['verified', 'high_confidence']);
    }

    public function getEvidenceUrlAttribute(): ?string
    {
        return MediaUrl::resolve($this->evidence_path);
    }

    public function scores(): array
    {
        if (is_array($this->score_breakdown) && $this->score_breakdown !== []) {
            return $this->score_breakdown;
        }

        return array_filter([
            'quality' => $this->score_quality,
            // V3 aliases preserve old rows while matching the dynamic rubric keys.
            'correctness' => $this->score_accuracy,
            'accuracy' => $this->score_accuracy,
            'instruction_following' => $this->score_prompt_adherence,
            'prompt_adherence' => $this->score_prompt_adherence,
            'creativity' => $this->score_creativity,
            'speed' => $this->score_speed,
        ], fn ($value) => $value !== null);
    }

    public function verificationLabel(): string
    {
        return (string) (config('test_lab.verification_levels.'.$this->verification_level) ?: 'Unverified');
    }
}
