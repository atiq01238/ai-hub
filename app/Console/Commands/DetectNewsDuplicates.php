<?php

namespace App\Console\Commands;

use App\Models\NewsItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DetectNewsDuplicates extends Command
{
    protected $signature = 'news:duplicates
                            {--all : Recheck articles that were checked previously}
                            {--limit=100 : Maximum number of articles to check}';

    protected $description = 'Detect exact and possible duplicate news articles';

    public function handle(): int
    {
        if (! Schema::hasColumn('news_items', 'duplicate_status')) {
            $this->error(
                'Duplicate detection fields are missing. Run php artisan migrate first.'
            );

            return self::FAILURE;
        }

        $limit = min(1000, max(1, (int) $this->option('limit')));

        $query = NewsItem::query()->oldest('id');

        if (! $this->option('all')) {
            $query->whereNull('duplicate_checked_at');
        }

        $items = $query->limit($limit)->get();

        if ($items->isEmpty()) {
            $this->info(
                'No news articles are waiting for duplicate detection.'
            );

            return self::SUCCESS;
        }

        $stats = [
            'checked' => 0,
            'unique' => 0,
            'possible' => 0,
            'duplicate' => 0,
        ];

        foreach ($items as $item) {
            $status = $this->inspectItem($item);

            $stats['checked']++;
            $stats[$status]++;
        }

        $this->info(
            "Checked {$stats['checked']} article(s): "
            . "{$stats['duplicate']} duplicate, "
            . "{$stats['possible']} possible, "
            . "{$stats['unique']} unique."
        );

        return self::SUCCESS;
    }

    private function inspectItem(NewsItem $item): string
    {
        $normalizedHeadline = $item->normalized_headline
            ?: NewsItem::normalizeHeadline($item->headline);

        if (! $normalizedHeadline) {
            return $this->storeResult($item, 'unique');
        }

        /*
         * First check exact matches using content hash,
         * canonical URL or normalized headline.
         */
        $exactMatch = NewsItem::query()
            ->where('id', '<', $item->id)
            ->where(function ($query) use (
                $item,
                $normalizedHeadline
            ) {
                if ($item->content_hash) {
                    $query->orWhere(
                        'content_hash',
                        $item->content_hash
                    );
                }

                if ($item->canonical_url) {
                    $query->orWhere(
                        'canonical_url',
                        $item->canonical_url
                    );
                }

                $query->orWhere(
                    'normalized_headline',
                    $normalizedHeadline
                );
            })
            ->oldest('id')
            ->first([
                'id',
                'duplicate_of_id',
            ]);

        if ($exactMatch) {
            return $this->storeResult(
                $item,
                'duplicate',
                100,
                $exactMatch->duplicate_of_id ?: $exactMatch->id
            );
        }

        /*
         * No exact match was found, so compare the headline
         * against recent existing articles.
         */
        $bestMatch = null;
        $bestScore = 0.0;

        $candidates = NewsItem::query()
            ->where('id', '<', $item->id)
            ->whereNotNull('normalized_headline')
            ->latest('id')
            ->limit(500)
            ->get([
                'id',
                'duplicate_of_id',
                'normalized_headline',
            ]);

        foreach ($candidates as $candidate) {
            $score = $this->headlineScore(
                $normalizedHeadline,
                (string) $candidate->normalized_headline
            );

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $candidate;
            }
        }

        if ($bestMatch && $bestScore >= 92) {
            return $this->storeResult(
                $item,
                'duplicate',
                $bestScore,
                $bestMatch->duplicate_of_id ?: $bestMatch->id
            );
        }

        if ($bestMatch && $bestScore >= 78) {
            return $this->storeResult(
                $item,
                'possible',
                $bestScore,
                $bestMatch->duplicate_of_id ?: $bestMatch->id
            );
        }

        return $this->storeResult($item, 'unique');
    }

    private function headlineScore(
        string $left,
        string $right
    ): float {
        similar_text(
            $left,
            $right,
            $characterScore
        );

        $leftTokens = $this->headlineTokens($left);
        $rightTokens = $this->headlineTokens($right);

        $union = array_unique(
            array_merge($leftTokens, $rightTokens)
        );

        $tokenScore = $union === []
            ? 0
            : (
                count(array_intersect(
                    $leftTokens,
                    $rightTokens
                )) / count($union)
            ) * 100;

        return round(
            max($characterScore, $tokenScore),
            2
        );
    }

    private function headlineTokens(string $headline): array
    {
        $tokens = preg_split(
            '/\s+/u',
            trim($headline)
        ) ?: [];

        return array_values(
            array_unique(
                array_filter(
                    $tokens,
                    static fn (string $token): bool =>
                        strlen($token) >= 4
                )
            )
        );
    }

    private function storeResult(
        NewsItem $item,
        string $status,
        ?float $score = null,
        ?int $duplicateOfId = null
    ): string {
        $item->forceFill([
            'duplicate_status' => $status,
            'duplicate_score' => $score,
            'duplicate_of_id' => $duplicateOfId,
            'duplicate_checked_at' => now(),
        ])->saveQuietly();

        return $status;
    }
}