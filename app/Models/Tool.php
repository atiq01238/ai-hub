<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\MediaUrl;

class Tool extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id', 'category_id', 'subcategory_id', 'subcategory',
        'name', 'slug', 'logo_path', 'cover_image_path', 'website', 'launch_date',
        'short_description', 'description',
        'pricing_models', 'tags', 'capabilities', 'platforms',
        'status', 'product_status', 'product_status_note', 'product_status_source_id', 'product_status_verified_at',
        'rating', 'popularity', 'rating_breakdown',
        'benchmarks', 'benchmark_score',
        'seo_title', 'meta_description', 'og_image_path',
        'published_at',
    ];

    protected $casts = [
        'pricing_models'   => 'array',
        'tags'             => 'array',
        'capabilities'     => 'array',
        'platforms'        => 'array',
        'rating_breakdown' => 'array',
        'benchmarks'       => 'array',
        'launch_date'      => 'date',
        'published_at'     => 'datetime',
        'product_status_verified_at' => 'datetime',
        'rating'           => 'float',
        'popularity'       => 'integer',
        'benchmark_score'  => 'float',
    ];


    public const PRODUCT_STATUSES = [
        'unknown' => 'Unknown / not yet verified',
        'active' => 'Active',
        'beta' => 'Beta',
        'preview' => 'Preview',
        'waitlist' => 'Waitlist',
        'discontinued' => 'Discontinued',
        'sunset' => 'Sunset / shutting down',
        'acquired' => 'Acquired',
        'rebranded' => 'Rebranded',
        'region_limited' => 'Region limited',
    ];

    public function productStatusSource()
    {
        return $this->belongsTo(ToolSource::class, 'product_status_source_id');
    }

    public function getProductStatusLabelAttribute(): string
    {
        return self::PRODUCT_STATUSES[$this->product_status ?: 'unknown'] ?? 'Unknown / not yet verified';
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategoryTerm()
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id');
    }

    public function featureTerms()
    {
        return $this->belongsToMany(Feature::class, 'feature_tool')
            ->withPivot(['description','verification_status','tool_source_id','verified_at','notes'])
            ->withTimestamps();
    }

    public function tagTerms()
    {
        return $this->belongsToMany(Tag::class, 'tag_tool')->withTimestamps();
    }

    public function useCaseTerms()
    {
        return $this->belongsToMany(UseCase::class, 'tool_use_case')
            ->withPivot(['fit_note','verification_status','tool_source_id','verified_at','notes'])
            ->withTimestamps();
    }

    public function models()
    {
        return $this->hasMany(AiModel::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function pricingPlans()
    {
        return $this->hasMany(PricingPlan::class);
    }

    public function sources()
    {
        return $this->hasMany(ToolSource::class);
    }

    public function verifiedSources()
    {
        return $this->hasMany(ToolSource::class)
            ->where('enabled', true)
            ->where('verification_status', 'verified');
    }

    public function factEvidence()
    {
        return $this->hasMany(ToolFactEvidence::class);
    }

    public function platformTerms()
    {
        return $this->belongsToMany(Platform::class, 'platform_tool')->withTimestamps();
    }

    public function technicalProfile()
    {
        return $this->hasOne(ToolTechnicalProfile::class);
    }

    public function integrationTerms()
    {
        return $this->belongsToMany(Integration::class, 'integration_tool')
            ->withPivot(['tool_source_id', 'verification_status', 'verified_at', 'notes'])
            ->withTimestamps();
    }

    public function recalculateRating(): void
    {
        $average = $this->reviews()
            ->published()
            ->where('review_type', 'user')
            ->avg('rating');

        $this->rating = $average !== null ? round((float) $average, 1) : 0.0;
        $this->saveQuietly();
    }
    public function benchmarkResults()
    {
        return $this->morphMany(BenchmarkResult::class, 'benchmarkable');
    }


    public function getLogoUrlAttribute(): string
    {
        return MediaUrl::resolve($this->logo_path)
            ?: $this->company?->logo_url
            ?: MediaUrl::placeholder();
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return MediaUrl::resolve($this->cover_image_path);
    }

    public function getOgImageUrlAttribute(): ?string
    {
        return MediaUrl::resolve($this->og_image_path);
    }

    public function getOverviewAttribute(): string
    {
        // Phase 4: keep the narrative overview editorial. Older builds padded
        // short descriptions with repeated provider/category/capability text,
        // which made otherwise rich tool pages read like templates. Structured
        // facts now live in their dedicated decision, capability and pricing
        // sections instead of being turned into synthetic overview paragraphs.
        $description = trim(strip_tags((string) $this->description));
        if ($description !== '') {
            return $description;
        }

        return trim(strip_tags((string) $this->short_description));
    }
}
