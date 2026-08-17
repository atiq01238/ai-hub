<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use LogsActivity;

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'priority',
        'status',
        'assigned_to',
        'resolved_by',
        'resolution_note',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reportable()
    {
        return $this->morphTo();
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['pending', 'reviewing']);
    }

    public function getSubjectLabelAttribute(): string
    {
        $subject = $this->reportable;

        return match (true) {
            $subject instanceof User => $subject->name,
            $subject instanceof Submission => $subject->tool_name,
            $subject instanceof Review => 'Review #' . $subject->id,
            default => class_basename($this->reportable_type) . ' #' . $this->reportable_id,
        };
    }
}
