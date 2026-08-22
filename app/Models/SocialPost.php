<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;

class SocialPost extends Model
{
    protected $fillable = [
        'news_item_id', 'platform', 'content', 'image_path',
        'status', 'scheduled_at', 'published_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return MediaUrl::resolve($this->image_path);
    }

    public function newsItem()
    {
        return $this->belongsTo(NewsItem::class);
    }
}
