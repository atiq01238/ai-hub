<?php

namespace App\Services\Frontend;

use App\Models\AiModel;
use App\Models\AiTest;
use App\Models\Company;
use App\Models\Review;
use App\Models\Tool;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Database\Eloquent\Model;

class UserInteractionService
{
    private const TARGET_MAP = [
        'tool' => Tool::class,
        'model' => AiModel::class,
        'company' => Company::class,
        'review' => Review::class,
        'test' => AiTest::class,
    ];

    private const ACTION_TARGETS = [
        'follow' => ['tool', 'model', 'company'],
        'helpful' => ['review'],
        'test_viewed' => ['test'],
    ];

    public function validateTarget(string $action, string $type, int $id): Model
    {
        abort_unless(isset(self::ACTION_TARGETS[$action]), 422);
        abort_unless(in_array($type, self::ACTION_TARGETS[$action], true), 422);
        abort_unless(isset(self::TARGET_MAP[$type]), 422);

        $query = self::TARGET_MAP[$type]::query()->whereKey($id);

        if ($type === 'tool') {
            $query->where('status', 'published');
        } elseif ($type === 'model') {
            $query->whereIn('status', ['active', 'preview']);
        } elseif ($type === 'company') {
            $query->where('status', 'active');
        } elseif ($type === 'review') {
            $query->where('status', 'published');
        }

        return $query->firstOrFail();
    }

    public function toggle(User $user, string $action, string $type, int $id): bool
    {
        $this->validateTarget($action, $type, $id);

        $existing = UserInteraction::query()
            ->where('user_id', $user->id)
            ->where('action', $action)
            ->where('target_type', $type)
            ->where('target_id', $id)
            ->first();

        if ($existing) {
            $existing->delete();
            return false;
        }

        UserInteraction::create([
            'user_id' => $user->id,
            'action' => $action,
            'target_type' => $type,
            'target_id' => $id,
        ]);

        return true;
    }

    public function activate(User $user, string $action, string $type, int $id): UserInteraction
    {
        $this->validateTarget($action, $type, $id);

        return UserInteraction::firstOrCreate([
            'user_id' => $user->id,
            'action' => $action,
            'target_type' => $type,
            'target_id' => $id,
        ]);
    }

    public function recordTestView(User $user, int $testId): void
    {
        $this->validateTarget('test_viewed', 'test', $testId);

        UserInteraction::updateOrCreate(
            [
                'user_id' => $user->id,
                'action' => 'test_viewed',
                'target_type' => 'test',
                'target_id' => $testId,
            ],
            ['updated_at' => now()]
        );
    }

    public function isActive(User $user, string $action, string $type, int $id): bool
    {
        return UserInteraction::query()
            ->where('user_id', $user->id)
            ->where('action', $action)
            ->where('target_type', $type)
            ->where('target_id', $id)
            ->exists();
    }
}
