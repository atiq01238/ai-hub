<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'company_id', 'title', 'slug', 'featured_image_path',
        'content', 'summary', 'category', 'tags', 'related_tools', 'related_models',
        'seo_title', 'meta_description', 'status', 'published_at',
    ];

    protected $casts = [
        'tags'           => 'array',
        'related_tools'  => 'array',
        'related_models' => 'array',
        'published_at'   => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
