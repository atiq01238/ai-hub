<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Read one setting, e.g. Setting::get('site_name', 'AI Hub').
     */
    public static function get(string $key, $default = null)
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Write one setting, e.g. Setting::set('site_name', 'My Site').
     * Creates the row if it doesn't exist yet, updates it if it does.
     */
    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Read many settings at once into a simple ['key' => 'value'] array —
     * used by the settings page so it doesn't run one query per field.
     */
    public static function getMany(array $keys, array $defaults = []): array
    {
        $rows = static::whereIn('key', $keys)->pluck('value', 'key')->all();

        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $rows[$key] ?? ($defaults[$key] ?? null);
        }

        return $result;
    }
}
