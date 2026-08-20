<?php

namespace App\Services\Frontend;

use App\Models\AiModel;
use App\Models\Article;
use App\Models\Company;
use App\Models\NewsItem;
use App\Models\SavedItem;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SavedItemService
{
    public const TYPE_MAP = [
        'tool' => Tool::class,
        'model' => AiModel::class,
        'news' => NewsItem::class,
        'article' => Article::class,
        'company' => Company::class,
    ];

    public function typeMap(): array
    {
        return self::TYPE_MAP;
    }

    public function publicRecord(string $type, int $id): Model
    {
        abort_unless(array_key_exists($type, self::TYPE_MAP), 422);

        $modelClass = self::TYPE_MAP[$type];
        $query = $modelClass::query()->whereKey($id);

        if ($modelClass === Tool::class) {
            $query->where('status', 'published');
        } elseif ($modelClass === AiModel::class) {
            $query->whereIn('status', ['active', 'preview']);
        } elseif ($modelClass === NewsItem::class) {
            $query->where('status', 'published');
        } elseif ($modelClass === Article::class) {
            $query->where('status', 'published')->where('approval_status', 'approved');
        } elseif ($modelClass === Company::class) {
            $query->where('status', 'active');
        }

        return $query->firstOrFail();
    }

    public function toggle(User $user, string $type, int $id): bool
    {
        $record = $this->publicRecord($type, $id);
        $modelClass = self::TYPE_MAP[$type];

        $existing = SavedItem::query()
            ->where('user_id', $user->id)
            ->where('saveable_type', $modelClass)
            ->where('saveable_id', $record->getKey())
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        SavedItem::firstOrCreate([
            'user_id' => $user->id,
            'saveable_type' => $modelClass,
            'saveable_id' => $record->getKey(),
        ]);

        return true;
    }

    public function save(User $user, string $type, int $id): SavedItem
    {
        $record = $this->publicRecord($type, $id);

        return SavedItem::firstOrCreate([
            'user_id' => $user->id,
            'saveable_type' => self::TYPE_MAP[$type],
            'saveable_id' => $record->getKey(),
        ]);
    }

    public function rememberPending(Request $request, string $type, int $id): void
    {
        // Validate that the public object really exists before storing the intent.
        $this->publicRecord($type, $id);

        $request->session()->put('pending_user_action', [
            'action' => 'save',
            'type' => $type,
            'id' => $id,
        ]);
    }

    public function consumePending(Request $request, User $user): ?array
    {
        $pending = $request->session()->pull('pending_user_action');

        if (! is_array($pending) || ($pending['action'] ?? null) !== 'save') {
            return null;
        }

        $type = (string) ($pending['type'] ?? '');
        $id = (int) ($pending['id'] ?? 0);

        if (! array_key_exists($type, self::TYPE_MAP) || $id < 1) {
            return null;
        }

        // The item could have been unpublished/deleted while the user was signing in.
        // In that case, fail quietly and still complete login normally.
        try {
            $this->save($user, $type, $id);
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }

        return [
            'action' => 'save',
            'type' => $type,
            'id' => $id,
            'message' => 'Saved to your library.',
        ];
    }
}
