<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscoverySource extends Model
{
    protected $fillable = [
        'news_source_id', 'enabled', 'trusted', 'detect_tools', 'detect_models',
        'minimum_confidence', 'last_discovery_at', 'discoveries_count',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'trusted' => 'boolean',
        'detect_tools' => 'boolean',
        'detect_models' => 'boolean',
        'minimum_confidence' => 'integer',
        'last_discovery_at' => 'datetime',
        'discoveries_count' => 'integer',
    ];

    public function newsSource()
    {
        return $this->belongsTo(NewsSource::class);
    }
}
