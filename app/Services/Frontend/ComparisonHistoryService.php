<?php

namespace App\Services\Frontend;

use App\Models\AiModel;
use App\Models\Comparison;
use App\Models\Tool;
use App\Models\User;
use App\Models\UserComparison;
use Illuminate\Support\Collection;

class ComparisonHistoryService
{
    public function signature(string $type, array $itemIds): string
    {
        $ids = collect($itemIds)->map(fn ($id) => (int) $id)->filter()->unique()->sort()->values();

        return hash('sha256', $type . '|' . $ids->join(','));
    }

    public function validateItems(string $type, array $itemIds): Collection
    {
        abort_unless(in_array($type, ['tool', 'model'], true), 422);

        $ids = collect($itemIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->take(4)
            ->values();

        abort_unless($ids->count() >= 2, 422);

        $modelClass = $type === 'tool' ? Tool::class : AiModel::class;
        $query = $modelClass::query()->whereIn('id', $ids);

        if ($type === 'tool') {
            $query->where('status', 'published');
        } else {
            $query->whereIn('status', ['active', 'preview']);
        }

        $rows = $query->get()->keyBy('id');
        $items = $ids->map(fn ($id) => $rows->get($id))->filter()->values();

        abort_unless($items->count() === $ids->count(), 404);

        return $items;
    }

    public function fromPublished(User $user, Comparison $comparison, bool $saved = false): UserComparison
    {
        $items = $this->validateItems($comparison->comparable_type, (array) $comparison->item_ids);

        return $this->upsert(
            $user,
            $comparison->comparable_type,
            $items->pluck('id')->all(),
            $comparison->title,
            $comparison->id,
            $saved
        );
    }

    public function fromPreview(User $user, string $type, array $itemIds, ?string $title = null, bool $saved = false): UserComparison
    {
        $items = $this->validateItems($type, $itemIds);
        $resolvedTitle = trim((string) $title) ?: $items->pluck('name')->join(' vs ');

        return $this->upsert(
            $user,
            $type,
            $items->pluck('id')->all(),
            $resolvedTitle,
            null,
            $saved
        );
    }

    public function toggle(
        User $user,
        string $type,
        array $itemIds,
        ?string $title = null,
        ?Comparison $comparison = null
    ): UserComparison {
        $record = $comparison
            ? $this->fromPublished($user, $comparison, false)
            : $this->fromPreview($user, $type, $itemIds, $title, false);

        $record->forceFill([
            'is_saved' => ! $record->is_saved,
            'last_viewed_at' => now(),
        ])->save();

        return $record;
    }

    public function savePayload(User $user, array $payload): ?UserComparison
    {
        try {
            if (! empty($payload['comparison_id'])) {
                $comparison = Comparison::query()
                    ->where('status', 'published')
                    ->findOrFail((int) $payload['comparison_id']);

                $record = $this->fromPublished($user, $comparison, true);
            } else {
                $record = $this->fromPreview(
                    $user,
                    (string) ($payload['type'] ?? ''),
                    (array) ($payload['item_ids'] ?? []),
                    (string) ($payload['title'] ?? ''),
                    true
                );
            }

            if (! $record->is_saved) {
                $record->forceFill(['is_saved' => true])->save();
            }

            return $record;
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }
    }

    private function upsert(
        User $user,
        string $type,
        array $itemIds,
        string $title,
        ?int $comparisonId,
        bool $saved
    ): UserComparison {
        $signature = $this->signature($type, $itemIds);

        $record = UserComparison::firstOrNew([
            'user_id' => $user->id,
            'signature' => $signature,
        ]);

        $record->fill([
            'comparison_id' => $comparisonId ?: $record->comparison_id,
            'comparable_type' => $type,
            'item_ids' => array_values(array_map('intval', $itemIds)),
            'title' => $title,
            'last_viewed_at' => now(),
        ]);

        if ($saved) {
            $record->is_saved = true;
        }

        $record->save();

        return $record;
    }
}
