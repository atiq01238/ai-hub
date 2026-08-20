<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunityComment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'parent_id',
        'commentable_type',
        'commentable_id',
        'body',
        'status',
        'moderation_reason',
        'auto_published',
        'moderation_note',
        'moderated_by',
        'moderated_at',
    ];

    protected $casts = [
        'auto_published' => 'boolean',
        'moderated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id');
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
