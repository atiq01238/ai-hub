<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Comparison extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'comparable_type', 'item_ids', 'views', 'status', 'comparison_version', 'summary', 'primary_intent', 'last_verified_at', 'auto_generated', 'seo_faq',
    ];

    protected $casts = [
        'item_ids' => 'array',
        'views' => 'integer',
        'last_verified_at' => 'datetime',
        'auto_generated' => 'boolean',
        'seo_faq' => 'array',
    ];

    /**
     * Resolve current catalog items in display order.
     *
     * Historical comparison rows can carry stale numeric IDs after catalog
     * imports. We therefore recover only from explicit human-readable evidence
     * already stored on the comparison (title/slug); no fuzzy guessing.
     */
    public function items(): Collection
    {
        $modelClass = $this->comparable_type === 'tool' ? Tool::class : AiModel::class;

        $ids = collect($this->item_ids ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $directRows = $ids->isEmpty()
            ? collect()
            : $modelClass::query()->whereIn('id', $ids)->get()->keyBy('id');

        $resolved = $ids
            ->map(fn ($id) => $directRows->get($id))
            ->filter()
            ->values();

        if ($resolved->count() >= 2) {
            return $resolved;
        }

        $catalog = $modelClass::query()->get();
        $byName = $catalog->keyBy(fn ($item) => mb_strtolower(trim((string) $item->name)));
        $bySlug = $catalog->keyBy(fn ($item) => (string) ($item->slug ?: Str::slug((string) $item->name)));

        $recovered = collect($resolved);

        // Title is the strongest recovery source because admins edit it together
        // with the comparison pair.
        $titleParts = $this->pairParts((string) $this->title, false);
        foreach ($titleParts as $name) {
            $match = $byName->get(mb_strtolower($name)) ?: $bySlug->get(Str::slug($name));
            if ($match) {
                $recovered->push($match);
            }
        }

        if ($recovered->unique('id')->count() < 2) {
            // Stored URL slug is a second exact recovery source.
            foreach ($this->pairParts((string) $this->slug, true) as $slugPart) {
                $match = $bySlug->get($slugPart);
                if ($match) {
                    $recovered->push($match);
                }
            }
        }

        return $recovered->unique('id')->values();
    }

    /**
     * Resolve only catalog entities that are eligible for public detail pages.
     * Admin screens can keep using items() when they need to inspect draft data.
     */
    public function publicItems(): Collection
    {
        return $this->items()
            ->filter(function ($item) {
                if ($this->comparable_type === 'tool') {
                    return $item instanceof Tool && $item->status === 'published';
                }

                return $item instanceof AiModel && in_array($item->status, ['active', 'preview'], true);
            })
            ->values();
    }


    /**
     * Curated, indexable comparison pages are intentionally pair-only.
     *
     * The public builder may compare 2–4 items, but those ad-hoc combinations
     * live on /compare/preview and stay noindex. Persisted three/four-item
     * legacy rows can still be viewed directly, but they are not SEO inventory.
     */
    public function isSeoPair(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        try {
            return $this->publicItems()->count() === 2;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    /**
     * Return the public slug that matches the comparison's current pair.
     *
     * Existing slugs are preserved when they still describe the same two
     * entities. When an admin has replaced one of the compared items but kept
     * the historical slug, use the current title/pair for the public URL while
     * resolveRouteBinding() keeps the old slug working as a redirect alias.
     */
    public function canonicalSlug(): string
    {
        $stored = trim((string) $this->slug);

        try {
            $resolvedItems = $this->relationLoaded('resolved_items')
                ? collect($this->getRelation('resolved_items'))
                : $this->items();

            // Only curated head-to-head pairs receive an entity-derived SEO
            // slug. Legacy 3–4 item persisted rows keep their stored URL and
            // are served as noindex utilities instead of pretending to be a
            // two-item comparison.
            if ($resolvedItems->count() !== 2) {
                return $stored;
            }

            $names = $resolvedItems->pluck('name')->filter()->values();
        } catch (\Throwable $e) {
            report($e);
            return $stored;
        }

        if ($names->count() !== 2) {
            return $stored;
        }

        $forward = Str::slug($names[0] . '-vs-' . $names[1]);
        $reverse = Str::slug($names[1] . '-vs-' . $names[0]);

        // Do not churn URLs when the saved slug still represents the current pair.
        if (in_array($stored, [$forward, $reverse], true)) {
            return $stored;
        }

        // Prefer the editorial title order when it names the same current pair.
        $titleParts = $this->pairParts((string) $this->title, false);
        if ($titleParts->count() === 2) {
            $titleNames = $titleParts->map(fn ($part) => Str::slug($part))->sort()->values();
            $currentNames = $names->map(fn ($name) => Str::slug($name))->sort()->values();

            if ($titleNames->all() === $currentNames->all()) {
                $titleSlug = Str::slug($titleParts[0] . '-vs-' . $titleParts[1]);
                if ($this->publicSlugIsAvailable($titleSlug)) {
                    return $titleSlug;
                }
            }
        }

        return $this->publicSlugIsAvailable($forward) ? ($forward ?: $stored) : $stored;
    }


    /**
     * Avoid redirecting one comparison onto another comparison's stored slug.
     */
    private function publicSlugIsAvailable(?string $slug): bool
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return false;
        }

        return ! static::query()
            ->where('slug', $slug)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists();
    }

    /**
     * Frontend route generation should always point at the canonical current
     * comparison URL. Admin routes pass IDs explicitly and are unaffected.
     */
    public function getRouteKey()
    {
        return $this->canonicalSlug();
    }

    /**
     * Resolve exact stored slugs plus safe aliases derived from the current
     * title/items. This lets historical links continue to bind so the frontend
     * controller can permanently redirect them to canonicalSlug().
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?: $this->getRouteKeyName();
        $exact = $this->where($field, $value)->first();

        if ($exact || $field !== 'slug') {
            return $exact;
        }

        $needle = (string) $value;

        return static::query()
            ->where('status', 'published')
            ->get()
            ->first(function (Comparison $comparison) use ($needle) {
                $aliases = collect();

                $titleParts = $comparison->pairParts((string) $comparison->title, false);
                if ($titleParts->count() === 2) {
                    $aliases->push(Str::slug($titleParts[0] . '-vs-' . $titleParts[1]));
                    $aliases->push(Str::slug($titleParts[1] . '-vs-' . $titleParts[0]));
                }

                try {
                    $names = $comparison->items()->pluck('name')->filter()->values();
                    if ($names->count() === 2) {
                        $aliases->push(Str::slug($names[0] . '-vs-' . $names[1]));
                        $aliases->push(Str::slug($names[1] . '-vs-' . $names[0]));
                    }
                } catch (\Throwable $e) {
                    report($e);
                }

                return $aliases->filter()->unique()->contains($needle);
            });
    }

    /**
     * Stable unordered identity for a two-item comparison.
     *
     * Custom 2–4 item previews never call this model method because they are
     * intentionally noindex utilities. Persisted pair pages use it to prevent
     * duplicate X-vs-Y / Y-vs-X SEO inventory.
     */
    public function pairKey(): ?string
    {
        try {
            $items = $this->relationLoaded('resolved_items')
                ? collect($this->getRelation('resolved_items'))
                : $this->publicItems();
        } catch (\Throwable $e) {
            report($e);
            return null;
        }

        return $this->pairKeyFromItems($items);
    }

    /**
     * Conservative SEO quality gate for curated comparison pages.
     *
     * A page must be a public two-item pair, be the canonical persisted record
     * for that unordered pair, and contain at least one real editorial signal.
     * This keeps skeletal/duplicate records out of sitemaps and internal-link
     * discovery without deleting them or making the public utility unavailable.
     */
    public function seoAssessment(): array
    {
        $reasons = [];
        $warnings = [];

        if ($this->status !== 'published') {
            $reasons[] = 'not_published';
        }

        try {
            $items = $this->relationLoaded('resolved_items')
                ? collect($this->getRelation('resolved_items'))
                : $this->publicItems();
        } catch (\Throwable $e) {
            report($e);
            $items = collect();
        }

        if ($items->count() !== 2) {
            $reasons[] = 'not_pair';
        }

        $pairKey = $this->pairKeyFromItems($items);
        $canonicalId = $pairKey ? (self::seoCanonicalPairMap()[$pairKey] ?? null) : null;
        if ($canonicalId && (int) $canonicalId !== (int) $this->getKey()) {
            $reasons[] = 'duplicate_pair';
        }

        $summaryChars = mb_strlen(trim(strip_tags((string) $this->summary)));
        $faqCount = collect($this->seo_faq ?? [])
            ->filter(fn ($faq) => is_array($faq)
                && trim((string) ($faq['question'] ?? '')) !== ''
                && trim((string) ($faq['answer'] ?? '')) !== '')
            ->count();
        $hasIntent = trim((string) $this->primary_intent) !== '';
        $hasVerification = $this->last_verified_at !== null;

        // At least one non-boilerplate editorial/verification signal is needed.
        $contentReady = $summaryChars >= 80 || $faqCount > 0 || $hasIntent || $hasVerification;
        if (! $contentReady) {
            $reasons[] = 'thin_editorial_context';
        }

        if (! $hasVerification) {
            $warnings[] = 'verification_date_missing';
        } elseif ($this->last_verified_at->lt(now()->subYear())) {
            $warnings[] = 'verification_older_than_12_months';
        }

        if ($summaryChars > 0 && $summaryChars < 80) {
            $warnings[] = 'short_summary';
        }

        return [
            'indexable' => $reasons === [],
            'pair_key' => $pairKey,
            'canonical_id' => $canonicalId,
            'reasons' => $reasons,
            'warnings' => $warnings,
            'signals' => [
                'summary_chars' => $summaryChars,
                'faq_count' => $faqCount,
                'has_intent' => $hasIntent,
                'has_verification' => $hasVerification,
            ],
        ];
    }

    public function isSeoIndexable(): bool
    {
        return (bool) ($this->seoAssessment()['indexable'] ?? false);
    }

    private function pairKeyFromItems(Collection $items): ?string
    {
        if ($items->count() !== 2 || ! in_array($this->comparable_type, ['tool', 'model'], true)) {
            return null;
        }

        $ids = $items->pluck('id')
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        if ($ids->count() !== 2) {
            return null;
        }

        return $this->comparable_type.':'.$ids[0].':'.$ids[1];
    }

    /** @return array<string,int> */
    private static function seoCanonicalPairMap(): array
    {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        $groups = [];
        foreach (static::query()->where('status', 'published')->get() as $comparison) {
            try {
                $items = $comparison->publicItems();
            } catch (\Throwable $e) {
                report($e);
                continue;
            }

            $key = $comparison->pairKeyFromItems($items);
            if (! $key) {
                continue;
            }

            $names = $items->pluck('name')->filter()->values();
            $forward = $names->count() === 2 ? Str::slug($names[0].'-vs-'.$names[1]) : '';
            $reverse = $names->count() === 2 ? Str::slug($names[1].'-vs-'.$names[0]) : '';
            $faqCount = collect($comparison->seo_faq ?? [])
                ->filter(fn ($faq) => is_array($faq)
                    && trim((string) ($faq['question'] ?? '')) !== ''
                    && trim((string) ($faq['answer'] ?? '')) !== '')
                ->count();

            $groups[$key][] = [
                'id' => (int) $comparison->getKey(),
                'stored_slug_matches_pair' => in_array((string) $comparison->slug, [$forward, $reverse], true) ? 1 : 0,
                'manual' => $comparison->auto_generated ? 0 : 1,
                'verified_at' => $comparison->last_verified_at?->getTimestamp() ?? 0,
                'content' => mb_strlen(trim(strip_tags((string) $comparison->summary)))
                    + ($faqCount * 80)
                    + (trim((string) $comparison->primary_intent) !== '' ? 80 : 0),
                'views' => (int) $comparison->views,
            ];
        }

        $cache = [];
        foreach ($groups as $key => $candidates) {
            usort($candidates, function (array $a, array $b): int {
                foreach (['stored_slug_matches_pair', 'manual', 'verified_at', 'content', 'views'] as $field) {
                    if ($a[$field] !== $b[$field]) {
                        return $b[$field] <=> $a[$field];
                    }
                }

                // When every quality signal ties, keep the older stable record.
                return $a['id'] <=> $b['id'];
            });

            $cache[$key] = (int) $candidates[0]['id'];
        }

        return $cache;
    }

    private function pairParts(string $value, bool $alreadySlugged): Collection
    {
        if ($value === '') {
            return collect();
        }

        if ($alreadySlugged) {
            $parts = explode('-vs-', trim($value), 2);
            return collect($parts)
                ->map(fn ($part) => trim($part))
                ->filter()
                ->values();
        }

        return collect(preg_split('/\s+vs\.?\s+/i', trim($value), 2) ?: [])
            ->map(fn ($part) => trim($part))
            ->filter()
            ->values();
    }
}
