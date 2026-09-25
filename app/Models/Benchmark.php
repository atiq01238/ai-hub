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

    /**
     * Benchmarks that are strong enough to remain indexable even when they are
     * not yet important enough for direct sitemap promotion.
     *
     * Tier A = sitemap priority. Tier B = indexable but discovered naturally.
     * Tier C = public methodology/reference page, noindex until evidence grows.
     */
    public function scopeSeoIndexable($query)
    {
        $verified = fn ($q) => $q->where('verified', true)->where('status', 'verified');
        $indexMin = (int) config('seo_content_quality.benchmark.index_min_verified_results', 2);
        $indexDescriptionMin = (int) config('seo_content_quality.benchmark.index_min_description_chars', 100);
        $highEvidenceMin = (int) config('seo_content_quality.benchmark.high_evidence_verified_results', 5);
        $richSingleMin = (int) config('seo_content_quality.benchmark.rich_single_min_description_chars', 160);

        return $query
            ->where('is_active', true)
            ->where(function ($source) {
                $source->whereNotNull('official_url')
                    ->orWhereNotNull('methodology_url');
            })
            ->where(function ($query) use ($verified, $indexMin, $indexDescriptionMin, $highEvidenceMin, $richSingleMin) {
                // High-evidence benchmark: result depth can compensate for a
                // short editorial description, but a source is still required.
                $query->whereHas('results', $verified, '>=', $highEvidenceMin)
                    ->orWhere(function ($standard) use ($verified, $indexMin, $indexDescriptionMin) {
                        $standard
                            ->whereHas('results', $verified, '>=', $indexMin)
                            ->whereNotNull('description')
                            ->whereRaw('CHAR_LENGTH(TRIM(description)) >= ?', [$indexDescriptionMin]);
                    })
                    ->orWhere(function ($richSingle) use ($verified, $richSingleMin) {
                        $richSingle
                            ->whereHas('results', $verified, '>=', 1)
                            ->whereNotNull('description')
                            ->whereRaw('CHAR_LENGTH(TRIM(description)) >= ?', [$richSingleMin]);
                    });
            });
    }

    /**
     * Highest-signal benchmark pages promoted directly through the sitemap.
     *
     * This deliberately uses a stronger gate than seoIndexable(): AI Orbit's
     * crawl-recovery strategy gives direct sitemap priority only to benchmarks
     * with multiple verified entities plus explanatory/source context.
     */
    public function scopeSeoDiscoveryPriority($query)
    {
        $verified = fn ($q) => $q->where('verified', true)->where('status', 'verified');

        return $query
            ->where('is_active', true)
            ->whereHas('results', $verified, '>=', (int) config('seo_content_quality.benchmark.sitemap_min_verified_results', 5))
            ->whereNotNull('description')
            ->whereRaw('CHAR_LENGTH(TRIM(description)) >= ?', [
                (int) config('seo_content_quality.benchmark.sitemap_min_description_chars', 120),
            ])
            ->where(function ($source) {
                $source->whereNotNull('official_url')
                    ->orWhereNotNull('methodology_url');
            });
    }

    public function seoAssessment(): array
    {
        $verifiedRows = $this->relationLoaded('results')
            ? $this->results
            : $this->results()
                ->where('verified', true)
                ->where('status', 'verified')
                ->get(['id', 'benchmark_id', 'benchmarkable_type', 'benchmarkable_id', 'verified', 'status']);

        $verifiedRows = $verifiedRows
            ->filter(fn ($row) => (bool) $row->verified && $row->status === 'verified');

        // A benchmark page presents the latest result per entity, so evidence
        // strength should count distinct entities rather than historical rows.
        $verifiedEntities = $verifiedRows
            ->unique(fn ($row) => $row->benchmarkable_type.'|'.$row->benchmarkable_id)
            ->count();

        $descriptionChars = mb_strlen(trim(strip_tags((string) $this->description)));
        $hasSource = filled($this->official_url) || filled($this->methodology_url);

        $tierA = $this->is_active
            && $verifiedEntities >= (int) config('seo_content_quality.benchmark.sitemap_min_verified_results', 5)
            && $descriptionChars >= (int) config('seo_content_quality.benchmark.sitemap_min_description_chars', 120)
            && $hasSource;

        $indexMin = (int) config('seo_content_quality.benchmark.index_min_verified_results', 2);
        $indexDescriptionMin = (int) config('seo_content_quality.benchmark.index_min_description_chars', 100);
        $highEvidenceMin = (int) config('seo_content_quality.benchmark.high_evidence_verified_results', 5);
        $richSingleMin = (int) config('seo_content_quality.benchmark.rich_single_min_description_chars', 160);

        $tierB = ! $tierA
            && $this->is_active
            && $hasSource
            && (
                // Strong result depth is useful evidence even when the benchmark
                // description is terse (for example a well-known benchmark).
                $verifiedEntities >= $highEvidenceMin
                || (
                    $verifiedEntities >= $indexMin
                    && $descriptionChars >= $indexDescriptionMin
                )
                || (
                    $verifiedEntities >= 1
                    && $descriptionChars >= $richSingleMin
                )
            );

        $tier = $tierA ? 'A' : ($tierB ? 'B' : 'C');

        return [
            'tier' => $tier,
            'indexable' => in_array($tier, ['A', 'B'], true),
            'sitemap_priority' => $tier === 'A',
            'robots' => in_array($tier, ['A', 'B'], true)
                ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
                : 'noindex,follow',
            'verified_entities' => $verifiedEntities,
            'description_chars' => $descriptionChars,
            'has_source' => $hasSource,
        ];
    }

    public function isSeoIndexable(): bool
    {
        return (bool) ($this->seoAssessment()['indexable'] ?? false);
    }

    public function isSeoDiscoveryPriority(): bool
    {
        return (bool) ($this->seoAssessment()['sitemap_priority'] ?? false);
    }

    public function results(): HasMany
    {
        return $this->hasMany(BenchmarkResult::class);
    }
}
