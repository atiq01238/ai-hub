<?php

namespace App\Services\Tools;

use App\Models\Platform;
use App\Models\Tool;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PlatformNormalizer
{
    /**
     * Aliases are deliberately conservative. Generic labels stay generic instead of
     * being expanded into operating systems that were never explicitly verified.
     */
    private const ALIASES = [
        'web' => 'Web',
        'web app' => 'Web',
        'webapp' => 'Web',
        // In the legacy catalog, Browser appears alongside Web for extension-based tools.
        // Treat it as an extension rather than collapsing it into Web and losing that fact.
        'browser' => 'Browser Extension',
        'windows' => 'Windows',
        'windows app' => 'Windows',
        'macos' => 'macOS',
        'mac os' => 'macOS',
        'os x' => 'macOS',
        'linux' => 'Linux',
        'desktop' => 'Desktop',
        'desktop app' => 'Desktop',
        'ios' => 'iOS',
        'iphone' => 'iOS',
        'ipad' => 'iOS',
        'android' => 'Android',
        'mobile' => 'Mobile App',
        'mobile app' => 'Mobile App',
        'browser extension' => 'Browser Extension',
        'browser extensions' => 'Browser Extension',
        'extension' => 'Browser Extension',
        'chrome' => 'Chrome Extension',
        'chrome extension' => 'Chrome Extension',
        'firefox' => 'Firefox Extension',
        'firefox extension' => 'Firefox Extension',
        'vs code' => 'VS Code',
        'vscode' => 'VS Code',
        'visual studio code' => 'VS Code',
        'jetbrains' => 'JetBrains',
        'jetbrains ide' => 'JetBrains',
        'cli' => 'CLI',
        'command line' => 'CLI',
        'command-line' => 'CLI',
        'api' => 'API',
        'api access' => 'API',
        'self hosted' => 'Self Hosted',
        'self-hosted' => 'Self Hosted',
        'selfhosted' => 'Self Hosted',
        'cloud' => 'Cloud',
        'cloud hosted' => 'Cloud',
        'ide' => 'IDE',
        'aws console' => 'AWS Console',
        'on premise' => 'On-Premises',
        'on-premise' => 'On-Premises',
        'on premises' => 'On-Premises',
        'on-premises' => 'On-Premises',
        'ipados' => 'iPadOS',
        'ipad os' => 'iPadOS',
        'adobe apps' => 'Adobe Apps',
        'discord' => 'Discord',
        'embedded' => 'Embedded',
        'git' => 'Git',
        'github' => 'GitHub',
        'gitlab' => 'GitLab',
        'local' => 'Local',
        'local app' => 'Local',
        'robotics' => 'Robotics',
        'robot' => 'Robotics',
        'vehicle' => 'Vehicle',
    ];

    public function normalize(array|string|null $values): array
    {
        if (is_string($values)) {
            $values = preg_split('/[|;,]+/', $values) ?: [];
        }

        $canonical = [];
        $unknown = [];

        foreach ((array) $values as $value) {
            $raw = trim((string) $value);
            if ($raw === '') continue;

            $key = $this->key($raw);
            $name = self::ALIASES[$key] ?? null;

            if ($name === null) {
                $unknown[] = $raw;
                continue;
            }

            $canonical[] = $name;
        }

        return [
            'canonical' => array_values(array_unique($canonical)),
            'unknown' => array_values(array_unique($unknown)),
        ];
    }

    public function idsForNames(array $names): array
    {
        if ($names === []) return [];

        return Platform::query()
            ->active()
            ->whereIn('name', array_values(array_unique($names)))
            ->orderBy('sort_order')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function missingCanonicalNames(array $names): array
    {
        $names = array_values(array_unique(array_filter(array_map('strval', $names))));
        if ($names === []) return [];

        $existing = Platform::query()
            ->active()
            ->whereIn('name', $names)
            ->pluck('name')
            ->all();

        return array_values(array_diff($names, $existing));
    }

    public function namesForIds(array $ids): array
    {
        if ($ids === []) return [];

        return Platform::query()
            ->active()
            ->whereIn('id', array_values(array_unique(array_map('intval', $ids))))
            ->orderBy('sort_order')
            ->pluck('name')
            ->all();
    }

    public function syncTool(Tool $tool, array|string|null $values, bool $persistLegacyCache = true): array
    {
        $result = $this->normalize($values);
        if ($result['unknown'] !== []) return $result;

        $missingTerms = $this->missingCanonicalNames($result['canonical']);
        if ($missingTerms !== []) {
            $result['unknown'] = $missingTerms;
            return $result;
        }

        $tool->platformTerms()->sync($this->idsForNames($result['canonical']));

        if ($persistLegacyCache) {
            $tool->updateQuietly(['platforms' => $result['canonical']]);
        }

        return $result;
    }

    public function available(): Collection
    {
        return Platform::query()->active()->orderBy('sort_order')->orderBy('name')->get();
    }

    private function key(string $value): string
    {
        $value = Str::of($value)->lower()->replace(['_', '/'], ' ')->squish()->value();
        return trim($value);
    }
}
