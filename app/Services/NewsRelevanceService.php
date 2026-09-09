<?php

namespace App\Services;

use App\Models\NewsItem;
use Illuminate\Support\Str;

class NewsRelevanceService
{
    private const STRONG_AI_PHRASES = [
        'ai', 'artificial intelligence', 'generative ai', 'gen ai', 'machine learning', 'llm',
        'large language model', 'language model', 'foundation model', 'reasoning model',
        'multimodal model', 'vision model', 'video model', 'image model', 'ai model', 'ai models', 'ai agent', 'ai agents',
        'agentic ai', 'ai assistant', 'ai assistants', 'ai chatbot', 'ai chatbots',
        'ai coding', 'ai search', 'ai video', 'ai image', 'ai safety', 'ai regulation', 'ai policy', 'ai research',
        'ai benchmark', 'ai inference', 'ai training', 'ai-generated', 'ai generated',
        'neural network', 'deep learning', 'computer vision', 'natural language processing',
    ];

    private const AI_PRODUCTS_AND_MODELS = [
        'chatgpt', 'openai codex', 'gpt-5', 'gpt-4', 'claude', 'gemini', 'deepmind',
        'llama', 'mistral', 'deepseek', 'qwen', 'grok', 'gemma', 'copilot', 'sora',
        'veo', 'midjourney', 'stable diffusion', 'hugging face', 'perplexity ai',
        'notebooklm', 'cursor ai', 'github copilot', 'firefly ai',
    ];

    private const AI_NATIVE_ORGANIZATIONS = [
        'openai', 'anthropic', 'google deepmind', 'deepmind', 'mistral ai', 'xai',
        'hugging face', 'cohere', 'stability ai', 'perplexity ai', 'midjourney',
    ];

    private const SUPPORTING_TERMS = [
        'transformer', 'tokens', 'token', 'context window', 'prompt', 'prompting',
        'inference', 'fine-tuning', 'fine tuning', 'training data', 'benchmark', 'evals',
        'gpu', 'tpu', 'npu', 'accelerator', 'robotics', 'humanoid', 'autonomous agent',
        'retrieval augmented generation', 'rag', 'vector database', 'embedding',
        'model weights', 'open weights', 'open-source model', 'open source model',
    ];

    private const GENERAL_TECH_COMPANIES = [
        'google', 'microsoft', 'meta', 'amazon', 'apple', 'nvidia', 'adobe', 'salesforce',
        'oracle', 'ibm', 'intel', 'amd', 'tesla',
    ];

    private const NEGATIVE_TOPICS = [
        'cargo plane', 'plane crash', 'aircraft crash', 'airport closure', 'airport delay',
        'flight delay', 'airline strike', 'aviation accident', 'sports score', 'football match',
        'baseball game', 'basketball game', 'movie review', 'box office', 'celebrity gossip',
        'phone deal', 'iphone deal', 'discount code', 'shopping deal', 'hands-on phone review',
    ];

    private const NON_AI_PHONE_SIGNALS = [
        'handoff', 'battery life', 'camera sensor', 'screen size', 'phone price',
        'phone is getting more expensive', 'iphone case', 'iphone color', 'carrier plan',
    ];

