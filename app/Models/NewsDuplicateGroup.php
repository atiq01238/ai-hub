<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsDuplicateGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'primary_news_item_id',
        'fingerprint',
        'article_count',
        'status',
        'last_detected_at',
    ];

    protected $casts = [
        'last_detected_at' => 'datetime',
    ];

    public function primaryNewsItem()
    {
        return $this->belongsTo(NewsItem::class, 'primary_news_item_id');
    }

    public function newsItems()
    {
        return $this->hasMany(NewsItem::class, 'duplicate_group_id');
    }

    public function getDuplicateItemsAttribute()
    {
        return $this->newsItems()
            ->where('duplicate_status', 'confirmed')
            ->where('id', '!=', $this->primary_news_item_id)
            ->get();
    }
}
