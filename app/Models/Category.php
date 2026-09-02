<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'type', 'short_description', 'description',
        'meta_title', 'meta_description', 'is_active', 'is_indexable', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_indexable' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tools()
    {
        return $this->hasMany(Tool::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class)->orderBy('sort_order')->orderBy('name');
    }

    public function scopeProduct($query)
    {
        return $query->where('type', 'product');
    }

    public function scopeContent($query)
    {
        return $query->where('type', 'content');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSeoProductIndexable($query)
    {
        return $query
            ->product()
            ->active()
            ->where('is_indexable', true)
            ->whereHas('tools', fn ($q) => $q->where('status', 'published'));
    }

    public function scopeSeoContentIndexable($query)
    {
        return $query
            ->content()
            ->active()
            ->where('is_indexable', true)
            ->whereHas('articles', fn ($q) => $q
                ->where('status', 'published')
                ->where('approval_status', 'approved'));
    }
}

