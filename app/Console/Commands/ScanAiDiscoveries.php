<?php

namespace App\Console\Commands;

use App\Models\DiscoverySource;
use App\Models\NewsItem;
use App\Services\Discovery\DiscoveryClassifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScanAiDiscoveries extends Command
{
    protected $signature = 'discovery:scan
                            {--limit=250 : Maximum news items to analyze}
                            {--force : Re-analyze items even if they were analyzed before}';

    protected $description = 'Analyze RSS news items for new AI tools, models and meaningful product/model updates.';

    public function handle(DiscoveryClassifier $classifier): int
    {
        $limit = max(1, min(5000, (int) $this->option('limit')));

        $enabledSourceIds = DiscoverySource::where('enabled', true)->pluck('news_source_id');

        if ($enabledSourceIds->isEmpty()) {
            $this->warn('No enabled discovery sources found.');
            return self::SUCCESS;
        }

        $query = NewsItem::query()
            ->with(['company', 'newsSource.company'])
            ->whereIn('news_source_id', $enabledSourceIds);

        if (! $this->option('force')) {
            $query->whereNull('discovery_analyzed_at');
        }

        $items = $query->latest('published_at')->limit($limit)->get();

        $created = 0;
        $analyzed = 0;
        $failed = 0;

        foreach ($items as $item) {
            try {
                if ($classifier->analyze($item, (bool) $this->option('force'))) {
                    $created++;
                }
                $analyzed++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Scheduled AI discovery scan failed for news item '.$item->id, [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Discovery scan complete: {$created} candidates from {$analyzed} analyzed news items.");

        if ($failed > 0) {
            $this->warn("Failed items: {$failed}. They remain eligible for retry.");
        }

        return $failed === $items->count() && $items->isNotEmpty() ? self::FAILURE : self::SUCCESS;
    }
}
