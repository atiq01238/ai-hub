<?php

namespace App\Services\Discovery;

use App\Models\AiDiscovery;
use App\Models\AiModel;
use App\Models\AppNotification;
use App\Models\Company;
use App\Models\DiscoverySource;
use App\Models\NewsItem;
use App\Models\Tool;
use Illuminate\Support\Str;

class DiscoveryClassifier
{
    private const RELEASE_SIGNALS = [
        'introducing', 'we introduce', 'announcing', 'we announce', 'launching', 'launched',
        'launches', 'released', 'releases', 'now available', 'available today', 'unveils',
        'unveiled', 'debut', 'rolls out', 'general availability', 'public preview', 'new model',
        'new ai model', 'new ai tool', 'new product', 'meet ',
    ];

    private const MODEL_SIGNALS = [
        ' model', 'gpt-', 'gpt ', 'claude', 'gemini', 'llama', 'mistral', 'grok', 'qwen',
        'deepseek', 'gemma', 'phi-', 'sonnet', 'opus', 'haiku', 'command r', 'foundation model',
        'language model', 'reasoning model', 'multimodal model',
    ];

    private const TOOL_SIGNALS = [
        ' ai tool', 'assistant', 'agent', 'copilot', 'studio', 'platform', 'workspace',
        'app ', ' application', 'product', 'builder', 'generator', 'search engine', 'browser',
    ];

    public function analyze(NewsItem $item, bool $force = false): ?AiDiscovery
    {
        if (! $item->news_source_id) {
            return null;
        }

        if (! $force && $item->discovery_analyzed_at) {
            return AiDiscovery::where('news_item_id', $item->id)->first();
        }

        $existingDiscovery = AiDiscovery::where('news_item_id', $item->id)->first();
        if ($existingDiscovery) {
            $item->forceFill(['discovery_analyzed_at' => now()])->saveQuietly();
            return $existingDiscovery;
        }

        $source = DiscoverySource::firstOrCreate(
            ['news_source_id' => $item->news_source_id],
            ['enabled' => true, 'trusted' => (bool) $item->newsSource?->company_id]
        );

        if (! $source->enabled) {
            return null;
        }

        $headline = trim((string) $item->headline);
        $summary = trim((string) $item->summary);
        $text = Str::lower($headline . ' ' . $summary);

        $releaseHits = $this->hits($text, self::RELEASE_SIGNALS);
        if ($releaseHits === []) {
            $this->markAnalyzed($item);
            return null;
        }

        $company = $item->company ?: $item->newsSource?->company;
        $existingModel = $this->matchModel($headline, $summary, $company?->id);
        $existingTool = $this->matchTool($headline, $summary, $company?->id);
        $modelHits = $this->hits($text, self::MODEL_SIGNALS);
        $toolHits = $this->hits($text, self::TOOL_SIGNALS);

        $type = null;
        $matchedModelId = null;
        $matchedToolId = null;
        $candidateName = null;
        $score = 30;
        $signals = ['release' => $releaseHits];

        if ($source->trusted) {
            $score += 20;
            $signals['trusted_source'] = true;
        }
        if ($company) {
            $score += 10;
            $signals['company'] = $company->name;
        }

        if ($existingModel && $source->detect_models) {
            $type = 'model_update';
            $matchedModelId = $existingModel->id;
            $candidateName = $existingModel->name . ($existingModel->version ? ' ' . $existingModel->version : '');
            $score += 30;
            $signals['existing_model'] = $existingModel->name;
        } elseif ($existingTool && $source->detect_tools && $modelHits === []) {
            $type = 'tool_update';
            $matchedToolId = $existingTool->id;
            $candidateName = $existingTool->name;
            $score += 30;
            $signals['existing_tool'] = $existingTool->name;
        } elseif ($modelHits !== [] && $source->detect_models) {
            $type = 'model';
            $candidateName = $this->extractCandidateName($headline, true);
            $score += min(25, 8 + count($modelHits) * 5);
            $signals['model'] = $modelHits;
        } elseif ($toolHits !== [] && $source->detect_tools) {
            $type = 'tool';
            $candidateName = $this->extractCandidateName($headline, false);
            $score += min(22, 7 + count($toolHits) * 4);
            $signals['tool'] = $toolHits;
        }

        if (! $type || ! $candidateName) {
            $this->markAnalyzed($item);
            return null;
        }

        if (count($releaseHits) > 1) {
            $score += min(10, (count($releaseHits) - 1) * 3);
        }

        $score = max(0, min(100, $score));
        if ($score < $source->minimum_confidence) {
            $this->markAnalyzed($item);
            return null;
        }

        $discovery = AiDiscovery::create([
            'news_item_id' => $item->id,
            'news_source_id' => $item->news_source_id,
            'company_id' => $company?->id,
            'matched_tool_id' => $matchedToolId,
            'matched_model_id' => $matchedModelId,
            'entity_type' => $type,
            'candidate_name' => Str::limit($candidateName, 255, ''),
            'headline' => Str::limit($headline, 255, ''),
            'summary' => Str::limit($summary, 2000, ''),
            'source_url' => $item->canonical_url ?: $item->source_url,
            'confidence' => $score,
            'status' => 'pending',
            'signals' => $signals,
        ]);

        $this->markAnalyzed($item);

        $source->forceFill([
            'last_discovery_at' => now(),
            'discoveries_count' => $source->discoveries_count + 1,
        ])->save();

        if ($score >= 85) {
            AppNotification::broadcast(
                $type === 'model' || $type === 'model_update' ? 'brain-circuit' : 'wrench',
                'info',
                'AI discovery detected',
                $discovery->candidate_name . ' · ' . str_replace('_', ' ', $type) . " · {$score}% confidence",
                url('/admin/discovery/' . $discovery->id),
                'ai_discovery'
            );
        }

        return $discovery;
    }

