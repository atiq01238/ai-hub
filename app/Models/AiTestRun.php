<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AiTestRun extends Model
{
    protected $fillable = [
        'ai_test_result_id', 'run_number', 'response_text', 'status', 'model_version',
        'latency_ms', 'input_tokens', 'output_tokens', 'estimated_cost_usd', 'score_breakdown',
        'overall_score', 'evaluator_summary', 'source_label', 'source_url', 'evidence_path',
        'verification_level', 'tested_at', 'verified_at', 'exclude_reason',
    ];

    protected $casts = [
        'run_number' => 'integer',
        'estimated_cost_usd' => 'float',
        'score_breakdown' => 'array',
        'overall_score' => 'float',
        'tested_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (AiTestRun $run) {
            $scores = is_array($run->score_breakdown) ? $run->score_breakdown : [];
            $rubric = $run->result?->test?->evaluationRubric() ?: [];
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

            $run->overall_score = $weightTotal > 0 ? round($weighted / $weightTotal, 1) : null;

            if ($run->status === 'complete' && blank($run->tested_at)) {
                $run->tested_at = now();
            }

            if (in_array($run->verification_level, ['verified', 'high_confidence'], true)) {
                $run->verified_at ??= now();
            } else {
                $run->verified_at = null;
            }
        });
    }

    public function result()
    {
        return $this->belongsTo(AiTestResult::class, 'ai_test_result_id');
    }

    public function scopeComplete(Builder $query): Builder
    {
        return $query->where('status', 'complete');
    }

    public function getEvidenceUrlAttribute(): ?string
    {
        return MediaUrl::resolve($this->evidence_path);
    }

    public function verificationLabel(): string
    {
        return (string) (config('test_lab.verification_levels.'.$this->verification_level) ?: 'Unverified');
    }
}
