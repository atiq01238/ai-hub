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
        'score_creativity', 'score_speed', 'overall_score', 'evaluator_summary',
        'source_label', 'source_url', 'evidence_path', 'is_verified', 'tested_at',
        'verified_at', 'exclude_reason',
    ];

    protected $casts = [
        'estimated_cost_usd' => 'float',
        'overall_score' => 'float',
        'is_verified' => 'boolean',
        'tested_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (AiTestResult $result) {
            $criteria = config('test_lab.criteria', []);
            $weights = $result->test?->scoreWeights() ?: config('test_lab.default_weights', []);
            $weighted = 0.0;
            $weightTotal = 0;

            foreach ($criteria as $key => $definition) {
                $field = $definition['field'];
                $score = $result->{$field};
                $weight = max(0, (int) ($weights[$key] ?? 0));

                if ($score !== null && $weight > 0) {
                    $weighted += ((float) $score) * $weight;
                    $weightTotal += $weight;
                }
            }

            $result->overall_score = $weightTotal > 0 ? round($weighted / $weightTotal, 1) : 0;

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

    public function scopeComplete(Builder $query): Builder
    {
        return $query->where('status', 'complete');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', 'complete')->where('is_verified', true);
    }

    public function getEvidenceUrlAttribute(): ?string
    {
        return MediaUrl::resolve($this->evidence_path);
    }

    public function scores(): array
    {
        return collect(config('test_lab.criteria', []))->mapWithKeys(function ($definition, $key) {
            return [$key => $this->{$definition['field']}];
        })->all();
    }
}
