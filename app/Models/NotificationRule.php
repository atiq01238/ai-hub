<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRule extends Model
{
    protected $fillable = ['trigger_key', 'label', 'enabled'];

    protected $casts = ['enabled' => 'boolean'];

    /**
     * Check before sending any notification, e.g.:
     *   if (NotificationRule::isEnabled('price_change')) { ... }
     * Defaults to true if the rule row doesn't exist yet, so nothing
     * silently stops working if you add a new trigger later and forget
     * to seed a row for it.
     */
    public static function isEnabled(string $key): bool
    {
        return static::where('trigger_key', $key)->value('enabled') ?? true;
    }
}
