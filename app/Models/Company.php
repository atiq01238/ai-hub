<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\MediaUrl;

class Company extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name', 'slug', 'logo_path', 'website',
        'description', 'status', 'founded_year',
    ];

    public function tools()
    {
        return $this->hasMany(Tool::class);
    }

    public function models()
    {
        return $this->hasMany(AiModel::class);
    }

    public function newsItems()
    {
        return $this->hasMany(NewsItem::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function scopePublic($query)
    {
        return $query->whereIn('status', ['active', 'acquired']);
    }

    public function scopeSeoIndexable($query)
    {
        return $query
            ->public()
            ->where(function ($query) {
                $query->whereHas('tools', fn ($q) => $q->where('status', 'published'))
                    ->orWhereHas('models', fn ($q) => $q->whereIn('status', ['active', 'preview']))
                    ->orWhereHas('newsItems', fn ($q) => $q
                        ->where('status', 'published')
                        ->whereNull('duplicate_of_id')
                        ->where(fn ($news) => $news
                            ->whereNull('duplicate_status')
                            ->orWhere('duplicate_status', '!=', 'duplicate')))
                    ->orWhereHas('articles', fn ($q) => $q
                        ->where('status', 'published')
                        ->where('approval_status', 'approved'))
                    ->orWhere(function ($profile) {
                        $profile->whereNotNull('website')
                            ->whereNotNull('description')
                            ->whereRaw('CHAR_LENGTH(TRIM(description)) >= 160');
                    });
            });
    }

    public function getLogoUrlAttribute(): string
    {
        return MediaUrl::resolve($this->logo_path, 'favicon.ico') ?: MediaUrl::placeholder();
    }
}
