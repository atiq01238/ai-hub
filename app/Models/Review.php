<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'tool_id', 'user_id', 'review_type', 'rating', 'verdict', 'body',
        'pros', 'cons', 'rating_breakdown', 'status', 'moderation_note',
        'moderated_by', 'moderated_at',
    ];

    protected $casts = [
        'pros'             => 'array',
        'cons'             => 'array',
        'rating_breakdown' => 'array',
        'rating'           => 'float',
        'moderated_at'     => 'datetime',
    ];

    public function tool()
    {
        return $this->belongsTo(Tool::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
