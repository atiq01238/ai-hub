<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Article;
use App\Models\Company;
use App\Models\NewsItem;
use App\Models\SavedItem;
use App\Models\Tool;
use App\Services\Frontend\SavedItemService;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedController extends Controller
{
    public function __construct(private readonly SavedItemService $savedItems)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            $request->session()->put('url.intended', $request->fullUrl());

            return redirect()
                ->route('login')
                ->with('status', 'Sign in to view your saved library.');
        }

        $typeMap = $this->savedItems->typeMap();
        $type = (string) $request->query('type', 'all');
        $type = $type === 'all' || array_key_exists($type, $typeMap) ? $type : 'all';

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

        $userId = (int) $user->getAuthIdentifier();
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
            $query->where('saveable_type', $typeMap[$type]);
        }

        $savedItems = $query->paginate(18)->withQueryString();

        $counts = SavedItem::query()
            ->where('user_id', $userId)
            ->selectRaw('saveable_type, COUNT(*) as total')
            ->groupBy('saveable_type')
            ->pluck('total', 'saveable_type');

        return view('frontend.saved.index', compact('savedItems', 'counts', 'type', 'recommendations'));
    }

    public function intent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', array_keys($this->savedItems->typeMap()))],
            'id' => ['required', 'integer', 'min:1'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($request->user()) {
            return response()->json([
                'authenticated' => true,
                'login_url' => null,
            ]);
        }

        $this->savedItems->rememberPending($request, $validated['type'], (int) $validated['id']);

        $returnTo = $this->safeReturnUrl($request, (string) ($validated['return_to'] ?? ''));
        $request->session()->put('url.intended', $returnTo);

        return response()->json([
            'authenticated' => false,
            'login_url' => route('login'),
        ]);
    }

    public function toggle(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Authentication required.',
                    'login_url' => route('login'),
                ], 401);
            }

            $request->session()->put('url.intended', url()->previous());

            return redirect()->route('login');
        }

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:' . implode(',', array_keys($this->savedItems->typeMap()))],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $saved = $this->savedItems->toggle(
            $user,
            $validated['type'],
            (int) $validated['id']
        );

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
            'type' => ['required', 'string', 'in:' . implode(',', array_keys($this->savedItems->typeMap()))],
            'ids' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! $request->user()) {
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
            ->where('user_id', $request->user()->id)
            ->where('saveable_type', $this->savedItems->typeMap()[$validated['type']])
            ->whereIn('saveable_id', $ids)
            ->pluck('saveable_id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return response()->json(['authenticated' => true, 'saved_ids' => $savedIds]);
    }

    private function safeReturnUrl(Request $request, string $returnTo): string
    {
        $fallback = url()->previous();

        if ($returnTo === '') {
            return $fallback;
        }

        if (str_starts_with($returnTo, '//')) {
            return $fallback;
        }

        $parts = parse_url($returnTo);
        if ($parts === false) {
            return $fallback;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = $parts['host'] ?? null;

        if ($scheme !== '' && ! in_array($scheme, ['http', 'https'], true)) {
            return $fallback;
        }

        if ($host !== null && $host !== $request->getHost()) {
            return $fallback;
        }

        if ($host === null && ! str_starts_with($returnTo, '/')) {
            return $fallback;
        }

        return $returnTo;
    }
}
