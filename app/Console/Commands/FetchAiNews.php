<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\NewsItem;
use App\Models\NewsSource;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FetchAiNews extends Command
{
    protected $signature = 'news:fetch
                            {--source= : Fetch only the source with this ID}
                            {--limit=50 : Maximum feed entries processed per source}
                            {--timeout=20 : HTTP timeout in seconds}';

    protected $description = 'Fetch active AI news RSS sources, normalize entries, skip exact duplicates, and store new articles for processing.';

    public function handle(): int
    {
        $query = NewsSource::query()
            ->where('status', 'active')
            ->orderBy('id');

        if ($sourceId = $this->option('source')) {
            $query->whereKey((int) $sourceId);
        }

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->warn(
                $this->option('source')
                    ? 'The requested source does not exist or is not active.'
                    : 'No active news sources found.'
            );

            return self::SUCCESS;
        }

        $totalCreated = 0;
        $totalSkipped = 0;
        $totalSeen = 0;
        $failed = 0;

        $this->info('AI Hub News Collection started.');
        $this->line('Sources: ' . $sources->count());

        foreach ($sources as $source) {
            $result = $this->fetchSource($source);

            $totalSeen += $result['seen'];
            $totalCreated += $result['created'];
            $totalSkipped += $result['skipped'];

            if (! $result['success']) {
                $failed++;
            }
        }

        $this->newLine();
        $this->info('Collection finished.');
        $this->line("Entries seen: {$totalSeen}");
        $this->line("New articles: {$totalCreated}");
        $this->line("Skipped duplicates/invalid: {$totalSkipped}");

        if ($failed > 0) {
            $this->warn("Failed sources: {$failed}");

            // A partial run is still useful, but if every requested source
            // failed the pipeline must report a real failure.
            if ($failed === $sources->count()) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    private function fetchSource(NewsSource $source): array
    {
        $startedAt = microtime(true);

        $source->forceFill([
            'last_started_at' => now(),
            'last_error' => null,
        ])->save();

        $this->newLine();
        $this->info("[{$source->id}] {$source->name}");

        try {
            if ($source->type !== 'rss') {
                throw new \RuntimeException(
                    "Source type [{$source->type}] is not supported by this collector yet. "
                    . 'Only RSS/Atom feeds are active in Step 2.'
                );
            }

            $response = $this->requestFeed($source);

            if (! $response->successful()) {
                throw new \RuntimeException(
                    "HTTP {$response->status()} returned by {$source->url}"
                );
            }

            $entries = $this->parseFeed($response->body());

            if ($entries === []) {
                throw new \RuntimeException('The feed returned zero readable RSS/Atom entries.');
            }

            $limit = max(1, (int) $this->option('limit'));
            $entries = array_slice($entries, 0, $limit);

            $created = 0;
            $skipped = 0;

            foreach ($entries as $entry) {
                $outcome = $this->storeEntry($source, $entry);

                if ($outcome === 'created') {
                    $created++;
                } else {
                    $skipped++;
                }
            }

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $source->forceFill([
                'last_fetched_at' => now(),
                'last_success_at' => now(),
                'articles_collected' => (int) $source->articles_collected + $created,
                'last_items_seen' => count($entries),
                'last_items_created' => $created,
                'last_items_skipped' => $skipped,
                'last_duration_ms' => $durationMs,
                'consecutive_failures' => 0,
                'last_error' => null,
            ])->save();

            $this->line(
                "  ✓ {$created} new | {$skipped} skipped | "
                . count($entries) . " seen | {$durationMs} ms"
            );

            return [
                'success' => true,
                'seen' => count($entries),
                'created' => $created,
                'skipped' => $skipped,
            ];
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $source->forceFill([
                'last_fetched_at' => now(),
                'last_duration_ms' => $durationMs,
                'consecutive_failures' => (int) $source->consecutive_failures + 1,
                'last_error' => Str::limit($e->getMessage(), 1000),
            ])->save();

            $this->error("  ✗ {$e->getMessage()}");

            return [
                'success' => false,
                'seen' => 0,
                'created' => 0,
                'skipped' => 0,
            ];
        }
    }

    private function requestFeed(NewsSource $source): Response
    {
        $timeout = max(5, (int) $this->option('timeout'));

        return Http::timeout($timeout)
            ->connectTimeout(min($timeout, 10))
            ->retry(2, 500, throw: false)
            ->withHeaders([
                'Accept' => 'application/rss+xml, application/atom+xml, application/xml, text/xml;q=0.9, */*;q=0.8',
                'User-Agent' => 'AI-Hub-News-Fetcher/1.0 (+AI Intelligence)',
            ])
            ->get($source->url);
    }

    private function storeEntry(NewsSource $source, array $entry): string
    {
        $title = $this->cleanText($entry['title'] ?? '');

        if ($title === '') {
            return 'skipped';
        }

        $url = $this->canonicalizeUrl($entry['link'] ?? '');

        if ($url === '') {
            return 'skipped';
        }

        $sourceItemId = $this->cleanSourceItemId($entry['source_item_id'] ?? null);

        // Exact URL duplicate, even if the source record changed.
        $duplicate = NewsItem::query()
            ->where(function ($query) use ($url) {
                $query->where('canonical_url', $url)
                    ->orWhere('source_url', $url);
            })
            ->exists();

        // Feed GUID/ID duplicate inside the same source.
        if (! $duplicate && $sourceItemId !== null) {
            $duplicate = NewsItem::query()
                ->where('news_source_id', $source->id)
                ->where('source_item_id', $sourceItemId)
                ->exists();
        }

        if ($duplicate) {
            return 'skipped';
        }

        $summary = $this->cleanText($entry['summary'] ?? '');
        $headline = Str::limit($title, 255, '');

        $companyId = $source->company_id ?: $this->guessCompany($headline);

        NewsItem::create([
            'news_source_id' => $source->id,
            'company_id' => $companyId,
            'headline' => $headline,
            'slug' => $this->uniqueSlug($headline),
            'summary' => Str::limit($summary, 500, ''),
            'category' => $source->default_category,
            'source' => $source->name,
            'source_url' => Str::limit($url, 2048, ''),
            'source_item_id' => $sourceItemId,
            'canonical_url' => Str::limit($url, 2048, ''),
            'sentiment' => 'neutral',
            'importance' => 50,
            'verification_status' => 'unverified',
            'processing_status' => 'pending',
            'status' => 'draft',
            'published_at' => $entry['published_at'] ?? now(),
            'fetched_at' => now(),
        ]);

        return 'created';
    }

    private function parseFeed(string $xml): array
    {
        libxml_use_internal_errors(true);

        $doc = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        if ($doc === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            $message = $errors[0]->message ?? 'Unknown XML parsing error.';
            throw new \RuntimeException('Could not parse feed XML: ' . trim($message));
        }

        $entries = [];

        if (isset($doc->channel->item)) {
            foreach ($doc->channel->item as $item) {
                $entries[] = [
                    'title' => (string) $item->title,
                    'link' => (string) $item->link,
                    'summary' => (string) ($item->description ?? ''),
                    'source_item_id' => (string) ($item->guid ?? ''),
                    'published_at' => $this->parseDate((string) ($item->pubDate ?? '')),
                ];
            }
        } elseif (isset($doc->entry)) {
            foreach ($doc->entry as $entry) {
                $link = '';

                foreach ($entry->link as $linkNode) {
                    $attributes = $linkNode->attributes();

                    if (
                        ! isset($attributes['rel'])
                        || (string) $attributes['rel'] === 'alternate'
                    ) {
                        $link = (string) ($attributes['href'] ?? $linkNode);
                        break;
                    }
                }

                $entries[] = [
                    'title' => (string) $entry->title,
                    'link' => $link,
                    'summary' => (string) ($entry->summary ?? $entry->content ?? ''),
                    'source_item_id' => (string) ($entry->id ?? ''),
                    'published_at' => $this->parseDate(
                        (string) ($entry->published ?? $entry->updated ?? '')
                    ),
                ];
            }
        }

        return $entries;
    }

    private function parseDate(?string $value): ?\DateTime
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return new \DateTime($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function cleanText(?string $value): string
    {
        $value = html_entity_decode(
            strip_tags((string) $value),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function canonicalizeUrl(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '' || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($value);

        if (! $parts || empty($parts['host'])) {
            return '';
        }

        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = strtolower($parts['host']);

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        $path = $parts['path'] ?? '/';
        $path = preg_replace('#/+#', '/', $path) ?: '/';

        // Tracking parameters should not make the same article look new.
        $tracking = [
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_term',
            'utm_content',
            'utm_id',
            'gclid',
            'fbclid',
            'mc_cid',
            'mc_eid',
        ];

        $query = [];

        if (! empty($parts['query'])) {
            parse_str($parts['query'], $params);

            foreach ($params as $key => $param) {
                if (! in_array(strtolower($key), $tracking, true)) {
                    $query[$key] = $param;
                }
            }

            ksort($query);
        }

        $url = $scheme . '://' . $host . $path;

        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return rtrim($url, '/') ?: $url;
    }

    private function cleanSourceItemId(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return Str::limit($value, 191, '');
    }

    private function uniqueSlug(string $headline): string
    {
        $base = Str::slug(Str::limit($headline, 80, '')) ?: 'news-item';
        $slug = $base . '-' . Str::lower(Str::random(6));

        while (NewsItem::where('slug', $slug)->exists()) {
            $slug = $base . '-' . Str::lower(Str::random(6));
        }

        return $slug;
    }

    private function guessCompany(string $headline): ?int
    {
        static $companies = null;

        $companies ??= Company::query()->pluck('id', 'name');

        foreach ($companies as $name => $id) {
            $name = trim((string) $name);

            if ($name !== '' && Str::contains($headline, $name, ignoreCase: true)) {
                return $id;
            }
        }

        return null;
    }
}
