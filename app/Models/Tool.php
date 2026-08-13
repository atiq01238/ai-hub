<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'company_id', 'category_id', 'subcategory',
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
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Recompute this tool's `rating` column as the average of its published
     * reviews. Called automatically by ReviewObserver whenever a review is
     * saved or deleted — you should never need to call this by hand.
     */
    public function recalculateRating(): void
    {
        $average = $this->reviews()->published()->avg('rating');

        $this->rating = $average ? round($average, 1) : 0;
        $this->saveQuietly(); // "quietly" = don't re-fire Tool's own events
    }
}