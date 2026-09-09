<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\Company;
use App\Models\NewsItem;
use App\Models\Tool;
use Illuminate\Support\Str;

class NewsEntityLinker
{
    private const AMBIGUOUS_TOOL_NAMES = [
        'runway', 'make', 'cursor', 'gamma', 'character', 'bolt', 'jasper',
        'notion', 'glean', 'harvey', 'suno', 'replit', 'descript', 'firefly',
    ];

    private const AMBIGUOUS_TOOL_HINTS = [
        'runway' => ['runway ai', 'runwayml', 'gen-1', 'gen-2', 'gen-3', 'gen-4', 'gen 4', 'act-one', 'aleph', 'text-to-video', 'video generation'],
        'make' => ['make.com', 'make automation', 'workflow automation', 'automation platform'],
        'cursor' => ['cursor ai', 'cursor editor', 'ai code editor', 'coding assistant'],
        'gamma' => ['gamma app', 'gamma ai', 'ai presentation', 'presentation maker'],
        'character' => ['character.ai', 'character ai', 'ai character'],
        'bolt' => ['bolt.new', 'stackblitz', 'ai app builder'],
        'jasper' => ['jasper ai', 'ai marketing', 'marketing copilot'],
        'notion' => ['notion ai', 'notion mail', 'notion agent'],
        'glean' => ['glean ai', 'enterprise search', 'workplace search'],
        'harvey' => ['harvey ai', 'legal ai', 'legal assistant'],
        'suno' => ['suno ai', 'ai music', 'music generation'],
        'replit' => ['replit agent', 'replit ai', 'replit assistant'],
        'descript' => ['descript ai', 'ai video editor', 'ai audio editor'],
        'firefly' => ['adobe firefly', 'firefly ai', 'generative fill'],
    ];

    private const AMBIGUOUS_MODEL_HINTS = [
        'claude' => ['anthropic', 'claude ai', 'claude model', 'claude code'],
        'gemini' => ['google gemini', 'gemini ai', 'gemini model', 'deepmind'],
        'grok' => ['xai', 'grok ai', 'grok model'],
        'llama' => ['meta llama', 'llama model', 'llama ai'],
        'sora' => ['openai sora', 'sora video', 'sora model'],
    ];

    public function link(NewsItem $news): array
    {
        // Relevance is the first gate. Entity names must never turn an unrelated
        // story into AI news (e.g. airport "runway" -> Runway the AI company).
        if (! $news->isAiRelevant()) {
            $news->relatedToolTerms()->sync([]);
            $news->relatedModelTerms()->sync([]);
            $news->forceFill(['related_tools' => []])->saveQuietly();

            return ['tools' => 0, 'models' => 0, 'companies' => 0];
        }

        $text = Str::lower(strip_tags(implode(' ', array_filter([
            $news->headline,
            $news->summary,
            $news->ai_summary,
            $news->why_it_matters,
        ]))));

        $match = fn (string $name): bool => $this->matchesEntity($text, $name);

        $tools = Tool::query()
            ->select('id', 'name', 'company_id')
            ->get()
            ->filter(function (Tool $tool) use ($text, $match, $news) {
                if (! $match($tool->name)) {
                    return false;
                }

                if (! $this->isAmbiguous($tool->name)) {
                    return true;
                }

                if ($news->company_id && $tool->company_id && (int) $news->company_id === (int) $tool->company_id) {
                    return true;
                }

                $key = Str::lower(trim($tool->name));
                return $this->hasDisambiguationHintNearMention(
                    $text,
                    $tool->name,
                    self::AMBIGUOUS_TOOL_HINTS[$key] ?? []
                );
            })
            ->pluck('id');

        $models = AiModel::query()
            ->select('id', 'name', 'company_id')
            ->get()
            ->filter(function (AiModel $model) use ($text, $match, $news) {
                if (! $match($model->name)) {
                    return false;
                }

                $key = Str::lower(trim($model->name));
                if (! array_key_exists($key, self::AMBIGUOUS_MODEL_HINTS)) {
                    return true;
                }

                if ($news->company_id && $model->company_id && (int) $news->company_id === (int) $model->company_id) {
                    return true;
                }

                return $this->hasDisambiguationHintNearMention($text, $model->name, self::AMBIGUOUS_MODEL_HINTS[$key]);
            })
            ->pluck('id');

        $companies = Company::query()
            ->select('id', 'name')
            ->get()
            ->filter(fn (Company $company) => $match($company->name))
            ->pluck('id');

        $news->relatedToolTerms()->sync($tools);
        $news->relatedModelTerms()->sync($models);

        if (! $news->company_id && $companies->count() === 1) {
            $news->forceFill(['company_id' => $companies->first()])->saveQuietly();
        }

        $news->forceFill([
            'related_tools' => Tool::whereIn('id', $tools)->pluck('name')->values()->all(),
        ])->saveQuietly();

        return [
            'tools' => $tools->count(),
            'models' => $models->count(),
            'companies' => $companies->count(),
        ];
    }

    private function matchesEntity(string $text, string $name): bool
    {
        $name = Str::lower(trim($name));

        return mb_strlen($name) >= 3
            && (bool) preg_match('/(?<![\pL\pN])' . preg_quote($name, '/') . '(?![\pL\pN])/u', $text);
    }

    private function isAmbiguous(string $name): bool
    {
        return in_array(Str::lower(trim($name)), self::AMBIGUOUS_TOOL_NAMES, true);
    }

    private function hasDisambiguationHintNearMention(string $text, string $name, array $hints): bool
    {
        if ($hints === []) {
            return false;
        }

        $needle = Str::lower(trim($name));
        $offset = 0;

        while (($position = mb_stripos($text, $needle, $offset)) !== false) {
            $start = max(0, $position - 160);
            $window = mb_substr($text, $start, mb_strlen($needle) + 320);

            foreach ($hints as $hint) {
                if ($this->matchesEntity($window, $hint)) {
                    return true;
                }
            }

            $offset = $position + max(1, mb_strlen($needle));
        }

        return false;
    }
}
