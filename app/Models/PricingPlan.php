<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingPlan extends Model
{
    protected $fillable = [
        'tool_id', 'plan_name', 'monthly_price', 'yearly_price',
        'api_price_label', 'credits', 'limits',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
    ];

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
