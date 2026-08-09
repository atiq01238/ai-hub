<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    // Laravel auto-calls any method named "boot{TraitName}" when the model boots —
    // this is how the trait hooks into created/updated/deleted without you
    // having to call anything manually in your controllers.
    public static function bootLogsActivity()
    {
        static::created(fn ($model) => $model->logActivity('created'));
        static::updated(fn ($model) => $model->logActivity('updated'));
        static::deleted(fn ($model) => $model->logActivity('deleted'));
    }

    public function logActivity(string $action): void
    {
        ActivityLog::create([
            'user_id'      => auth()->id(),
            'action'       => $action,
            'subject_type' => static::class,
            'subject_id'   => $this->id,
            'description'  => $this->activityDescription($action),
        ]);
    }

    // Tries a few common "name" fields so the log reads naturally
    // regardless of which model triggered it (Tool has "name", Article has
    // "title", Submission has "tool_name", etc).
    protected function activityDescription(string $action): string
    {
        $name = $this->name ?? $this->title ?? $this->headline ?? $this->tool_name ?? "#{$this->id}";

        return ucfirst($action) . ' ' . class_basename(static::class) . " \"{$name}\"";
    }
}
