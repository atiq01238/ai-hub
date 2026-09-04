<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Benchmark extends Model
{
    public const CLASS_TECHNICAL = 'technical_performance';
    public const CLASS_PRODUCT_EXPERIENCE = 'product_experience';
    public const CLASS_INDEPENDENT_RESEARCH = 'independent_research';
    public const CLASS_AI_ORBIT_TESTED = 'ai_orbit_tested';
    public const CLASS_UNCLASSIFIED = 'unclassified';

    public const CLASSES = [
        self::CLASS_TECHNICAL,
        self::CLASS_PRODUCT_EXPERIENCE,
        self::CLASS_INDEPENDENT_RESEARCH,
        self::CLASS_AI_ORBIT_TESTED,
        self::CLASS_UNCLASSIFIED,
    ];

    protected $fillable = ['name','slug','category','benchmark_class','entity_scope','metric_type','unit','min_score','description','weight','max_score','version','variant','higher_is_better','official_url','methodology_url','is_active'];
    protected $casts = ['weight'=>'float','min_score'=>'float','max_score'=>'float','higher_is_better'=>'boolean','is_active'=>'boolean'];

    protected static function booted(): void
    {
        static::creating(function (Benchmark $benchmark) {
            $benchmark->slug ??= Str::slug($benchmark->name);
        });
    }

    /**
     * Resolve harmless benchmark-name punctuation variants (for example
     * "MMLU Pro" and "MMLU-Pro") to the same canonical definition.
     *
     * Exact canonical slugs win so legitimate versioned names remain distinct.
     */
    public static function findEquivalent(string $name): ?self
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $slug = Str::slug($name);
        if ($slug !== '') {
            $byCanonicalSlug = static::query()->where('slug', $slug)->first();
            if ($byCanonicalSlug) {
                return $byCanonicalSlug;
            }
        }

        return static::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();
    }

    public static function firstOrNewEquivalent(string $name): self
    {
        return static::findEquivalent($name) ?? new static(['name' => trim($name)]);
    }

    public static function classLabel(?string $class): string
    {
        return match ($class) {
            self::CLASS_TECHNICAL => 'Technical Performance',
            self::CLASS_PRODUCT_EXPERIENCE => 'Product Experience',
            self::CLASS_INDEPENDENT_RESEARCH => 'Independent Research',
            self::CLASS_AI_ORBIT_TESTED => 'AI Orbit Tested',
            default => 'Unclassified',
        };
    }

    public function getBenchmarkClassLabelAttribute(): string
    {
        return self::classLabel($this->benchmark_class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(BenchmarkResult::class);
    }
}
