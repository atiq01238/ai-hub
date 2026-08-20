<?php

namespace App\Services\Frontend;

use App\Models\User;
use Illuminate\Http\Request;

class PendingUserActionService
{
    public function __construct(
        private readonly SavedItemService $savedItems,
        private readonly ComparisonHistoryService $comparisons,
        private readonly UserInteractionService $interactions,
    ) {
    }

    public function remember(Request $request, string $action, array $payload): void
    {
        $request->session()->put('pending_user_action', array_merge(
            ['action' => $action],
            $payload
        ));
    }

    public function consume(Request $request, User $user): ?array
    {
        $pending = $request->session()->pull('pending_user_action');

        if (! is_array($pending)) {
            return null;
        }

        try {
            return match ($pending['action'] ?? null) {
                'save' => $this->consumeSave($user, $pending),
                'interaction' => $this->consumeInteraction($user, $pending),
                'comparison_save' => $this->consumeComparison($user, $pending),
                default => null,
            };
        } catch (\Throwable $exception) {
            report($exception);
            return null;
        }
    }

    private function consumeSave(User $user, array $pending): ?array
    {
        $type = (string) ($pending['type'] ?? '');
        $id = (int) ($pending['id'] ?? 0);

        if ($id < 1 || ! array_key_exists($type, $this->savedItems->typeMap())) {
            return null;
        }

        $this->savedItems->save($user, $type, $id);

        return ['message' => 'Saved to your library.'];
    }

    private function consumeInteraction(User $user, array $pending): ?array
    {
        $action = (string) ($pending['interaction_action'] ?? '');
        $type = (string) ($pending['target_type'] ?? '');
        $id = (int) ($pending['target_id'] ?? 0);

        if ($id < 1) {
            return null;
        }

        $this->interactions->activate($user, $action, $type, $id);

        return [
            'message' => $action === 'helpful'
                ? 'Marked as helpful.'
                : 'You are now following this item.',
        ];
    }

    private function consumeComparison(User $user, array $pending): ?array
    {
        $record = $this->comparisons->savePayload($user, (array) ($pending['comparison'] ?? []));

        if (! $record) {
            return null;
        }

        return ['message' => 'Comparison saved to your account.'];
    }
}
