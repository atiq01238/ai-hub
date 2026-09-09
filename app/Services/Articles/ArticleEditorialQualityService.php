<?php

namespace App\Services\Articles;

use App\Models\Article;
use App\Support\ArticleContent;
use Illuminate\Support\Collection;

class ArticleEditorialQualityService
{
    /**
     * Build a content-depth assessment from the article itself plus its curated
     * taxonomy/entity links. Word count is one signal, not the whole decision.
     */
    public function assess(Article $article): array
    {
        $content = (string) ($article->content ?? '');
        $wordCount = $this->wordCount($content);
        $summaryChars = mb_strlen($this->plainText($article->summary ?: $article->meta_description));
        $outline = ArticleContent::outline($content);
        $headingCount = count($outline);
        $h2Count = collect($outline)->where('level', 2)->count();
        $faqCount = count(ArticleContent::faq($content));
        $listItemCount = preg_match_all('/^(?:[-*]\s+|\d+[.)]\s+).+$/mu', $content, $listMatches) ?: 0;
        $referenceUrlCount = preg_match_all('~https?://[^\s)\]>]+~iu', $content, $urlMatches) ?: 0;
        $paragraphCount = $this->paragraphCount($content);

        $practicalHeadingCount = collect($outline)->filter(function (array $heading): bool {
            return (bool) preg_match(
                '/\b(example|examples|checklist|workflow|step|steps|how to|compare|comparison|decision|mistake|mistakes|risk|risks|security|cost|measure|evaluate|evaluation|test|testing|when to|best practice|best practices|limitation|limitations|trade-off|trade-offs|implementation|production)\b/iu',
                (string) ($heading['text'] ?? '')
            );
        })->count();

        $relatedSignals = collect($article->related_tools ?? [])->filter()->count()
            + collect($article->related_models ?? [])->filter()->count()
            + $this->relationCount($article, 'relatedToolTerms')
            + $this->relationCount($article, 'relatedModelTerms');

        $tagSignals = collect($article->tags ?? [])->filter()->count()
            + $this->relationCount($article, 'tagTerms');

        $wordScore = match (true) {
            $wordCount >= 1000 => 34,
            $wordCount >= 800 => 32,
            $wordCount >= 650 => 29,
            $wordCount >= 500 => 26,
            $wordCount >= 400 => 23,
            $wordCount >= 300 => 19,
            $wordCount >= 220 => 12,
            default => (int) round(min(10, ($wordCount / 220) * 10)),
        };

        $structureScore = match (true) {
            $h2Count >= 5 => 12,
            $h2Count >= 4 => 10,
            $h2Count >= 3 => 8,
            $h2Count >= 2 => 5,
            $h2Count === 1 => 2,
            default => 0,
        };

        $score = $wordScore + $structureScore;
        $score += $summaryChars >= 100 ? 12 : ($summaryChars >= 60 ? 8 : 0);
        $score += $practicalHeadingCount >= 2 ? 8 : ($practicalHeadingCount === 1 ? 5 : 0);
        $score += $faqCount >= 3 ? 6 : ($faqCount > 0 ? 4 : 0);
        $score += $listItemCount >= 3 ? 4 : ($listItemCount > 0 ? 2 : 0);
        $score += filled($article->user_id) ? 3 : 0;
        $score += filled($article->reviewer_id) ? 4 : 0;
        $score += (filled($article->category_id) || filled($article->category)) ? 5 : 0;
        $score += $relatedSignals > 0 ? 5 : 0;
        $score += $tagSignals > 0 ? 3 : 0;
        $score += filled($article->featured_image_path) ? 4 : 0;
        $score += (filled($article->seo_title) || filled($article->meta_description)) ? 4 : 0;
        $score += $referenceUrlCount > 0 ? 2 : 0;
        $score = min(100, $score);

        $preferredWords = (int) config('seo_content_quality.article.preferred_words', 700);
        $minimumWords = (int) config('seo_content_quality.article.min_words', 300);
        $minimumSummaryChars = (int) config('seo_content_quality.article.min_summary_chars', 60);
        $minimumHeadings = (int) config('seo_content_quality.article.min_headings', 3);
        $indexThreshold = (int) config('seo_content_quality.article.index_threshold', 65);

        $warnings = [];
        if ($wordCount < $preferredWords) {
            $warnings[] = "Editorial depth is below the preferred {$preferredWords}-word target; expand only where the topic benefits from more explanation, examples or evidence.";
        }
        if ($h2Count < $minimumHeadings && $wordCount < 650) {
            $warnings[] = 'Structure is light; add useful sections or examples when the topic needs them.';
        }
        if ($practicalHeadingCount === 0) {
            $warnings[] = 'No obvious practical/example/decision section was detected.';
        }
        if ($faqCount === 0) {
            $warnings[] = 'No FAQ section detected. This is optional, but useful when the topic has recurring questions.';
        }
        if ($referenceUrlCount === 0) {
            $warnings[] = 'No external references detected. Add primary sources when the article makes time-sensitive or factual claims that need attribution.';
        }
        if ($relatedSignals === 0) {
            $warnings[] = 'No related tool/model links are attached.';
        }

        $reasons = [];
        if ($article->status !== 'published' || $article->approval_status !== 'approved') {
            $reasons[] = 'Article is not published and approved.';
        }
        if ($wordCount < $minimumWords) {
            $reasons[] = "Article is below the minimum {$minimumWords}-word editorial floor.";
        }
        if ($summaryChars < $minimumSummaryChars) {
            $reasons[] = 'Article summary/meta description is too thin.';
        }
        if ($h2Count < $minimumHeadings && $wordCount < 650) {
            $reasons[] = 'Article does not yet have enough structured editorial depth for its length.';
        }
        if ($score < $indexThreshold) {
            $reasons[] = 'Overall editorial-quality score is below the indexing threshold.';
        }

        $reasons = array_values(array_unique($reasons));
        $indexable = $reasons === [];

        return [
            'type' => 'article',
            'indexable' => $indexable,
            'score' => $score,
            'label' => match (true) {
                $indexable && $score >= 85 => 'Strong editorial depth',
                $indexable && $score >= 70 => 'Indexable / solid',
                $indexable => 'Indexable',
                $score >= 60 => 'Needs enrichment',
                default => 'Thin / incomplete',
            },
            'robots' => $indexable
                ? 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1'
                : 'noindex,follow',
            'reasons' => $reasons,
            'quality_warnings' => array_values(array_unique($warnings)),
            'needs_enrichment' => $warnings !== [],
            'outline' => $outline,
            'metrics' => [
                'word_count' => $wordCount,
                'summary_chars' => $summaryChars,
                'heading_count' => $headingCount,
                'h2_count' => $h2Count,
                'paragraph_count' => $paragraphCount,
                'faq_count' => $faqCount,
                'practical_heading_count' => $practicalHeadingCount,
                'list_item_count' => $listItemCount,
                'reference_url_count' => $referenceUrlCount,
                'related_signals' => $relatedSignals,
                'tag_signals' => $tagSignals,
                'preferred_words' => $preferredWords,
            ],
        ];
    }

