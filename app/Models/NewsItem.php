<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'news_source_id',
        'headline',
        'normalized_headline',
        'slug',
        'summary',
        'why_it_matters',
        'category',
        'ai_topic',
        'ai_tags',
        'ai_summary',
        'ai_why_it_matters',
        'ai_confidence',
        'ai_processor',
        'source',
        'source_url',
        'source_item_id',
        'canonical_url',
        'content_hash',
        'sentiment',
        'importance',
        'verification_status',
        'verified_at',
        'verification_notes',
        'tags',
        'related_tools',
        'processing_status',
        'duplicate_of_id',
        'duplicate_score',
        'duplicate_status',
        'duplicate_checked_at',
        'status',
        'published_at',
        'fetched_at',
        'ai_processed_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'related_tools' => 'array',
        'ai_tags' => 'array',
        'importance' => 'integer',
        'ai_confidence' => 'integer',
        'duplicate_score' => 'decimal:2',
        'published_at' => 'datetime',
        'fetched_at' => 'datetime',
        'ai_processed_at' => 'datetime',
        'verified_at' => 'datetime',
        'duplicate_checked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (NewsItem $item): void {
            if ($item->isDirty(['headline', 'summary'])) {
                $item->normalized_headline = self::normalizeHeadline($item->headline);
                $item->content_hash = self::makeContentHash($item->headline, $item->summary);

                if ($item->exists) {
                    $item->duplicate_checked_at = null;
                    $item->duplicate_of_id = null;
                    $item->duplicate_score = null;
                    $item->duplicate_status = 'unique';
                }
            }

            if ($item->verification_status === 'verified' && ! $item->verified_at) {
                $item->verified_at = now();
            }

            if ($item->verification_status !== 'verified' && $item->isDirty('verification_status')) {
                $item->verified_at = null;
            }
        });
    }

    public static function normalizeHeadline(?string $value): ?string
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = Str::lower(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        return $value !== '' ? Str::limit($value, 255, '') : null;
    }

    public static function makeContentHash(?string $headline, ?string $summary = null): ?string
    {
        $headline = self::normalizeForHash($headline);
        $summary = self::normalizeForHash($summary);
        $content = trim($headline . "\n" . $summary);

        return $content !== '' ? hash('sha256', $content) : null;
    }

    private static function normalizeForHash(?string $value): string
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = Str::lower(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    public function bookmarks()
    {
        return $this->hasMany(NewsBookmark::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function newsSource()
    {
        return $this->belongsTo(NewsSource::class);
    }

    public function duplicateOf()
    {
        return $this->belongsTo(self::class, 'duplicate_of_id');
    }

    public function duplicates()
    {
        return $this->hasMany(self::class, 'duplicate_of_id');
    }
}
