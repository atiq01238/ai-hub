<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Article;
use App\Models\Company;
use App\Models\NewsItem;
use App\Models\SavedItem;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedController extends Controller
{
    private const TYPE_MAP = [
        'tool' => Tool::class,
        'model' => AiModel::class,
        'news' => NewsItem::class,
        'article' => Article::class,
        'company' => Company::class,
    ];

    public function index(Request $request): View
    {
        $type = (string) $request->query('type', 'all');
        $type = $type === 'all' || array_key_exists($type, self::TYPE_MAP) ? $type : 'all';

        $recommendations = [
            'tools' => Tool::query()
                ->with('company')
                ->where('status', 'published')
                ->orderByDesc('popularity')
                ->orderByDesc('rating')
                ->limit(4)
                ->get(),
            'models' => AiModel::query()
                ->with('company')
                ->whereIn('status', ['active', 'preview'])
                ->orderByDesc('benchmark_score')
                ->limit(4)
                ->get(),
        ];

        if (! auth()->check()) {
            return view('frontend.saved.index', [
                'savedItems' => null,
                'counts' => collect(),
                'type' => $type,
                'recommendations' => $recommendations,
            ]);
        }

        $userId = (int) auth()->id();
        $query = SavedItem::query()
            ->where('user_id', $userId)
            ->with(['saveable' => function (MorphTo $morphTo): void {
                $morphTo->morphWith([
                    Tool::class => ['company', 'category'],
                    AiModel::class => ['company'],
                    NewsItem::class => ['company'],
                    Article::class => ['company', 'categoryTerm'],
                    Company::class => [],
                ]);
            }])
            ->latest();

        if ($type !== 'all') {
            $query->where('saveable_type', self::TYPE_MAP[$type]);
        }

        $savedItems = $query->paginate(18)->withQueryString();

        $counts = SavedItem::query()
            ->where('user_id', $userId)
            ->selectRaw('saveable_type, COUNT(*) as total')
            ->groupBy('saveable_type')
            ->pluck('total', 'saveable_type');

        return view('frontend.saved.index', compact('savedItems', 'counts', 'type', 'recommendations'));
    }

    public function toggle(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(self::TYPE_MAP))],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $modelClass = self::TYPE_MAP[$validated['type']];
        $record = $this->publicRecord($modelClass, (int) $validated['id']);
        $userId = (int) auth()->id();

        $existing = SavedItem::query()
            ->where('user_id', $userId)
            ->where('saveable_type', $modelClass)
            ->where('saveable_id', $record->getKey())
            ->first();

        if ($existing) {
            $existing->delete();
            $saved = false;
        } else {
            SavedItem::create([
                'user_id' => $userId,
                'saveable_type' => $modelClass,
                'saveable_id' => $record->getKey(),
            ]);
            $saved = true;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'saved' => $saved,
                'message' => $saved ? 'Saved to your library.' : 'Removed from your saved library.',
            ]);
        }

        return back()->with('status', $saved ? 'Saved to your library.' : 'Removed from your saved library.');
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(self::TYPE_MAP))],
            'ids' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! auth()->check()) {
            return response()->json(['authenticated' => false, 'saved_ids' => []]);
        }

        $ids = collect(explode(',', (string) ($validated['ids'] ?? '')))
            ->filter(fn ($id) => ctype_digit(trim($id)))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(100)
            ->values();

        if ($ids->isEmpty()) {
            return response()->json(['authenticated' => true, 'saved_ids' => []]);
        }

        $savedIds = SavedItem::query()
            ->where('user_id', auth()->id())
            ->where('saveable_type', self::TYPE_MAP[$validated['type']])
            ->whereIn('saveable_id', $ids)
            ->pluck('saveable_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return response()->json(['authenticated' => true, 'saved_ids' => $savedIds]);
    }

    private function publicRecord(string $modelClass, int $id)
    {
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
}
