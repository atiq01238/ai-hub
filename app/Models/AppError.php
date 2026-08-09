<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Throwable;

class AppError extends Model
{
    protected $fillable = [
        'exception_class', 'message', 'file', 'line', 'url', 'http_method', 'user_id',
        'trace', 'occurrence_count', 'first_seen_at', 'last_seen_at', 'status', 'resolution_notes',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Log a caught exception. If the exact same exception+file+line already
     * has an OPEN entry, bump its occurrence count instead of creating a
     * duplicate row for every single request.
     */
    public static function recordFrom(Throwable $e): void
    {
        $existing = static::where('exception_class', get_class($e))
            ->where('file', $e->getFile())
            ->where('line', $e->getLine())
            ->where('status', '!=', 'resolved')
            ->first();

        if ($existing) {
            $existing->increment('occurrence_count');
            $existing->update(['last_seen_at' => now()]);

            return;
        }

        static::create([
            'exception_class'  => get_class($e),
            'message'          => $e->getMessage(),
            'file'             => $e->getFile(),
            'line'             => $e->getLine(),
            'url'              => request()?->fullUrl(),
            'http_method'      => request()?->method(),
            'user_id'          => auth()->id(),
            'trace'            => $e->getTraceAsString(),
            'occurrence_count' => 1,
            'first_seen_at'    => now(),
            'last_seen_at'     => now(),
            'status'           => 'open',
        ]);
    }

    /**
     * Rough severity guess from the exception's class name — not perfect,
     * just enough to sort "probably serious" from "probably minor" at a glance.
     */
    public function getSeverityAttribute(): string
    {
        $class = class_basename($this->exception_class);

        if (str_contains($class, 'Validation') || str_contains($class, 'NotFound') || str_contains($class, 'Authorization')) {
            return 'low';
        }

        if (str_contains($class, 'Error') || str_contains($class, 'Fatal')) {
            return 'critical';
        }

        return 'medium';
    }
}
