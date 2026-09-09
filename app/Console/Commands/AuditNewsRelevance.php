<?php

namespace App\Console\Commands;

use App\Models\NewsItem;
use App\Services\NewsEntityLinker;
use App\Services\NewsIntelligenceService;
use App\Services\NewsRelevanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AuditNewsRelevance extends Command
{
    protected $signature = 'news:audit-relevance
                            {--all : Re-evaluate every news item}
                            {--limit=250 : Maximum records when --all is not used}
                            {--apply : Save relevance scores/statuses}
                            {--unpublish : Move published rejected/review items back to draft}
                            {--only-published : Audit only currently published items}';

    protected $description = 'Audit AI relevance, protect entity links, and optionally unpublish non-AI news.';

    public function handle(
        NewsRelevanceService $relevance,
        NewsEntityLinker $linker,
        NewsIntelligenceService $intelligence
    ): int {
        $query = NewsItem::query()->with('newsSource')->orderBy('id');

        if ($this->option('only-published')) {
            $query->where('status', 'published');
        }

        if (! $this->option('all')) {
            $query->where(function ($q) {
                $q->whereNull('ai_relevance_checked_at')
                    ->orWhere('ai_relevance_status', 'pending');
            })->limit(max(1, (int) $this->option('limit')));
        }

        $items = $query->get();
        if ($items->isEmpty()) {
            $this->info('No news records require relevance auditing.');
            return self::SUCCESS;
        }

        $apply = (bool) $this->option('apply');
        $unpublish = $apply && (bool) $this->option('unpublish');
        $counts = ['accepted' => 0, 'review' => 0, 'rejected' => 0, 'unpublished' => 0];

        foreach ($items as $item) {
            $result = $apply ? $relevance->apply($item) : $relevance->evaluate($item);
            $counts[$result['status']] = ($counts[$result['status']] ?? 0) + 1;

            if ($apply) {
                if ($relevance->isAccepted($item)) {
                    $linker->link($item);
                    $intelligence->refresh($item->fresh());
                } else {
                    $item->relatedToolTerms()->sync([]);
                    $item->relatedModelTerms()->sync([]);
                    $item->forceFill(['related_tools' => []])->saveQuietly();
                }

                if ($unpublish && $item->status === 'published' && ! $relevance->isAccepted($item)) {
                    $item->forceFill(['status' => 'draft'])->saveQuietly();
                    $counts['unpublished']++;
                }
            }

            $flag = $result['status'] === 'accepted' ? '✓' : ($result['status'] === 'review' ? '!' : '✗');
            $this->line(sprintf('%s #%d [%d] %s - %s', $flag, $item->id, $result['score'], strtoupper($result['status']), Str::limit((string) $item->headline, 90)));
        }

        $this->newLine();
        $this->info(($apply ? 'Applied' : 'Dry audit') . " to {$items->count()} record(s).");
        $this->line("Accepted: {$counts['accepted']} | Review: {$counts['review']} | Rejected: {$counts['rejected']}");
        if ($unpublish) {
            $this->line("Moved back to draft: {$counts['unpublished']}");
        }

        return self::SUCCESS;
    }
}
