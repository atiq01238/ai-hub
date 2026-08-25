<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsPageView extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'visitor_id',
        'analytics_session_id',
        'user_id',
        'route_name',
        'path',
        'entity_type',
        'entity_id',
        'is_entry',
        'viewed_at',
    ];

    protected $casts = [
        'is_entry' => 'boolean',
        'viewed_at' => 'datetime',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(AnalyticsVisitor::class, 'visitor_id');
    }

    public function analyticsSession(): BelongsTo
    {
        return $this->belongsTo(AnalyticsSession::class, 'analytics_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
