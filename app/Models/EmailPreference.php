<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailPreference extends Model
{
    protected $fillable = [
        'user_id', 'email_enabled', 'breaking_news', 'new_models', 'new_tools',
        'followed_entities', 'benchmark_updates', 'price_changes', 'weekly_digest',
        'unsubscribed_at',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'breaking_news' => 'boolean',
        'new_models' => 'boolean',
        'new_tools' => 'boolean',
        'followed_entities' => 'boolean',
        'benchmark_updates' => 'boolean',
        'price_changes' => 'boolean',
        'weekly_digest' => 'boolean',
        'unsubscribed_at' => 'datetime',
    ];

    public static function defaults(): array
    {
        return [
            'email_enabled' => true,
            'breaking_news' => true,
            'new_models' => true,
            'new_tools' => true,
            'followed_entities' => true,
            'benchmark_updates' => false,
            'price_changes' => false,
            'weekly_digest' => true,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
