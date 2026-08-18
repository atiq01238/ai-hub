<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'user_id', 'name', 'email', 'topic', 'subject', 'message',
        'status', 'ip_hash', 'user_agent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
