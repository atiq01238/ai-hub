<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetectedPriceChange extends Model
{
    protected $fillable = [
        'pricing_plan_id', 'pricing_source_id', 'tool_id', 'metric',
        'current_value', 'detected_value', 'currency', 'source_url', 'status',
        'review_note', 'reviewed_by', 'detected_at', 'reviewed_at',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(PricingPlan::class, 'pricing_plan_id');
    }

    public function source()
    {
        return $this->belongsTo(PricingSource::class, 'pricing_source_id');
    }

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
