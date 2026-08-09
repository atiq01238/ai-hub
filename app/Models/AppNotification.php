<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Named AppNotification (not "Notification") to avoid clashing with
// Laravel's own built-in Notification classes/facade.
class AppNotification extends Model
{
    protected $table = 'notifications';

    protected $fillable = ['user_id', 'icon', 'tone', 'title', 'description', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Create a notice visible to every admin (user_id = null).
     * Used by observers elsewhere — e.g. AppNotification::broadcast('zap', 'warn', 'Price changed', '...')
     */
    public static function broadcast(string $icon, string $tone, string $title, ?string $description = null): void
    {
        static::create([
            'user_id'     => null,
            'icon'        => $icon,
            'tone'        => $tone,
            'title'       => $title,
            'description' => $description,
        ]);
    }
}
