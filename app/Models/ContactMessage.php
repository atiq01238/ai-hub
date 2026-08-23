<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'user_id', 'name', 'email', 'topic', 'subject', 'message',
        'status', 'admin_notes', 'handled_by', 'read_at', 'replied_at',
        'closed_at', 'spam_at', 'ip_hash', 'user_agent',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
        'closed_at' => 'datetime',
        'spam_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by')->withTrashed();
    }

    public function replies()
    {
        return $this->hasMany(ContactMessageReply::class)->oldest();
    }

    public function getTopicLabelAttribute(): string
    {
        return match ($this->topic) {
            'data_correction' => 'Data Correction',
            'partnership' => 'Partnership',
            'technical' => 'Technical',
            'feedback' => 'Feedback',
            'press' => 'Press',
            default => 'General',
        };
    }
}