    private function markAnalyzed(NewsItem $item): void
    {
        $item->forceFill(['discovery_analyzed_at' => now()])->saveQuietly();
    }

    private function hits(string $text, array $signals): array
    {
        return collect($signals)->filter(fn ($signal) => str_contains($text, Str::lower($signal)))->values()->all();
    }

    private function matchModel(string $headline, string $summary, ?int $companyId): ?AiModel
    {
        $text = Str::lower($headline . ' ' . $summary);
        return AiModel::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderByRaw('CHAR_LENGTH(name) DESC')
            ->get(['id', 'name', 'version', 'company_id'])
            ->first(function ($model) use ($text) {
                $name = Str::lower(trim((string) $model->name));
                $full = Str::lower(trim($model->name . ' ' . ($model->version ?? '')));
                return mb_strlen($name) >= 3 && (str_contains($text, $full) || str_contains($text, $name));
            });
    }

    private function matchTool(string $headline, string $summary, ?int $companyId): ?Tool
    {
        $text = Str::lower($headline . ' ' . $summary);
        return Tool::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderByRaw('CHAR_LENGTH(name) DESC')
            ->get(['id', 'name', 'company_id'])
            ->first(function ($tool) use ($text) {
                $name = Str::lower(trim((string) $tool->name));
                return mb_strlen($name) >= 3 && str_contains($text, $name);
            });
    }

    private function extractCandidateName(string $headline, bool $model): string
    {
        $headline = trim(preg_replace('/\s+/u', ' ', $headline) ?? $headline);

        $patterns = [
            '/(?:introducing|announcing|launching|launched|launches|releasing|released|releases|unveils?|meet)\s+(?:the\s+)?[“"\']?([^:|–—.!?]{2,80})/iu',
            '/^[^:]{0,45}:\s*[“"\']?([^|–—.!?]{2,80})/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $headline, $match)) {
                $name = $this->cleanCandidate($match[1]);
                if ($name !== '') return $name;
            }
        }

        if ($model && preg_match('/\b((?:GPT|Claude|Gemini|Llama|Mistral|Grok|Qwen|DeepSeek|Gemma|Phi)[-\s]?[A-Za-z0-9.\- ]{0,35})\b/u', $headline, $match)) {
            return $this->cleanCandidate($match[1]);
        }

        return Str::limit($this->cleanCandidate($headline), 90, '');
    }

    private function cleanCandidate(string $value): string
    {
        $value = preg_replace('/\s+(?:is|with|for|to|now|brings|adds)\b.*$/iu', '', trim($value)) ?? trim($value);
        $value = trim($value, " \t\n\r\0\x0B\"'“”‘’:-–—");
        return Str::limit($value, 100, '');
    }
}
