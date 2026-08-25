<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsVisitor extends Model
{
    protected $fillable = [
        'visitor_key',
        'user_id',
        'first_seen_at',
        'last_seen_at',
        'first_landing_path',
        'first_referrer_domain',
        'first_utm_source',
        'first_utm_medium',
        'first_utm_campaign',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AnalyticsSession::class, 'visitor_id');
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(AnalyticsPageView::class, 'visitor_id');
    }
}
