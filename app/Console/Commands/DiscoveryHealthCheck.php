<?php

namespace App\Console\Commands;

use App\Models\AiDiscovery;
use App\Models\AppNotification;
use App\Models\DiscoverySource;
use App\Models\NewsItem;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DiscoveryHealthCheck extends Command
{
    protected $signature = 'discovery:health-check {--notify : Create an admin notification when attention is required}';

    protected $description = 'Check AI Discovery readiness, backlog and source coverage.';

    public function handle(): int
    {
        $required = ['news_items', 'news_sources', 'discovery_sources', 'ai_discoveries'];
        $missing = collect($required)->reject(fn ($table) => Schema::hasTable($table))->values();

        if ($missing->isNotEmpty()) {
            $message = 'Missing tables: '.$missing->implode(', ').'. Run php artisan migrate.';
            $this->error($message);
            $this->record('failed', $message);
            $this->notifyOnce($message);
            return self::FAILURE;
        }

        if (! Schema::hasColumn('news_items', 'discovery_analyzed_at')) {
            $message = 'Discovery tracking column is missing. Run php artisan migrate.';
            $this->error($message);
            $this->record('failed', $message);
            $this->notifyOnce($message);
            return self::FAILURE;
        }

        $enabledSourceIds = DiscoverySource::where('enabled', true)->pluck('news_source_id');
        $enabledSources = $enabledSourceIds->count();
        $trustedSources = DiscoverySource::where('enabled', true)->where('trusted', true)->count();
        $unanalyzed = $enabledSourceIds->isEmpty() ? 0 : NewsItem::whereIn('news_source_id', $enabledSourceIds)->whereNull('discovery_analyzed_at')->count();
        $pending = AiDiscovery::where('status', 'pending')->count();
        $highConfidence = AiDiscovery::where('status', 'pending')->where('confidence', '>=', 85)->count();

        $issues = [];
        if ($enabledSources === 0) {
            $issues[] = 'No discovery sources are enabled';
        }
        if ($unanalyzed > 1000) {
            $issues[] = "{$unanalyzed} RSS items are waiting for discovery analysis";
        }
        if ($highConfidence > 100) {
            $issues[] = "{$highConfidence} high-confidence discoveries need review";
        }

        $status = $issues === [] ? 'healthy' : 'attention';
        $message = $issues === []
            ? "Discovery healthy: {$enabledSources} enabled sources ({$trustedSources} trusted), {$pending} pending discoveries, {$unanalyzed} items awaiting analysis."
            : implode('; ', $issues).'.';

        $this->line($message);
        $this->record($status, $message);

        if ($issues !== []) {
            $this->notifyOnce($message);
        }

        return self::SUCCESS;
    }

    private function record(string $status, string $message): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        Setting::set('ai_discovery_health_status', $status);
        Setting::set('ai_discovery_health_checked_at', now()->toDateTimeString());
        Setting::set('ai_discovery_health_message', mb_substr($message, 0, 1000));
    }

    private function notifyOnce(string $message): void
    {
        if (! $this->option('notify') || ! Schema::hasTable('notifications')) {
            return;
        }

        $recent = AppNotification::query()
            ->where('type', 'ai_discovery_health')
            ->where('created_at', '>=', now()->subHours(6))
            ->exists();

        if (! $recent) {
            AppNotification::broadcast(
                'triangle-alert',
                'warning',
                'AI Discovery needs attention',
                mb_substr($message, 0, 500),
                url('/admin/discovery'),
                'ai_discovery_health'
            );
        }
    }
}
