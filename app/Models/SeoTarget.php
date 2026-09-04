<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoTarget extends Model
{
    protected $fillable = [
        'target_key',
        'route_name',
        'page_type',
        'targetable_type',
        'targetable_id',
        'primary_keyword',
        'secondary_keywords',
        'search_intent',
        'topic_cluster',
        'source',
        'is_locked',
        'last_researched_at',
    ];

    protected $casts = [
        'secondary_keywords' => 'array',
        'is_locked' => 'boolean',
        'last_researched_at' => 'datetime',
    ];

    public function targetable(): MorphTo
    {
        return $this->morphTo();
    }
}
