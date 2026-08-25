<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsSession extends Model
{
    protected $fillable = [
        'visitor_id',
        'user_id',
        'session_key',
        'started_at',
        'last_seen_at',
        'landing_path',
        'referrer_domain',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'device_type',
        'browser',
        'operating_system',
        'country_code',
        'page_views',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'page_views' => 'integer',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(AnalyticsVisitor::class, 'visitor_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(AnalyticsPageView::class, 'analytics_session_id');
    }
}
