<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessageReply extends Model
{
    protected $fillable = [
        'contact_message_id',
        'admin_id',
        'body',
    ];

    public function contactMessage()
    {
        return $this->belongsTo(ContactMessage::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id')->withTrashed();
    }
}
