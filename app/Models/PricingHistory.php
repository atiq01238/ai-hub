<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingHistory extends Model
{
    protected $table = 'pricing_history';

    protected $fillable = [
        'tool_id', 'plan_name', 'metric', 'old_value', 'new_value',
        'old_price', 'new_price', 'change_type', 'source_url', 'detected_change_id',
    ];

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    public function detectedChange()
    {
        return $this->belongsTo(DetectedPriceChange::class);
    }
}
