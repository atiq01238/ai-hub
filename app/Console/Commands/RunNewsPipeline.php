<?php

namespace App\Console\Commands;

use App\Models\NewsSource;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class RunNewsPipeline extends Command
{
    protected $signature = 'news:pipeline
                            {--limit=100 : Approximate maximum new articles handled per run}
                            {--skip-ai : Skip local AI processing}
                            {--source= : Fetch only one source}';

    protected $description = 'Run the AI Hub news automation pipeline: fetch, deduplicate, and process.';

    public function handle(): int
    {
        $lock = Cache::lock('ai-hub-news-pipeline', 3300);

        if (! $lock->get()) {
            $this->warn('Another news pipeline is already running. This run was skipped.');
            $this->recordFinished('skipped', 0, 'Another pipeline run was already active.');
            return self::SUCCESS;
        }

        $startedAt = now();
        $started = microtime(true);
        $failedSteps = [];

        $this->recordStarted($startedAt);

        try {
            $limit = max(1, (int) $this->option('limit'));

            $this->info('AI Hub News Automation Pipeline started.');
            $this->newLine();

            $sourceCount = 1;
            if (! $this->option('source') && Schema::hasTable('news_sources')) {
                $sourceCount = max(1, NewsSource::where('status', 'active')->count());
            }

            $perSourceLimit = max(1, (int) ceil($limit / $sourceCount));

            $fetchOptions = ['--limit' => $perSourceLimit];
            if ($this->option('source')) {
                $fetchOptions['--source'] = $this->option('source');
            }

            if (! $this->runStep('RSS collection', 'news:fetch', $fetchOptions)) {
                $failedSteps[] = 'RSS collection';
            }

            if ($this->commandExists('discovery:scan')) {
                if (! $this->runStep('AI discovery scan', 'discovery:scan', [
                    '--limit' => $limit,
                ])) {
                    $failedSteps[] = 'AI discovery scan';
                }
            } else {
                $this->error('discovery:scan command not found.');
                $failedSteps[] = 'AI discovery scan';
            }

            if ($this->commandExists('news:duplicates')) {
                if (! $this->runStep('Duplicate detection', 'news:duplicates', [
                    '--all' => true,
                    '--limit' => $limit,
                ])) {
                    $failedSteps[] = 'Duplicate detection';
                }
            } else {
                $this->error('Duplicate command not found.');
                $failedSteps[] = 'Duplicate detection';
            }

            if (! $this->option('skip-ai')) {
                if ($this->commandExists('news:process-ai')) {
                    if (! $this->runStep('Local AI processing', 'news:process-ai', [
                        '--limit' => $limit,
                    ])) {
                        $failedSteps[] = 'Local AI processing';
                    }
                } else {
                    $this->error('news:process-ai command not found.');
                    $failedSteps[] = 'Local AI processing';
                }
            }

            $seconds = round(microtime(true) - $started, 2);
            $this->newLine();

            if ($failedSteps !== []) {
                $message = 'Pipeline finished with failures: ' . implode(', ', $failedSteps) . ". ({$seconds}s)";
                $this->error($message);
                $this->recordFinished('failed', $seconds, $message);
                return self::FAILURE;
            }

            $message = "Pipeline finished successfully in {$seconds}s.";
            $this->info($message);
            $this->recordFinished('success', $seconds, $message);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $seconds = round(microtime(true) - $started, 2);
            $message = 'Pipeline crashed: ' . $e->getMessage();
            $this->error($message);
            $this->recordFinished('failed', $seconds, $message);
            return self::FAILURE;
        } finally {
            optional($lock)->release();
        }
    }

    private function runStep(string $label, string $command, array $arguments = []): bool
    {
        $this->line("▶ {$label}...");

        try {
            $exitCode = Artisan::call($command, $arguments);
            $output = trim(Artisan::output());

            if ($output !== '') {
                $this->line($output);
            }

            if ($exitCode !== 0) {
                $this->error("✗ {$label} failed with exit code {$exitCode}.");
                $this->newLine();
                return false;
            }
        } catch (\Throwable $e) {
            $this->error("✗ {$label} crashed: {$e->getMessage()}");
            $this->newLine();
            return false;
        }

        $this->info("✓ {$label} completed.");
        $this->newLine();
        return true;
    }

    private function commandExists(string $signature): bool
    {
        return array_key_exists($signature, Artisan::all());
    }

    private function recordStarted($startedAt): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        Setting::set('ai_news_last_run_started_at', $startedAt->toDateTimeString());
        Setting::set('ai_news_last_run_status', 'running');
        Setting::set('ai_news_last_run_message', 'Pipeline is running.');
    }

    private function recordFinished(string $status, float|int $seconds, string $message): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }

        Setting::set('ai_news_last_run_finished_at', now()->toDateTimeString());
        Setting::set('ai_news_last_run_status', $status);
        Setting::set('ai_news_last_run_duration_seconds', (string) $seconds);
        Setting::set('ai_news_last_run_message', mb_substr($message, 0, 1000));
    }
}
