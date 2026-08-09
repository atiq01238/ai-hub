<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $fillable = [
        'user_id', 'tool_name', 'submitted_by_email', 'website',
        'category', 'description', 'status', 'admin_notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