    private function paragraphCount(string $content): int
    {
        $blocks = preg_split('/\R\s*\R/u', trim($content)) ?: [];

        return collect($blocks)->filter(function (string $block): bool {
            $line = trim($block);
            if ($line === '') {
                return false;
            }
            if (preg_match('/^#{1,6}\s+/u', $line)) {
                return false;
            }
            if (preg_match('/^(?:[-*]\s+|\d+[.)]\s+)/u', $line)) {
                return false;
            }

            return mb_strlen($this->plainText($line)) >= 25;
        })->count();
    }

    private function plainText(?string $value): string
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/^#{1,6}\s+/mu', '', $value) ?? $value;
        $value = preg_replace('/[`*_>#\[\]()]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function wordCount(?string $value): int
    {
        $text = $this->plainText($value);
        if ($text === '') {
            return 0;
        }

        preg_match_all('/[\p{L}\p{N}]+(?:[’\'\-][\p{L}\p{N}]+)*/u', $text, $matches);

        return count($matches[0] ?? []);
    }

    private function relationCount(object $model, string $relation): int
    {
        if (method_exists($model, 'relationLoaded') && $model->relationLoaded($relation)) {
            $value = $model->getRelation($relation);
            return $value instanceof Collection ? $value->count() : ($value ? 1 : 0);
        }

        return method_exists($model, $relation) ? (int) $model->{$relation}()->count() : 0;
    }
}
