<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_id', 'user_id', 'review_type', 'rating', 'verdict', 'body',
        'pros', 'cons', 'rating_breakdown', 'status',
    ];

    protected $casts = [
        'pros'             => 'array',
        'cons'             => 'array',
        'rating_breakdown' => 'array',
        'rating'           => 'decimal:1',
    ];

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
