<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\NewsItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FetchAiNews extends Command
{
    protected $signature = 'news:fetch';

    protected $description = 'Pull the latest items from every RSS source in config/news_sources.php and store new ones as unverified NewsItem rows for admin review.';

    public function handle(): int
    {
        $sources = config('news_sources', []);

        if (empty($sources)) {
            $this->warn('No sources configured — add feeds to config/news_sources.php first.');

            return self::SUCCESS;
        }

        $totalCreated = 0;

        foreach ($sources as $source) {
            $this->info("Fetching: {$source['name']}...");

            try {
                $created = $this->fetchSource($source);
                $totalCreated += $created;
                $this->line("  → {$created} new item(s).");
            } catch (\Throwable $e) {
                // One bad/unreachable feed shouldn't stop the rest from running.
                $this->error("  → Failed: {$e->getMessage()}");
            }
        }

        $this->info("Done. {$totalCreated} new news item(s) created (status: unverified).");

        return self::SUCCESS;
    }

    private function fetchSource(array $source): int
    {
        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'AI-Hub-News-Fetcher/1.0'])
            ->get($source['url']);

        if (! $response->successful()) {
            throw new \RuntimeException("HTTP {$response->status()}");
        }

        $entries = $this->parseFeed($response->body());
        $created = 0;

        foreach ($entries as $entry) {
            if (empty($entry['link']) || empty($entry['title'])) {
                continue;
            }

            // De-dupe on the exact source URL — the cheapest, most reliable
            // check. Cross-source similarity (same story, different site)
            // is a separate feature (Duplicate News Detection) that runs
            // on top of what lands here, not inside the fetcher itself.
            if (NewsItem::where('source_url', $entry['link'])->exists()) {
                continue;
            }

            NewsItem::create([
                'company_id'           => $source['company_id'] ?? $this->guessCompany($entry['title']),
                'headline'             => Str::limit(strip_tags($entry['title']), 255, ''),
                'slug'                 => Str::slug(Str::limit($entry['title'], 80, '')) . '-' . Str::random(6),
                'summary'              => Str::limit(strip_tags($entry['summary'] ?? ''), 500),
                'category'             => $source['category'] ?? null,
                'source'               => $source['name'],
                'source_url'           => $entry['link'],
                'sentiment'            => 'neutral',
                'importance'           => 50,
                'verification_status'  => 'unverified', // admin reviews before it's trusted
                'status'               => 'draft',       // admin publishes manually
                'published_at'         => $entry['published_at'] ?? now(),
            ]);

            $created++;
        }

        return $created;
    }

    /**
     * Parses both RSS 2.0 (<item>) and Atom (<entry>) feeds, since AI blogs
     * use a mix of both.
     */
    private function parseFeed(string $xml): array
    {
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml);

        if ($doc === false) {
            throw new \RuntimeException('Could not parse feed XML.');
        }

        $entries = [];

        if (isset($doc->channel->item)) {
            // RSS 2.0
            foreach ($doc->channel->item as $item) {
                $entries[] = [
                    'title'         => (string) $item->title,
                    'link'          => (string) $item->link,
                    'summary'       => (string) ($item->description ?? ''),
                    'published_at'  => $this->parseDate((string) ($item->pubDate ?? '')),
                ];
            }
        } elseif (isset($doc->entry)) {
            // Atom
            foreach ($doc->entry as $entry) {
                $link = '';
                foreach ($entry->link as $l) {
                    $attrs = $l->attributes();
                    if (! isset($attrs['rel']) || (string) $attrs['rel'] === 'alternate') {
                        $link = (string) $attrs['href'];
                        break;
                    }
                }

                $entries[] = [
                    'title'        => (string) $entry->title,
                    'link'         => $link,
                    'summary'      => (string) ($entry->summary ?? $entry->content ?? ''),
                    'published_at' => $this->parseDate((string) ($entry->updated ?? $entry->published ?? '')),
                ];
            }
        }

        return $entries;
    }

    private function parseDate(string $value): ?\DateTime
    {
        if (! $value) {
            return null;
        }

        try {
            return new \DateTime($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Best-effort match against existing companies by name appearing in
     * the headline (e.g. "OpenAI ships..." → OpenAI). Falls back to null
     * (unassigned) rather than guessing wrong.
     */
    private function guessCompany(string $headline): ?int
    {
        static $companies = null;
        $companies ??= Company::pluck('id', 'name');

        foreach ($companies as $name => $id) {
            if (Str::contains($headline, $name, ignoreCase: true)) {
                return $id;
            }
        }

        return null;
    }
}