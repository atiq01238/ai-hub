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

    public function useCaseTerms()
    {
        return $this->belongsToMany(UseCase::class, 'tool_use_case')->withTimestamps();
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
        $description = trim(strip_tags((string) ($this->description ?: $this->short_description)));
        $short = trim(strip_tags((string) $this->short_description));
        $parts = [];

        if ($description !== '') {
            $parts[] = $description;
        } else {
            $parts[] = $this->name . ' is an AI tool listed in the AI Hub catalog.';
        }

        $needsContext = mb_strlen($description) < 180 || ($short !== '' && $description === $short);
        if ($needsContext) {
            $provider = $this->company?->name;
            $category = $this->category?->name;
            $context = $this->name;
            if ($provider) $context .= ' is developed by ' . $provider;
            if ($category) $context .= ' and is categorized as ' . $category;
            $parts[] = $context . '.';

            $caps = collect($this->capabilities ?? [])->filter()->take(6)->values();
            if ($caps->isNotEmpty()) {
                $parts[] = 'Its cataloged capabilities include ' . $caps->join(', ', ' and ') . '.';
            }

            $platforms = collect($this->platforms ?? [])->filter()->take(6)->values();
            if ($platforms->isNotEmpty()) {
                $parts[] = 'AI Hub currently lists support for ' . $platforms->join(', ', ' and ') . '.';
            }

            $pricing = collect($this->pricing_models ?? [])->filter()->values();
            if ($pricing->isNotEmpty()) {
                $parts[] = 'The recorded pricing model is ' . $pricing->join(', ', ' and ') . '; detailed plan pricing is shown in the pricing section when verified data is available.';
            }
        }

        return implode("\n\n", array_values(array_unique(array_filter($parts))));
    }
}
