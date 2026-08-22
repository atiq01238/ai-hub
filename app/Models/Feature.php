<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'short_description', 'description', 'group', 'icon',
        'meta_title', 'meta_description', 'is_active', 'is_indexable', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_indexable' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tools()
    {
        return $this->belongsToMany(Tool::class, 'feature_tool')->withTimestamps();
    }

    public function models()
    {
        return $this->belongsToMany(AiModel::class, 'ai_model_feature')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
