<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function getLogoUrlAttribute(): string
    {
        return $this->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->logo_path) : asset('favicon.ico');
    }
}
