<?php

namespace App\Services\Discovery;

use App\Models\AiDiscovery;
use App\Models\AiModel;
use App\Models\AppNotification;
use App\Models\DiscoverySource;
use App\Models\NewsItem;
use App\Models\Tool;
use Illuminate\Support\Str;

class DiscoveryClassifier
{
    private const RELEASE_SIGNALS = [
        'introducing', 'introduces', 'we introduce', 'announcing', 'announces', 'we announce',
        'launching', 'launched', 'launches', 'launch ', 'released', 'releases', 'releasing',
        'now available', 'available today', 'unveils', 'unveiled', 'debut', 'debuts',
        'rolls out', 'rolled out', 'ships ', 'shipping ', 'general availability', 'public preview',
        'new model', 'new ai model', 'new ai tool', 'new product', 'meet ',
    ];

    private const MODEL_SIGNALS = [
        ' model', 'gpt-', 'gpt ', 'claude', 'gemini', 'llama', 'mistral', 'grok', 'qwen',
        'deepseek', 'gemma', 'phi-', 'sonnet', 'opus', 'haiku', 'command r', 'foundation model',
        'language model', 'reasoning model', 'multimodal model', 'vision model', 'audio model',
    ];

    private const TOOL_SIGNALS = [
        ' ai tool', 'assistant', 'agent', 'copilot', 'studio', 'platform', 'workspace',
        'app ', ' application', 'product', 'builder', 'generator', 'search engine', 'browser',
        'coding tool', 'research tool', 'automation tool', 'ai service', 'developer tool',
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
            [
                'enabled' => true,
                'trusted' => (bool) $item->newsSource?->company_id,
                'detect_tools' => true,
                'detect_models' => true,
                'minimum_confidence' => $item->newsSource?->company_id ? 50 : 60,
            ]
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

        /*
         * Candidate-first classification is important. The old implementation
         * searched the entire headline for any known model/tool before extracting
         * the newly launched name. A headline such as “Launches Nova, an alternative
         * to ChatGPT” could therefore be misclassified as a ChatGPT update.
         */
        if ($modelHits !== [] && $source->detect_models) {
            $candidateName = $this->extractCandidateName($headline, true);
            $existingModel = $candidateName !== ''
                ? $this->matchModelCandidate($candidateName, $company?->id)
                : null;

            if (! $existingModel && $this->isWeakCandidate($candidateName)) {
                $existingModel = $this->findMentionedModel($headline, $summary, $company?->id);
            }

            if ($existingModel) {
                $type = 'model_update';
                $matchedModelId = $existingModel->id;
                $candidateName = $existingModel->name . ($existingModel->version ? ' ' . $existingModel->version : '');
                $score += 30;
                $signals['existing_model'] = $existingModel->name;
            } elseif (! $this->isWeakCandidate($candidateName)) {
                $type = 'model';
                $score += min(25, 8 + count($modelHits) * 5);
                $signals['model'] = $modelHits;
            }
        }

        if (! $type && $toolHits !== [] && $source->detect_tools) {
            $candidateName = $this->extractCandidateName($headline, false);
            $existingTool = $candidateName !== ''
                ? $this->matchToolCandidate($candidateName, $company?->id)
                : null;

            if (! $existingTool && $this->isWeakCandidate($candidateName)) {
                $existingTool = $this->findMentionedTool($headline, $summary, $company?->id);
            }

            if ($existingTool) {
                $type = 'tool_update';
                $matchedToolId = $existingTool->id;
                $candidateName = $existingTool->name;
                $score += 30;
                $signals['existing_tool'] = $existingTool->name;
            } elseif (! $this->isWeakCandidate($candidateName)) {
                $type = 'tool';
                $score += min(22, 7 + count($toolHits) * 4);
                $signals['tool'] = $toolHits;
            }
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

        // Do not create several pending cards for the same launch just because
        // multiple RSS publications covered it.
        if ($this->pendingDuplicateExists($type, $candidateName)) {
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

        // New tools/models are always review-worthy once they passed the source's
        // own minimum confidence. Updates remain quieter unless confidence is high.
        if (in_array($type, ['model', 'tool'], true) || $score >= 85) {
            $isModel = str_starts_with($type, 'model');
            $title = $type === 'model'
                ? 'New AI model discovered'
                : ($type === 'tool' ? 'New AI tool discovered' : 'AI product update detected');

            AppNotification::broadcast(
                $isModel ? 'brain-circuit' : 'wrench',
                $score >= 85 ? 'info' : 'warning',
                $title,
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
        return collect($signals)
            ->filter(fn ($signal) => str_contains($text, Str::lower($signal)))
            ->values()
            ->all();
    }

    private function matchModelCandidate(string $candidate, ?int $companyId): ?AiModel
    {
        $needle = $this->normalizeCandidate($candidate);
        if ($needle === '') {
            return null;
        }

        return AiModel::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get(['id', 'name', 'version', 'company_id'])
            ->first(function ($model) use ($needle) {
                $name = $this->normalizeCandidate((string) $model->name);
                $full = $this->normalizeCandidate(trim($model->name . ' ' . ($model->version ?? '')));
                return $needle === $name || $needle === $full;
            });
    }

    private function matchToolCandidate(string $candidate, ?int $companyId): ?Tool
    {
        $needle = $this->normalizeCandidate($candidate);
        if ($needle === '') {
            return null;
        }

        return Tool::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get(['id', 'name', 'company_id'])
            ->first(fn ($tool) => $needle === $this->normalizeCandidate((string) $tool->name));
    }

    private function findMentionedModel(string $headline, string $summary, ?int $companyId): ?AiModel
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

    private function findMentionedTool(string $headline, string $summary, ?int $companyId): ?Tool
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
        $headline = Str::squish($headline);

        $patterns = [
            '/(?:introducing|introduces?|announcing|announces?|launching|launched|launches?|releasing|released|releases?|unveils?|debuts?|rolls out|ships?|meet)\s+(?:the\s+)?[“"\']?([^:|–—.!?]{2,90})/iu',
            '/^[^:]{0,55}:\s*[“"\']?([^|–—.!?]{2,90})/u',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $headline, $match)) {
                $name = $this->cleanCandidate($match[1]);
                if (! $this->isWeakCandidate($name)) {
                    return $name;
                }
            }
        }

        if ($model && preg_match('/\b((?:GPT|Claude|Gemini|Llama|Mistral|Grok|Qwen|DeepSeek|Gemma|Phi)[-\s]?[A-Za-z0-9.\- ]{0,35})\b/u', $headline, $match)) {
            $name = $this->cleanCandidate($match[1]);
            if (! $this->isWeakCandidate($name)) {
                return $name;
            }
        }

        return '';
    }

    private function cleanCandidate(string $value): string
    {
        $value = Str::squish($value);
        $value = preg_replace('/[,;]\s*(?:an?|the|its|with|for|that|which|designed|built|aimed)\b.*$/iu', '', $value) ?? $value;
        $value = preg_replace('/\s+(?:is|with|for|to|now|brings|adds|offers|that|which)\b.*$/iu', '', $value) ?? $value;
        $value = preg_replace('/^(?:a|an|the)\s+/iu', '', $value) ?? $value;
        $value = trim($value, " \t\n\r\0\x0B\"'“”‘’:-–—");
        return Str::limit($value, 100, '');
    }

    private function isWeakCandidate(?string $value): bool
    {
        $value = $this->normalizeCandidate((string) $value);

        if ($value === '' || mb_strlen($value) < 2) {
            return true;
        }

        return in_array($value, [
            'new model', 'new ai model', 'model', 'ai model', 'new tool', 'new ai tool',
            'tool', 'ai tool', 'new product', 'product', 'new platform', 'platform',
            'new assistant', 'assistant', 'new agent', 'agent',
        ], true);
    }

    private function pendingDuplicateExists(string $type, string $candidateName): bool
    {
        $needle = $this->normalizeCandidate($candidateName);
        if ($needle === '') {
            return false;
        }

        return AiDiscovery::query()
            ->where('status', 'pending')
            ->where('entity_type', $type)
            ->where('created_at', '>=', now()->subDays(30))
            ->latest('id')
            ->limit(250)
            ->get(['candidate_name'])
            ->contains(fn ($candidate) => $this->normalizeCandidate((string) $candidate->candidate_name) === $needle);
    }

    private function normalizeCandidate(string $value): string
    {
        $value = Str::lower(Str::ascii(Str::squish($value)));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;
        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
