<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id', 'headline', 'slug', 'summary', 'why_it_matters',
        'category', 'source', 'source_url',
        'sentiment', 'importance', 'verification_status',
        'tags', 'related_tools', 'status', 'published_at',
    ];

    protected $casts = [
        'tags'          => 'array',
        'related_tools' => 'array',
        'published_at'  => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
