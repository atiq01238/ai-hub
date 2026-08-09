<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingPlan extends Model
{
    protected $fillable = [
        'tool_id', 'plan_name', 'monthly_price', 'yearly_price',
        'api_price_label', 'credits', 'limits',
    ];

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }
}
