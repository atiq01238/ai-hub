<?php

namespace App\Console\Commands;

use App\Models\DiscoverySource;
use App\Models\NewsItem;
use App\Models\NewsSource;
use App\Services\Discovery\DiscoveryClassifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScanAiDiscoveries extends Command
{
    protected $signature = 'discovery:scan
                            {--limit=250 : Maximum news items to analyze}
                            {--days=30 : Only analyze news published/created within this many days}
                            {--force : Re-analyze items even if they were analyzed before}';

    protected $description = 'Analyze recent RSS news items for new AI tools, models and meaningful product/model updates.';

    public function handle(DiscoveryClassifier $classifier): int
    {
        $limit = max(1, min(5000, (int) $this->option('limit')));
        $days = max(1, min(3650, (int) $this->option('days')));

        $synced = $this->syncActiveNewsSources();

        $enabledSourceIds = DiscoverySource::query()
            ->where('enabled', true)
            ->whereHas('newsSource', fn ($query) => $query->where('status', 'active'))
            ->pluck('news_source_id');

        if ($enabledSourceIds->isEmpty()) {
            $this->warn('No enabled discovery sources found.');
            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);

        $query = NewsItem::query()
            ->with(['company', 'newsSource.company'])
            ->whereIn('news_source_id', $enabledSourceIds)
            ->where(function ($query) use ($cutoff) {
                $query->where('published_at', '>=', $cutoff)
                    ->orWhere(function ($query) use ($cutoff) {
                        $query->whereNull('published_at')->where('created_at', '>=', $cutoff);
                    });
            });

        if (! $this->option('force')) {
            $query->whereNull('discovery_analyzed_at');
        }

        $items = $query->latest('published_at')->limit($limit)->get();

        $created = 0;
        $analyzed = 0;
        $failed = 0;

        foreach ($items as $item) {
            try {
                $beforeId = \App\Models\AiDiscovery::where('news_item_id', $item->id)->value('id');
                $result = $classifier->analyze($item, (bool) $this->option('force'));

                if ($result && ! $beforeId) {
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

        $this->info("Discovery scan complete: {$created} new candidates from {$analyzed} analyzed news items.");
        $this->line("Coverage: {$enabledSourceIds->count()} enabled active sources · last {$days} days.");

        if ($synced > 0) {
            $this->line("Discovery source sync: {$synced} newly configured source(s).");
        }

        if ($items->isEmpty()) {
            $this->line('No unanalyzed recent news items were waiting. Use Refresh Market in Admin > AI Discovery to fetch fresh RSS data first.');
        }

        if ($failed > 0) {
            $this->warn("Failed items: {$failed}. They remain eligible for retry.");
        }

        return $failed === $items->count() && $items->isNotEmpty() ? self::FAILURE : self::SUCCESS;
    }

    private function syncActiveNewsSources(): int
    {
        $created = 0;

        NewsSource::query()
            ->where('status', 'active')
            ->select(['id', 'company_id', 'name'])
            ->orderBy('id')
            ->chunkById(100, function ($sources) use (&$created) {
                foreach ($sources as $source) {
                    $name = strtolower((string) $source->name);
                    $trusted = ! empty($source->company_id)
                        || str_contains($name, 'openai')
                        || str_contains($name, 'deepmind')
                        || str_contains($name, 'anthropic')
                        || str_contains($name, 'mistral')
                        || str_contains($name, 'meta');

                    $record = DiscoverySource::firstOrCreate(
                        ['news_source_id' => $source->id],
                        [
                            'enabled' => true,
                            'trusted' => $trusted,
                            'detect_tools' => true,
                            'detect_models' => true,
                            'minimum_confidence' => $trusted ? 50 : 60,
                        ]
                    );

                    if ($record->wasRecentlyCreated) {
                        $created++;
                    }
                }
            });

        return $created;
    }
}
