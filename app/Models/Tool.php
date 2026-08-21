<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id', 'category_id', 'subcategory_id', 'subcategory',
        'name', 'slug', 'logo_path', 'cover_image_path', 'website', 'launch_date',
        'short_description', 'description',
        'pricing_models', 'tags', 'capabilities', 'platforms',
        'status', 'rating', 'popularity', 'rating_breakdown',
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
        'rating'           => 'float',
        'popularity'       => 'integer',
        'benchmark_score'  => 'float',
    ];

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
        return $this->belongsToMany(Feature::class, 'feature_tool')->withTimestamps();
    }

    public function tagTerms()
    {
        return $this->belongsToMany(Tag::class, 'tag_tool')->withTimestamps();
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

    public function recalculateRating(): void
    {
        $average = $this->reviews()->published()->avg('rating');
        $this->rating = $average ? round($average, 1) : 0;
        $this->saveQuietly();
    }
    public function benchmarkResults()
    {
        return $this->morphMany(BenchmarkResult::class, 'benchmarkable');
    }


    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) return \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path);
        return $this->company?->logo_url ?: asset('favicon.ico');
    }
}
