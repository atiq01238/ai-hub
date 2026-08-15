<?php

namespace App\Console\Commands;

use App\Models\NewsItem;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ProcessAiNews extends Command
{
    protected $signature = 'news:process-ai
                            {--id= : Process one NewsItem by ID}
                            {--all : Re-process all articles}
                            {--limit=50 : Maximum articles to process}
                            {--force : Re-process already processed articles}';

    protected $description = 'Process pending AI news locally: classify topic, sentiment, importance, tags, summary and why it matters.';

    private array $topicKeywords = [
        'model' => [
            'model', 'llm', 'large language model', 'reasoning model',
            'foundation model', 'multimodal', 'language model',
        ],
        'generative-ai' => [
            'generative ai', 'genai', 'text generation', 'image generation',
            'video generation', 'ai generated', 'generative model',
        ],
        'agents' => [
            'ai agent', 'ai agents', 'agentic', 'agent', 'autonomous agent',
            'computer use', 'browser agent',
        ],
        'robotics' => [
            'robot', 'robotics', 'humanoid', 'autonomous vehicle',
            'self driving', 'physical ai',
        ],
        'chips-infrastructure' => [
            'gpu', 'npu', 'tpu', 'chip', 'semiconductor', 'datacenter',
            'data center', 'compute', 'infrastructure',
        ],
        'enterprise-ai' => [
            'enterprise ai', 'business ai', 'copilot', 'workplace ai',
            'productivity ai', 'enterprise',
        ],
        'research' => [
            'research', 'paper', 'study', 'benchmark', 'arxiv',
            'scientists', 'researchers',
        ],
        'safety-policy' => [
            'ai safety', 'alignment', 'responsible ai', 'regulation',
            'regulatory', 'policy', 'law', 'copyright', 'privacy',
        ],
        'funding-business' => [
            'funding', 'investment', 'investor', 'valuation', 'acquisition',
            'acquire', 'raises', 'million', 'billion', 'startup',
        ],
        'product' => [
            'launches', 'launched', 'release', 'released', 'announces',
            'announced', 'feature', 'product', 'update',
        ],
    ];

    private array $positiveWords = [
        'breakthrough', 'success', 'growth', 'improves', 'improved',
        'better', 'faster', 'powerful', 'advances', 'advance',
        'launches', 'launched', 'wins', 'successful', 'record',
        'innovation', 'innovative',
    ];

    private array $negativeWords = [
        'lawsuit', 'layoffs', 'layoff', 'breach', 'leak', 'failure',
        'fails', 'failed', 'risk', 'risks', 'warning', 'warns',
        'controversy', 'controversial', 'ban', 'banned', 'delay',
        'delays', 'concern', 'concerns', 'investigation', 'scandal',
    ];

    private array $highImpactWords = [
        'openai', 'anthropic', 'google', 'deepmind', 'microsoft', 'meta',
        'nvidia', 'apple', 'amazon', 'xai', 'mistral', 'government',
        'regulation', 'regulatory', 'lawsuit', 'acquisition', 'funding',
        'billion', 'breakthrough', 'launch', 'released', 'model',
        'safety', 'security',
    ];

    public function handle(): int
    {
        $query = NewsItem::query()
            ->whereNotNull('headline')
            ->whereNotNull('duplicate_checked_at')
            ->where(function ($q) {
                $q->whereNull('duplicate_status')
                    ->orWhere('duplicate_status', '!=', 'duplicate');
            })
            ->orderBy('id');

        if ($id = $this->option('id')) {
            $query->whereKey((int) $id);
        } elseif (! $this->option('all') && ! $this->option('force')) {
            $query->where(function ($q) {
                $q->whereNull('ai_processed_at')
                    ->orWhere('processing_status', 'pending');
            });
        }

        if (! $this->option('all') && ! $this->option('id')) {
            $query->limit(max(1, (int) $this->option('limit')));
        }

        if ($this->option('force')) {
            $query->limit(max(1, (int) $this->option('limit')));
        }

        $items = $query->get();

        if ($items->isEmpty()) {
            $this->info('No news articles require AI processing.');

            return self::SUCCESS;
        }

        $this->info("AI processing started for {$items->count()} article(s).");

        $processed = 0;
        $failed = 0;

        foreach ($items as $item) {
            try {
                $this->processItem($item);
                $processed++;

                $this->line(
                    "  ✓ #{$item->id} "
                    . Str::limit($item->headline, 80)
                );
            } catch (\Throwable $e) {
                $failed++;

                $item->forceFill([
                    'processing_status' => 'failed',
                    'verification_notes' => Str::limit(
                        'AI processing error: ' . $e->getMessage(),
                        1000
                    ),
                ])->save();

                $this->error(
                    "  ✗ #{$item->id}: {$e->getMessage()}"
                );
            }
        }

        $this->newLine();
        $this->info('AI processing finished.');
        $this->line("Processed: {$processed}");
        $this->line("Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processItem(NewsItem $item): void
    {
        $item->forceFill([
            'processing_status' => 'processing',
        ])->save();

        $text = $this->cleanText(
            implode(' ', [
                $item->headline,
                $item->summary,
            ])
        );

        if ($text === '') {
            throw new \RuntimeException('Article has no usable headline or summary.');
        }

        $topicResult = $this->detectTopic($text);
        $sentiment = $this->detectSentiment($text);
        $importance = $this->calculateImportance($text, $item);
        $tags = $this->buildTags($text, $topicResult['topic']);
        $summary = $this->makeSummary($item);
        $whyItMatters = $this->makeWhyItMatters(
            $topicResult['topic'],
            $importance,
            $item
        );

        $confidence = $this->confidence(
            $topicResult['score'],
            count($tags),
            $text
        );

        $item->forceFill([
            'ai_topic' => $topicResult['topic'],
            'ai_tags' => $tags,
            'ai_summary' => $summary,
            'ai_why_it_matters' => $whyItMatters,
            'ai_confidence' => $confidence,
            'ai_processor' => 'local-v1',
            'sentiment' => $sentiment,
            'importance' => $importance,
            'processing_status' => 'processed',
            'ai_processed_at' => now(),
        ])->save();
    }

    private function detectTopic(string $text): array
    {
        $scores = [];

        foreach ($this->topicKeywords as $topic => $keywords) {
            $score = 0;

            foreach ($keywords as $keyword) {
                if ($this->containsPhrase($text, $keyword)) {
                    $score += $this->wordCount($keyword) > 1 ? 2 : 1;
                }
            }

            if ($score > 0) {
                $scores[$topic] = $score;
            }
        }

        if ($scores === []) {
            return [
                'topic' => 'general-ai',
                'score' => 0,
            ];
        }

        arsort($scores);

        return [
            'topic' => array_key_first($scores),
            'score' => reset($scores),
        ];
    }

    private function detectSentiment(string $text): string
    {
        $positive = 0;
        $negative = 0;

        foreach ($this->positiveWords as $word) {
            if ($this->containsPhrase($text, $word)) {
                $positive++;
            }
        }

        foreach ($this->negativeWords as $word) {
            if ($this->containsPhrase($text, $word)) {
                $negative++;
            }
        }

        if ($negative > $positive && $negative >= 2) {
            return 'negative';
        }

        if ($positive > $negative && $positive >= 2) {
            return 'positive';
        }

        return 'neutral';
    }

    private function calculateImportance(string $text, NewsItem $item): int
    {
        $score = 35;

        foreach ($this->highImpactWords as $word) {
            if ($this->containsPhrase($text, $word)) {
                $score += in_array(
                    $word,
                    ['billion', 'breakthrough', 'regulation', 'lawsuit', 'safety'],
                    true
                ) ? 8 : 4;
            }
        }

        if ($item->company_id) {
            $score += 8;
        }

        if (mb_strlen($text) > 500) {
            $score += 4;
        }

        return max(0, min(100, $score));
    }

    private function buildTags(string $text, string $topic): array
    {
        $tags = [$topic];

        $tagMap = [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic',
            'google' => 'Google',
            'deepmind' => 'Google DeepMind',
            'microsoft' => 'Microsoft',
            'meta' => 'Meta',
            'nvidia' => 'NVIDIA',
            'apple' => 'Apple',
            'amazon' => 'Amazon',
            'xai' => 'xAI',
            'mistral' => 'Mistral',
            'llm' => 'LLM',
            'reasoning' => 'Reasoning',
            'multimodal' => 'Multimodal',
            'agent' => 'AI Agents',
            'robot' => 'Robotics',
            'robotics' => 'Robotics',
            'gpu' => 'GPU',
            'chip' => 'AI Chips',
            'funding' => 'Funding',
            'investment' => 'Investment',
            'benchmark' => 'Benchmark',
            'research' => 'Research',
            'safety' => 'AI Safety',
            'security' => 'AI Security',
            'regulation' => 'AI Regulation',
            'copyright' => 'Copyright',
        ];

        foreach ($tagMap as $needle => $tag) {
            if (
                $this->containsPhrase($text, $needle)
                && ! in_array($tag, $tags, true)
            ) {
                $tags[] = $tag;
            }

            if (count($tags) >= 8) {
                break;
            }
        }

        return array_values(array_unique($tags));
    }

    private function makeSummary(NewsItem $item): string
    {
        $summary = $this->cleanText((string) $item->summary);

        if ($summary !== '') {
            return Str::limit($summary, 600, '…');
        }

        return Str::limit(
            $this->cleanText((string) $item->headline),
            600,
            '…'
        );
    }

    private function makeWhyItMatters(
        string $topic,
        int $importance,
        NewsItem $item
    ): string {
        $topicText = match ($topic) {
            'model' => 'This may affect the capabilities and competitive direction of AI models.',
            'generative-ai' => 'This may influence how AI-generated content is created or used.',
            'agents' => 'This may accelerate the move from chat-based AI toward systems that can perform tasks.',
            'robotics' => 'This may affect the development and adoption of physical AI and autonomous systems.',
            'chips-infrastructure' => 'This may influence AI compute availability, cost, and infrastructure competition.',
            'enterprise-ai' => 'This may affect how organizations deploy AI in everyday workflows.',
            'research' => 'This may provide a signal about the direction of current AI research.',
            'safety-policy' => 'This may influence AI governance, safety, privacy, or regulatory decisions.',
            'funding-business' => 'This may signal changing investment, competition, or commercial momentum in AI.',
            'product' => 'This may affect the AI products and features available to users.',
            default => 'This provides a current signal about the development of the AI ecosystem.',
        };

        if ($importance >= 80) {
            return 'High-impact development. ' . $topicText;
        }

        if ($importance >= 60) {
            return 'Notable development. ' . $topicText;
        }

        return $topicText;
    }

    private function confidence(
        int $topicScore,
        int $tagCount,
        string $text
    ): int {
        $score = 50;

        $score += min(30, $topicScore * 6);
        $score += min(12, max(0, $tagCount - 1) * 2);

        if (mb_strlen($text) >= 250) {
            $score += 8;
        }

        return max(0, min(100, $score));
    }

    private function wordCount(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        // Unicode-safe word counting without requiring the mbstring
        // mb_str_word_count() function.
        preg_match_all('/[\\p{L}\\p{N}]+/u', $value, $matches);

        return count($matches[0] ?? []);
    }

    private function containsPhrase(string $text, string $phrase): bool
    {
        return Str::contains(
            ' ' . $text . ' ',
            ' ' . Str::lower(trim($phrase)) . ' ',
            ignoreCase: true
        ) || Str::contains(
            $text,
            Str::lower(trim($phrase)),
            ignoreCase: true
        );
    }

    private function cleanText(string $value): string
    {
        $value = html_entity_decode(
            strip_tags($value),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
