<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingPlan extends Model
{
    protected $fillable = [
        'tool_id', 'plan_name', 'currency', 'billing_type', 'billing_unit',
        'monthly_price', 'yearly_price', 'api_price_label', 'credits', 'limits', 'last_verified_at',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'last_verified_at' => 'datetime',
    ];

    public function getFreshnessAttribute(): string
    {
        $verified = $this->last_verified_at ?: $this->sources()->max('last_checked_at');
        if (! $verified) return 'unverified';
        $date = $verified instanceof \Carbon\CarbonInterface ? $verified : \Carbon\Carbon::parse($verified);
        if ($date->gte(now()->subDays(14))) return 'fresh';
        if ($date->gte(now()->subDays(45))) return 'review';
        return 'stale';
    }

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    public function sources()
    {
        return $this->hasMany(PricingSource::class);
    }

    public function detectedChanges()
    {
        return $this->hasMany(DetectedPriceChange::class);
    }
}
