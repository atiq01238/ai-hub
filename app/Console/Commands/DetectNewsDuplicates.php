<?php

namespace App\Console\Commands;

use App\Models\NewsItem;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DetectNewsDuplicates extends Command
{
    protected $signature = 'news:duplicates
                            {--id= : Check one news item}
                            {--all : Re-check all existing news items}
                            {--limit=200 : Maximum items to process when using --all}
                            {--threshold=82 : Score at or above this value is a duplicate}
                            {--possible=65 : Score at or above this value is a possible duplicate}
                            {--days=30 : Only compare against articles from this many days back}
                            {--force : Re-check items that are already marked duplicate/possible}';

    protected $description = 'Detect cross-source duplicate AI news using normalized headlines and lightweight text similarity.';

    public function handle(): int
    {
        $threshold = max(50.0, min(100.0, (float) $this->option('threshold')));
        $possible = max(40.0, min($threshold - 1, (float) $this->option('possible')));
        $days = max(1, (int) $this->option('days'));

        if ($this->option('id')) {
            $item = NewsItem::find((int) $this->option('id'));

            if (! $item) {
                $this->error('News item not found.');

                return self::FAILURE;
            }

            $this->checkItem($item, $threshold, $possible, $days);

            return self::SUCCESS;
        }

        if (! $this->option('all')) {
            $this->info('Nothing to do. Use --id=123 or --all.');

            return self::SUCCESS;
        }

        $query = NewsItem::query()
            ->orderBy('id');

        if (! $this->option('force')) {
            // Only unchecked/newly changed rows. NewsItem resets this field
            // whenever headline/summary changes, so old unique rows do not
            // consume the limit on every scheduled pipeline run.
            $query->whereNull('duplicate_checked_at');
        }

        $limit = max(1, (int) $this->option('limit'));
        $items = $query->limit($limit)->get();

        $this->info("Checking {$items->count()} news item(s)...");

        $counts = [
            'unique' => 0,
            'possible' => 0,
            'duplicate' => 0,
        ];

        foreach ($items as $item) {
            $status = $this->checkItem(
                $item,
                $threshold,
                $possible,
                $days
            );

            $counts[$status]++;
        }

        $this->newLine();
        $this->info('Duplicate detection finished.');
        $this->line("Unique: {$counts['unique']}");
        $this->line("Possible duplicates: {$counts['possible']}");
        $this->line("Duplicates: {$counts['duplicate']}");

        return self::SUCCESS;
    }

    private function checkItem(
        NewsItem $item,
        float $threshold,
        float $possibleThreshold,
        int $days
    ): string {
        $best = $this->findBestMatch(
            $item,
            $possibleThreshold,
            $days
        );

        if (! $best) {
            $this->markUnique($item);

            $this->line(
                "[{$item->id}] UNIQUE — {$item->headline}"
            );

            return 'unique';
        }

        $status = $best['score'] >= $threshold
            ? 'duplicate'
            : 'possible';

        // The older article is the canonical/primary article.
        // We never make the newer article the parent of an older one.
        $primary = $this->choosePrimary($item, $best['item']);

        $item->forceFill([
            'duplicate_of_id' => $primary->id,
            'duplicate_score' => $best['score'],
            'duplicate_status' => $status,
            'duplicate_checked_at' => now(),
        ])->save();

        $this->line(sprintf(
            '[%d] %s %.2f%% → #%d | %s',
            $item->id,
            strtoupper($status),
            $best['score'],
            $best['item']->id,
            $item->headline
        ));

        return $status;
    }

    private function findBestMatch(
        NewsItem $item,
        float $possibleThreshold,
        int $days
    ): ?array {
        $headline = $item->normalized_headline
            ?: NewsItem::normalizeHeadline($item->headline);

        if (! $headline) {
            return null;
        }

        $tokens = $this->tokens($headline);

        if ($tokens === []) {
            return null;
        }

        // Compare recent articles only. This prevents an old article with
        // generic words from becoming a false duplicate years later.
        $from = now()->subDays($days);

        $query = NewsItem::query()
            ->whereKeyNot($item->id)
            ->where('id', '<', $item->id)
            ->where(function (Builder $q) use ($from) {
                $q->where('published_at', '>=', $from)
                    ->orWhereNull('published_at');
            });

        // Strong signal: duplicate stories are normally reported by
        // different sources, so source itself is not a negative factor.
        // Company/category are used only as candidate narrowing hints.
        if ($item->company_id) {
            $query->where(function (Builder $q) use ($item) {
                $q->where('company_id', $item->company_id)
                    ->orWhereNull('company_id');
            });
        }

        $candidates = $query
            ->select([
                'id',
                'news_source_id',
                'company_id',
                'headline',
                'normalized_headline',
                'summary',
                'category',
                'published_at',
                'content_hash',
                'duplicate_status',
                'duplicate_of_id',
            ])
            ->latest('published_at')
            ->limit(300)
            ->get();

        $best = null;

        foreach ($candidates as $candidate) {
            // Exact canonical content match is a very strong signal.
            if (
                $item->content_hash
                && $candidate->content_hash
                && hash_equals(
                    (string) $item->content_hash,
                    (string) $candidate->content_hash
                )
            ) {
                $score = 100.0;
            } else {
                $score = $this->similarityScore(
                    $headline,
                    $candidate->normalized_headline
                        ?: NewsItem::normalizeHeadline($candidate->headline),
                    $item->summary,
                    $candidate->summary
                );
            }

            if ($score < $possibleThreshold) {
                continue;
            }

            if ($best === null || $score > $best['score']) {
                $best = [
                    'item' => $candidate,
                    'score' => $score,
                ];
            }
        }

        return $best;
    }

    private function similarityScore(
        string $headlineA,
        ?string $headlineB,
        ?string $summaryA,
        ?string $summaryB
    ): float {
        if (! $headlineB) {
            return 0.0;
        }

        $headlineA = $this->normalizeForSimilarity($headlineA);
        $headlineB = $this->normalizeForSimilarity($headlineB);

        if ($headlineA === '' || $headlineB === '') {
            return 0.0;
        }

        if ($headlineA === $headlineB) {
            return 100.0;
        }

        similar_text($headlineA, $headlineB, $charPercent);

        $tokensA = $this->tokens($headlineA);
        $tokensB = $this->tokens($headlineB);

        $headlineJaccard = $this->jaccard($tokensA, $tokensB);
        $headlineContainment = $this->containment($tokensA, $tokensB);

        // Character similarity catches small wording differences.
        // Token similarity catches reordered/shared keywords.
        $score = (
            ($charPercent * 0.35)
            + ($headlineJaccard * 100 * 0.40)
            + ($headlineContainment * 100 * 0.25)
        );

        // A small summary signal helps distinguish headlines that share
        // broad terms but describe different stories.
        $summaryA = $this->normalizeForSimilarity(
            NewsItem::normalizeHeadline($summaryA) ?? ''
        );
        $summaryB = $this->normalizeForSimilarity(
            NewsItem::normalizeHeadline($summaryB) ?? ''
        );

        if ($summaryA !== '' && $summaryB !== '') {
            similar_text($summaryA, $summaryB, $summaryPercent);

            $score = ($score * 0.85) + ($summaryPercent * 0.15);
        }

        return round(min(100.0, $score), 2);
    }

    private function normalizeForSimilarity(string $value): string
    {
        $value = Str::lower($value);
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);

        return $value;
    }

    private function tokens(string $value): array
    {
        $stopWords = [
            'the', 'a', 'an', 'and', 'or', 'but', 'for', 'with', 'from',
            'into', 'onto', 'over', 'under', 'after', 'before', 'about',
            'this', 'that', 'these', 'those', 'new', 'latest', 'says',
            'said', 'will', 'has', 'have', 'its', 'their', 'how', 'why',
            'what', 'who', 'to', 'of', 'in', 'on', 'at', 'by', 'as', 'is',
            'are', 'be', 'was', 'were',
        ];

        $tokens = preg_split('/\s+/u', trim($value)) ?: [];

        $tokens = array_map(
            fn ($token) => trim($token, " \t\n\r\0\x0B.,!?;:'\"()[]{}"),
            $tokens
        );

        $tokens = array_filter(
            $tokens,
            fn ($token) => mb_strlen($token) >= 3
                && ! in_array($token, $stopWords, true)
        );

        return array_values(array_unique($tokens));
    }

    private function jaccard(array $a, array $b): float
    {
        $a = array_unique($a);
        $b = array_unique($b);

        if ($a === [] || $b === []) {
            return 0.0;
        }

        $intersection = count(array_intersect($a, $b));
        $union = count(array_unique(array_merge($a, $b)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    private function containment(array $a, array $b): float
    {
        $a = array_unique($a);
        $b = array_unique($b);

        if ($a === [] || $b === []) {
            return 0.0;
        }

        $intersection = count(array_intersect($a, $b));
        $smallest = min(count($a), count($b));

        return $smallest > 0 ? $intersection / $smallest : 0.0;
    }

    private function choosePrimary(
        NewsItem $current,
        NewsItem $candidate
    ): NewsItem {
        // Candidates are intentionally restricted to an older database ID,
        // so the current article can never point to itself. If the candidate
        // is already a duplicate, link directly to its root canonical item
        // instead of creating a duplicate chain.
        if ($candidate->duplicate_of_id) {
            $root = NewsItem::find($candidate->duplicate_of_id);

            if ($root && $root->id !== $current->id) {
                return $root;
            }
        }

        return $candidate;
    }

    private function markUnique(NewsItem $item): void
    {
        $item->forceFill([
            'duplicate_of_id' => null,
            'duplicate_score' => null,
            'duplicate_status' => 'unique',
            'duplicate_checked_at' => now(),
        ])->save();
    }
}
