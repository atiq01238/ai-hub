<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AiTest extends Model
{
    protected $fillable = [
        'name', 'slug', 'short_description', 'prompt', 'category', 'difficulty',
        'feature_id', 'use_case_id', 'criteria', 'scoring_weights', 'methodology',
        'expected_output', 'status', 'is_featured', 'is_verified', 'source_note',
        'seo_title', 'meta_description', 'published_at', 'last_verified_at',
    ];

    protected $casts = [
        'scoring_weights' => 'array',
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

            if (blank($test->scoring_weights)) {
                $test->scoring_weights = config('test_lab.default_weights', []);
            }
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

    public function scoreWeights(): array
    {
        $defaults = config('test_lab.default_weights', []);
        $weights = array_merge($defaults, $this->scoring_weights ?: []);

        return collect($weights)
            ->map(fn ($weight) => max(0, min(100, (int) $weight)))
            ->all();
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
        return $value ?: $this->name.' AI Model Test — Test Lab | AI Hub';
    }

    public function getSeoDescriptionAttribute(): string
    {
        return $this->meta_description
            ?: Str::limit($this->short_description ?: 'See the shared prompt, scoring methodology, evidence and side-by-side AI model results for '.$this->name.'.', 175, '');
    }
}
