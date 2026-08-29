<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Comparison extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'slug', 'comparable_type', 'item_ids', 'views', 'status', 'comparison_version', 'summary', 'primary_intent', 'last_verified_at', 'auto_generated', 'seo_faq',
    ];

    protected $casts = [
        'item_ids' => 'array',
        'views'    => 'integer',
        'last_verified_at' => 'datetime',
        'auto_generated' => 'boolean',
        'seo_faq' => 'array',
    ];

    /**
     * Fetch the actual Tool or AiModel rows this comparison points to,
     * in the order they were selected.
     */
    public function items()
    {
        $modelClass = $this->comparable_type === 'tool' ? Tool::class : AiModel::class;
        $ids = collect($this->item_ids ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $rows = $ids->isEmpty()
            ? collect()
            : $modelClass::query()->whereIn('id', $ids)->get()->keyBy('id');

        $resolved = $ids
            ->map(fn ($id) => $rows->get($id))
            ->filter()
            ->values();

        // Historical comparisons can retain stale numeric IDs after catalog
        // maintenance/imports. If fewer than two records resolve, recover by
        // the human-readable comparison title instead of sending users to 404.
        if ($resolved->count() >= 2) {
            return $resolved;
        }

        $titleNames = collect(preg_split('/\s+vs\.?\s+/i', trim((string) $this->title)) ?: [])
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique()
            ->values();

        if ($titleNames->count() < 2) {
            return $resolved;
        }

        // First try exact catalog names.
        $nameRows = $modelClass::query()
            ->whereIn('name', $titleNames->all())
            ->get()
            ->keyBy(fn ($item) => mb_strtolower(trim((string) $item->name)));

        $recovered = $titleNames
            ->map(fn ($name) => $nameRows->get(mb_strtolower($name)))
            ->filter()
            ->values();

        if ($recovered->count() >= 2) {
            return $recovered;
        }

        // Last-resort normalized-name lookup handles punctuation differences
        // such as dots/spaces without guessing across unrelated products.
        $all = $modelClass::query()->get();
        $bySlug = $all->keyBy(fn ($item) => Str::slug((string) $item->name));
        $normalized = $titleNames
            ->map(fn ($name) => $bySlug->get(Str::slug($name)))
            ->filter()
            ->values();

        return $normalized->count() >= 2 ? $normalized : $resolved;
    }

    /**
     * Preserve old comparison URLs and tolerate title-order aliases.
     * Exact stored slugs always win; aliases only apply to published records.
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
            ->get(['id', 'title', 'slug', 'status'])
            ->first(function (Comparison $comparison) use ($needle) {
                if (Str::slug((string) $comparison->title) === $needle) {
                    return true;
                }

                $parts = collect(preg_split('/\s+vs\.?\s+/i', trim((string) $comparison->title)) ?: [])
                    ->map(fn ($part) => trim($part))
                    ->filter()
                    ->values();

                return $parts->count() === 2
                    && Str::slug($parts[1] . '-vs-' . $parts[0]) === $needle;
            });
    }
}
