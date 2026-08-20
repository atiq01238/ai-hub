<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserComparison extends Model
{
    protected $fillable = [
        'user_id',
        'comparison_id',
        'comparable_type',
        'item_ids',
        'title',
        'signature',
        'is_saved',
        'last_viewed_at',
    ];

    protected $casts = [
        'item_ids' => 'array',
        'is_saved' => 'boolean',
        'last_viewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comparison()
    {
        return $this->belongsTo(Comparison::class);
    }
}
