<?php

namespace App\Console\Commands;

use App\Models\NewsSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NewsHealthCheck extends Command
{
    protected $signature = 'news:health-check {--source= : Check one source ID only}';

    protected $description = 'Check active AI news RSS sources and update source health telemetry.';

    public function handle(): int
    {
        if (! Schema::hasTable('news_sources')) {
            $this->error('news_sources table does not exist. Run php artisan migrate.');
            return self::FAILURE;
        }

        $query = NewsSource::query()->where('status', 'active')->orderBy('id');

        if ($this->option('source')) {
            $query->whereKey((int) $this->option('source'));
        }

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->warn('No active news sources found.');
            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($sources as $source) {
            if ($source->type !== 'rss') {
                $this->warn("[{$source->id}] {$source->name}: skipped (type {$source->type})");
                continue;
            }

            if (! filter_var($source->url, FILTER_VALIDATE_URL)) {
                $this->markFailure($source, 'Invalid or missing source URL.');
                $this->error("[{$source->id}] {$source->name}: invalid URL");
                $failed++;
                continue;
            }

            $started = microtime(true);

            try {
                $response = Http::timeout(15)
                    ->connectTimeout(8)
                    ->retry(1, 400, throw: false)
                    ->withHeaders([
                        'Accept' => 'application/rss+xml, application/atom+xml, application/xml, text/xml;q=0.9, */*;q=0.8',
                        'User-Agent' => 'AI-Hub-News-Health/1.0',
                    ])
                    ->get($source->url);

                if (! $response->successful()) {
                    throw new \RuntimeException("HTTP {$response->status()}");
                }

                $body = trim($response->body());
                if ($body === '' || ! $this->looksLikeFeed($body)) {
                    throw new \RuntimeException('Response is not a readable RSS/Atom feed.');
                }

                $durationMs = (int) round((microtime(true) - $started) * 1000);

                $source->forceFill([
                    'last_success_at' => now(),
                    'last_duration_ms' => $durationMs,
                    'consecutive_failures' => 0,
                    'last_error' => null,
                ])->save();

                $this->info("[{$source->id}] {$source->name}: OK ({$response->status()}, {$durationMs} ms)");
            } catch (\Throwable $e) {
                $durationMs = (int) round((microtime(true) - $started) * 1000);
                $this->markFailure($source, $e->getMessage(), $durationMs);
                $this->error("[{$source->id}] {$source->name}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->line("Sources checked: {$sources->count()}");
        $this->line("Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function looksLikeFeed(string $body): bool
    {
        $sample = Str::lower(substr($body, 0, 5000));

        return str_contains($sample, '<rss')
            || str_contains($sample, '<feed')
            || str_contains($sample, '<rdf:rdf');
    }

    private function markFailure(NewsSource $source, string $message, ?int $durationMs = null): void
    {
        $source->forceFill([
            'last_duration_ms' => $durationMs,
            'consecutive_failures' => (int) $source->consecutive_failures + 1,
            'last_error' => Str::limit($message, 1000),
        ])->save();
    }
}
