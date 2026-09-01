<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $fillable = ['name', 'slug', 'website', 'category', 'is_active', 'sort_order'];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tools()
    {
        return $this->belongsToMany(Tool::class, 'integration_tool')
            ->withPivot(['tool_source_id', 'verification_status', 'verified_at', 'notes'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
