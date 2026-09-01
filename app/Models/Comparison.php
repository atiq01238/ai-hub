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
     * Preserve saved comparison URLs after title/order cleanup.
     * Exact stored slugs win. Fallback aliases are derived only from the saved
     * title or the currently resolved pair, so unrelated comparisons cannot bind.
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
