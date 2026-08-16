<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingSource extends Model
{
    protected $fillable = [
        'pricing_plan_id', 'metric', 'source_name', 'source_url', 'source_type',
        'extraction_rule', 'currency', 'unit', 'enabled', 'last_checked_at',
        'last_check_status', 'last_check_message', 'last_detected_value',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_checked_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(PricingPlan::class, 'pricing_plan_id');
    }

    public function detectedChanges()
    {
        return $this->hasMany(DetectedPriceChange::class);
    }
}
