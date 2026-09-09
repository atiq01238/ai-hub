<?php

namespace App\Console\Commands;

use App\Models\NewsItem;
use App\Services\NewsEntityLinker;
use App\Services\NewsIntelligenceService;
use App\Services\NewsRelevanceService;
use Illuminate\Console\Command;

class RefreshNewsIntelligence extends Command
{
    protected $signature = 'news:refresh-intelligence {--dry-run}';
    protected $description = 'Recalculate AI relevance, source-aware trending scores and safe entity links.';

    public function handle(
        NewsIntelligenceService $scores,
        NewsEntityLinker $linker,
        NewsRelevanceService $relevance
    ): int {
        $query = NewsItem::with('newsSource')->whereNull('duplicate_of_id');
        $count = $query->count();
        $this->info("Eligible news items: {$count}");

        if ($this->option('dry-run')) {
            $preview = ['accepted' => 0, 'review' => 0, 'rejected' => 0];
            $query->chunkById(200, function ($items) use ($relevance, &$preview) {
                foreach ($items as $news) {
                    $result = $relevance->evaluate($news);
                    $preview[$result['status']] = ($preview[$result['status']] ?? 0) + 1;
                }
            });

            $this->line("Accepted: {$preview['accepted']} | Review: {$preview['review']} | Rejected: {$preview['rejected']}");
            $this->info('Dry run complete. No database changes made.');
            return self::SUCCESS;
        }

        $linked = ['tools' => 0, 'models' => 0, 'companies' => 0];
        $relevanceCounts = ['accepted' => 0, 'review' => 0, 'rejected' => 0];

        $query->chunkById(200, function ($items) use ($scores, $linker, $relevance, &$linked, &$relevanceCounts) {
            foreach ($items as $news) {
                $result = $relevance->apply($news);
                $relevanceCounts[$result['status']] = ($relevanceCounts[$result['status']] ?? 0) + 1;

                if ($relevance->isAccepted($news)) {
                    $matches = $linker->link($news);
                    foreach ($linked as $key => $value) {
                        $linked[$key] += $matches[$key];
                    }
                    $scores->refresh($news->fresh());
                } else {
                    $news->relatedToolTerms()->sync([]);
                    $news->relatedModelTerms()->sync([]);
                    $news->forceFill(['related_tools' => []])->saveQuietly();
                }
            }
        });

        $this->info("Refreshed {$count} news records.");
        $this->line("AI relevance — accepted {$relevanceCounts['accepted']}, review {$relevanceCounts['review']}, rejected {$relevanceCounts['rejected']}.");
        $this->line("Matched {$linked['tools']} tool, {$linked['models']} model and {$linked['companies']} company mentions on accepted stories.");

        return self::SUCCESS;
    }
}
