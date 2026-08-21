<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'reviewer_id', 'company_id', 'category_id', 'origin_news_item_id', 'title', 'slug', 'featured_image_path',
        'content', 'summary', 'category', 'tags', 'related_tools', 'related_models',
        'seo_title', 'meta_description', 'status', 'approval_status', 'published_at',
        'submitted_for_review_at', 'approved_at',
    ];

    protected $casts = [
        'tags'                    => 'array',
        'related_tools'           => 'array',
        'related_models'          => 'array',
        'published_at'            => 'datetime',
        'submitted_for_review_at' => 'datetime',
        'approved_at'             => 'datetime',
    ];

    public function originNews() { return $this->belongsTo(NewsItem::class, 'origin_news_item_id'); }

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function categoryTerm()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function relatedToolTerms()
    {
        return $this->belongsToMany(Tool::class, 'article_tool');
    }

    public function relatedModelTerms()
    {
        return $this->belongsToMany(AiModel::class, 'ai_model_article');
    }

    public function tagTerms()
    {
        return $this->belongsToMany(Tag::class, 'article_tag');
    }

    public function workflowEvents()
    {
        return $this->hasMany(ArticleWorkflowEvent::class)->latest();
    }
}
