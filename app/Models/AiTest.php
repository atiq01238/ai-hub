<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AiTest extends Model
{
    protected $fillable = [
        'name', 'slug', 'short_description', 'prompt', 'category', 'test_type', 'difficulty',
        'feature_id', 'use_case_id', 'criteria', 'scoring_weights', 'evaluation_rubric',
        'run_mode', 'required_runs', 'prompt_locked_at', 'methodology', 'expected_output',
        'status', 'is_featured', 'is_verified', 'source_note', 'seo_title', 'meta_description',
        'published_at', 'last_verified_at',
    ];

    protected $casts = [
        'scoring_weights' => 'array',
        'evaluation_rubric' => 'array',
        'required_runs' => 'integer',
        'prompt_locked_at' => 'datetime',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'published_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (AiTest $test) {
            if (blank($test->slug) || $test->isDirty('name')) {
                $base = Str::slug((string) $test->name) ?: 'ai-test';
                $slug = $base;
                $counter = 2;

                while (static::query()->where('slug', $slug)->when($test->exists, fn ($q) => $q->whereKeyNot($test->getKey()))->exists()) {
                    $slug = $base.'-'.$counter++;
                }

                $test->slug = $slug;
            }

            $test->test_type = $test->test_type ?: 'reasoning';
            $test->run_mode = $test->run_mode ?: 'quick';
            $test->required_runs = max(1, min(10, (int) ($test->required_runs ?: 1)));

            if (blank($test->evaluation_rubric)) {
                $test->evaluation_rubric = static::rubricForType($test->test_type);
            }

            $test->scoring_weights = collect($test->evaluationRubric())
                ->mapWithKeys(fn ($item) => [$item['key'] => (int) $item['weight']])
                ->all();
        });
    }

    public function results()
    {
        return $this->hasMany(AiTestResult::class);
    }

    public function completedResults()
    {
        return $this->hasMany(AiTestResult::class)->where('status', 'complete');
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    public function useCase()
    {
        return $this->belongsTo(UseCase::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function evaluationRubric(): array
    {
        $rubric = is_array($this->evaluation_rubric) && $this->evaluation_rubric !== []
            ? $this->evaluation_rubric
            : static::rubricForType($this->test_type ?: 'reasoning');

        $library = config('test_lab.rubric_library', []);

        return collect($rubric)
            ->map(function ($item, $index) use ($library) {
                if (! is_array($item)) return null;
                $key = (string) ($item['key'] ?? (is_string($index) ? $index : ''));
                if ($key === '') return null;
                $base = $library[$key] ?? [];
                $weight = max(0, min(100, (int) ($item['weight'] ?? 0)));
                if ($weight <= 0) return null;

                return [
                    'key' => $key,
                    'label' => (string) ($item['label'] ?? $base['label'] ?? Str::headline($key)),
                    'description' => (string) ($item['description'] ?? $base['description'] ?? ''),
                    'weight' => $weight,
                    'auto_strategy' => (string) ($item['auto_strategy'] ?? $base['auto_strategy'] ?? 'manual'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public static function rubricForType(string $type): array
    {
        $types = config('test_lab.test_types', []);
        $library = config('test_lab.rubric_library', []);
        $weights = $types[$type]['rubric'] ?? ($types['reasoning']['rubric'] ?? []);

        return collect($weights)->map(function ($weight, $key) use ($library) {
            $base = $library[$key] ?? [];
            return [
                'key' => $key,
                'label' => $base['label'] ?? Str::headline($key),
                'description' => $base['description'] ?? '',
                'weight' => (int) $weight,
                'auto_strategy' => $base['auto_strategy'] ?? 'manual',
            ];
        })->values()->all();
    }

    public function scoreWeights(): array
    {
        return collect($this->evaluationRubric())
            ->mapWithKeys(fn ($item) => [$item['key'] => (int) $item['weight']])
            ->all();
    }

    public function testTypeLabel(): string
    {
        return (string) (config('test_lab.test_types.'.$this->test_type.'.label') ?: Str::headline((string) $this->test_type));
    }

    public function runModeLabel(): string
    {
        return (string) (config('test_lab.run_modes.'.$this->run_mode.'.label') ?: Str::headline((string) $this->run_mode));
    }

    public function isPublishable(): bool
    {
        return $this->completedResults()->count() >= 2;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return static::query()
            ->where(function ($query) use ($value) {
                $query->where('slug', $value);
                if (ctype_digit((string) $value)) {
                    $query->orWhere('id', (int) $value);
                }
            })
            ->first();
    }

    public function getSeoTitleAttribute($value): string
    {
        return $value ?: $this->name.' AI Model Test — Test Lab | AI Orbit';
    }

    public function getSeoDescriptionAttribute(): string
    {
        return $this->meta_description
            ?: Str::limit($this->short_description ?: 'See the shared prompt, scoring methodology, evidence and side-by-side AI model results for '.$this->name.'.', 175, '');
    }
}
