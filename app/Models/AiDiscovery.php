<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiDiscovery extends Model
{
    protected $fillable = [
        'news_item_id', 'news_source_id', 'company_id', 'matched_tool_id', 'matched_model_id',
        'entity_type', 'candidate_name', 'headline', 'summary', 'source_url', 'confidence',
        'status', 'signals', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = [
        'confidence' => 'integer',
        'signals' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function newsItem() { return $this->belongsTo(NewsItem::class); }
    public function newsSource() { return $this->belongsTo(NewsSource::class); }
    public function company() { return $this->belongsTo(Company::class); }
    public function matchedTool() { return $this->belongsTo(Tool::class, 'matched_tool_id'); }
    public function matchedModel() { return $this->belongsTo(AiModel::class, 'matched_model_id'); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
}
