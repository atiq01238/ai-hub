<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id', 'submission_type', 'tool_name', 'submitted_by_email',
        'website', 'category', 'description', 'status', 'admin_notes',
        'reviewed_by', 'reviewed_at', 'converted_tool_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function convertedTool()
    {
        return $this->belongsTo(Tool::class, 'converted_tool_id');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
