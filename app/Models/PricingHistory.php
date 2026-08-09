<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingHistory extends Model
{
    protected $table = 'pricing_history';

    protected $fillable = [
        'tool_id', 'plan_name', 'old_price', 'new_price', 'change_type',
    ];

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }
}
