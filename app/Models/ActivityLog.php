<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'action', 'subject_type', 'subject_id', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Handy for the UI — turns "App\Models\Tool" into "Tool".
    public function getSubjectNameAttribute(): string
    {
        return class_basename($this->subject_type);
    }
}
