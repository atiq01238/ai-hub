<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UseCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'short_description', 'description', 'icon',
        'meta_title', 'meta_description', 'is_active', 'is_indexable', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_indexable' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tools()
    {
        return $this->belongsToMany(Tool::class, 'tool_use_case')
            ->withPivot(['fit_note','verification_status','tool_source_id','verified_at','notes'])
            ->withTimestamps();
    }

    public function models()
    {
        return $this->belongsToMany(AiModel::class, 'ai_model_use_case')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSeoIndexable($query)
    {
        return $query
            ->active()
            ->where('is_indexable', true)
            ->where(function ($q) {
                $q->whereHas('tools', fn ($tools) => $tools->where('status', 'published'))
                    ->orWhereHas('models', fn ($models) => $models->whereIn('status', ['active', 'preview']));
            });
    }
}

