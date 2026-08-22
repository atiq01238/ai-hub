<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'slug', 'short_description', 'description',
        'meta_title', 'meta_description', 'is_active', 'is_indexable', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_indexable' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function tools()
    {
        return $this->hasMany(Tool::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
