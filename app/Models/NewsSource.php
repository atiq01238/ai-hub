<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'url',
        'company_id',
        'default_category',
        'status',
        'last_fetched_at',
        'last_started_at',
        'last_success_at',
        'articles_collected',
        'last_items_seen',
        'last_items_created',
        'last_items_skipped',
        'last_duration_ms',
        'consecutive_failures',
        'last_error',
    ];

    protected $casts = [
        'last_fetched_at' => 'datetime',
        'last_started_at' => 'datetime',
        'last_success_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function newsItems()
    {
        return $this->hasMany(NewsItem::class);
    }

    public function isHealthy(): bool
    {
        return $this->status === 'active'
            && $this->consecutive_failures === 0
            && empty($this->last_error);
    }
}
