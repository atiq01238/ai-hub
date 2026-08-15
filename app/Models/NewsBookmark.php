<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsBookmark extends Model
{
    protected $fillable = ['user_id', 'news_item_id'];

    public function newsItem()
    {
        return $this->belongsTo(NewsItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
