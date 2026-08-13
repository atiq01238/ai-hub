<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    const UPDATED_AT = null; // this table only tracks when the attempt happened, nothing to update

    protected $fillable = ['email', 'user_id', 'ip_address', 'user_agent', 'successful'];

    protected $casts = ['successful' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
