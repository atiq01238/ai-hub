<?php

namespace App\Services;

use App\Models\NewsItem;
use Illuminate\Support\Facades\DB;

class NewsIntelligenceService
{
    public function trendingScore(NewsItem $news): float
    {
        $age = max(0, optional($news->published_at)->diffInHours(now()) ?? 720);
        $freshness = 100 * exp(-$age / 168);
        $authority = (int) ($news->newsSource?->authority_score ?? 55);
        $verified = $news->verification_status === 'verified' ? 100 : 45;
        $unique = ($news->duplicate_of_id || $news->duplicate_status === 'duplicate') ? 0 : 100;

        return round(
            ((int) $news->importance * .35)
            + ($freshness * .25)
            + ($authority * .20)
            + ($verified * .15)
            + ($unique * .05),
            2
        );
    }

    /**
     * Refresh a derived ranking signal without changing the article's content
     * timestamp. Sitemap <lastmod> and NewsArticle dateModified must represent
     * substantive editorial changes, not a background score recalculation.
     */
    public function refresh(NewsItem $news): void
    {
        $news->loadMissing('newsSource');
        $score = $this->trendingScore($news);

        if ((float) $news->trending_score === $score) {
            return;
        }

        DB::table($news->getTable())
            ->where($news->getKeyName(), $news->getKey())
            ->update(['trending_score' => $score]);

        $news->trending_score = $score;
        $news->syncOriginalAttribute('trending_score');
    }
}