    public function evaluate(NewsItem $item): array
    {
        $headline = $this->normalize($item->headline);
        // Relevance is based on source/editorial input only. Do not score
        // ai_summary/ai_why_it_matters because those fields are generated after
        // ingestion and could accidentally introduce AI wording into unrelated news.
        $summary = $this->normalize(implode(' ', array_filter([
            $item->summary,
            $item->why_it_matters,
        ])));
        $combined = trim($headline . ' ' . $summary);

        $score = 0;
        $reasons = [];

        $headlineStrong = $this->matches($headline, self::STRONG_AI_PHRASES);
        $summaryStrong = $this->matches($summary, self::STRONG_AI_PHRASES);
        $headlineProducts = $this->matches($headline, self::AI_PRODUCTS_AND_MODELS);
        $summaryProducts = $this->matches($summary, self::AI_PRODUCTS_AND_MODELS);
        $headlineNative = $this->matches($headline, self::AI_NATIVE_ORGANIZATIONS);
        $summaryNative = $this->matches($summary, self::AI_NATIVE_ORGANIZATIONS);
        $supporting = $this->matches($combined, self::SUPPORTING_TERMS);
        $generalCompanies = $this->matches($combined, self::GENERAL_TECH_COMPANIES);

        if ($headlineStrong !== []) {
            $score += min(65, 45 + (count($headlineStrong) - 1) * 8);
            $reasons[] = 'Strong AI language in headline: ' . implode(', ', array_slice($headlineStrong, 0, 3));
        }

        if ($summaryStrong !== []) {
            $score += min(35, 22 + (count($summaryStrong) - 1) * 5);
            $reasons[] = 'Strong AI context in summary';
        }

        if ($headlineProducts !== []) {
            $score += min(58, 50 + (count($headlineProducts) - 1) * 4);
            $reasons[] = 'Known AI product/model in headline: ' . implode(', ', array_slice($headlineProducts, 0, 3));
        } elseif ($summaryProducts !== []) {
            $score += min(28, 18 + (count($summaryProducts) - 1) * 4);
            $reasons[] = 'Known AI product/model in summary';
        }

        if ($headlineNative !== []) {
            $score += 28;
            $reasons[] = 'AI-native organization in headline: ' . $headlineNative[0];
        } elseif ($summaryNative !== []) {
            $score += 14;
            $reasons[] = 'AI-native organization in summary';
        }

        if ($supporting !== []) {
            $score += min(25, count($supporting) * 7);
            $reasons[] = 'Supporting AI technical signals: ' . implode(', ', array_slice($supporting, 0, 3));
        }

        $sourceBonus = $this->sourceBonus($item);
        if ($sourceBonus > 0) {
            $score += $sourceBonus;
            $reasons[] = $sourceBonus >= 35 ? 'AI-native first-party source' : 'AI-focused source/feed';
        }

        // A general technology company is never enough by itself. It only adds a
        // small corroborating signal when the story already contains real AI context.
        if ($generalCompanies !== [] && ($headlineStrong !== [] || $summaryStrong !== [] || $supporting !== [] || $headlineProducts !== [] || $summaryProducts !== [])) {
            $score += min(10, count($generalCompanies) * 3);
        }

        $explicitAiEvidence = $headlineStrong !== [] || $summaryStrong !== [] || $headlineProducts !== [] || $summaryProducts !== [];
        $negativeHits = $this->matches($combined, self::NEGATIVE_TOPICS);

        if ($negativeHits !== []) {
            $penalty = $explicitAiEvidence ? 10 : 55;
            $score -= $penalty;
            $reasons[] = 'Non-AI topic risk: ' . implode(', ', array_slice($negativeHits, 0, 2));
        }

        $phoneHits = $this->matches($combined, self::NON_AI_PHONE_SIGNALS);
        if ($phoneHits !== [] && ! $explicitAiEvidence) {
            $score -= 35;
            $reasons[] = 'Consumer-device story lacks explicit AI context';
        }

        // Generic names such as Apple/Amazon/Microsoft/Meta must not convert an
        // unrelated story into AI news merely because a company was linked.
        if (! $explicitAiEvidence && $supporting === [] && $sourceBonus < 35) {
            $score = min($score, 35);
        }

        $score = max(0, min(100, $score));
        $reviewThreshold = (int) config('news_relevance.review_threshold', 45);
        $acceptThreshold = (int) config('news_relevance.accept_threshold', 60);

        $status = $score >= $acceptThreshold
            ? 'accepted'
            : ($score >= $reviewThreshold ? 'review' : 'rejected');

        $override = $item->ai_relevance_override ?: 'auto';
        if ($override === 'include') {
            $status = 'accepted';
            $reasons[] = 'Editorial override: force include';
        } elseif ($override === 'exclude') {
            $status = 'rejected';
            $reasons[] = 'Editorial override: force exclude';
        }

        if ($reasons === []) {
            $reasons[] = 'No strong AI relevance signals detected';
        }

        return [
            'score' => $score,
            'status' => $status,
            'reasons' => array_values(array_unique($reasons)),
            'version' => (string) config('news_relevance.evaluator_version', 'local-v2'),
        ];
    }

    public function apply(NewsItem $item, bool $save = true): array
    {
        $result = $this->evaluate($item);

        $item->forceFill([
            'ai_relevance_score' => $result['score'],
            'ai_relevance_status' => $result['status'],
            'ai_relevance_reasons' => $result['reasons'],
            'ai_relevance_version' => $result['version'],
            'ai_relevance_checked_at' => now(),
        ]);

        if ($save) {
            $item->saveQuietly();
        }

        return $result;
    }

    public function isAccepted(NewsItem $item): bool
    {
        if (($item->ai_relevance_override ?: 'auto') === 'include') {
            return true;
        }

        if (($item->ai_relevance_override ?: 'auto') === 'exclude') {
            return false;
        }

        return $item->ai_relevance_status === 'accepted'
            && (int) $item->ai_relevance_score >= (int) config('news_relevance.accept_threshold', 60);
    }

    private function sourceBonus(NewsItem $item): int
    {
        $sourceName = $this->normalize((string) ($item->newsSource?->name ?: $item->source));
        $sourceUrl = $this->normalize((string) ($item->newsSource?->url ?: $item->source_url));
        $sourceText = trim($sourceName . ' ' . $sourceUrl);

        if ($this->matches($sourceText, self::AI_NATIVE_ORGANIZATIONS) !== []) {
            return 38;
        }

        foreach ((array) config('news_relevance.ai_focused_source_hints', []) as $hint) {
            $hint = trim(Str::lower((string) $hint));
            if ($hint !== '' && str_contains(' ' . $sourceText . ' ', $hint)) {
                return 15;
            }
        }

        if (str_contains($sourceUrl, 'artificial-intelligence') || preg_match('~/(?:ai|machine-learning)(?:/|\b)~u', $sourceUrl)) {
            return 15;
        }

        return 0;
    }

    private function matches(string $text, array $phrases): array
    {
        if ($text === '') {
            return [];
        }

        $hits = [];
        foreach ($phrases as $phrase) {
            if ($this->containsPhrase($text, $phrase)) {
                $hits[] = $phrase;
            }
        }

        return $hits;
    }

    private function containsPhrase(string $text, string $phrase): bool
    {
        $phrase = $this->normalize($phrase);
        if ($phrase === '') {
            return false;
        }

        return (bool) preg_match('/(?<![\pL\pN])' . preg_quote($phrase, '/') . '(?![\pL\pN])/u', $text);
    }

    private function normalize(?string $value): string
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = Str::lower(Str::squish($value));
        $value = str_replace(['–', '—', '_'], ['-', '-', ' '], $value);

        return $value;
    }
}
