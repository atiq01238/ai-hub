<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Comparison;
use App\Models\UserComparison;
use App\Services\Frontend\ComparisonHistoryService;
use App\Services\Frontend\PendingUserActionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserComparisonController extends Controller
{
    public function __construct(
        private readonly ComparisonHistoryService $history,
        private readonly PendingUserActionService $pending,
    ) {
    }

    public function index(Request $request): View
    {
        $comparisons = UserComparison::query()
            ->where('user_id', $request->user()->id)
            ->where('is_saved', true)
            ->latest('updated_at')
            ->paginate(15);

        return view('frontend.comparisons.my', compact('comparisons'));
    }

    public function history(Request $request): View
    {
        $comparisons = UserComparison::query()
            ->where('user_id', $request->user()->id)
            ->whereNotNull('last_viewed_at')
            ->latest('last_viewed_at')
            ->paginate(20);

        return view('frontend.comparisons.history', compact('comparisons'));
    }

    public function status(Request $request): JsonResponse
    {
        $payload = $this->validatedPayload($request);

        if (! $request->user()) {
            return response()->json(['authenticated' => false, 'saved' => false]);
        }

        $signature = $this->history->signature($payload['type'], $payload['item_ids']);

        $saved = UserComparison::query()
            ->where('user_id', $request->user()->id)
            ->where('signature', $signature)
            ->where('is_saved', true)
            ->exists();

        return response()->json(['authenticated' => true, 'saved' => $saved]);
    }

    public function intent(Request $request): JsonResponse
    {
        $payload = $this->validatedPayload($request);

        if ($request->user()) {
            return response()->json(['authenticated' => true]);
        }

        $this->pending->remember($request, 'comparison_save', [
            'comparison' => $payload,
        ]);

        $request->session()->put('url.intended', $this->safeReturnUrl(
            $request,
            (string) $request->input('return_to')
        ));

        return response()->json([
            'authenticated' => false,
            'login_url' => route('login'),
        ]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $payload = $this->validatedPayload($request);

        $comparison = ! empty($payload['comparison_id'])
            ? Comparison::query()->where('status', 'published')->findOrFail($payload['comparison_id'])
            : null;

        $record = $this->history->toggle(
            $request->user(),
            $payload['type'],
            $payload['item_ids'],
            $payload['title'] ?? null,
            $comparison
        );

        return response()->json([
            'saved' => $record->is_saved,
            'message' => $record->is_saved
                ? 'Comparison saved to your account.'
                : 'Comparison removed from your saved comparisons.',
        ]);
    }

    private function validatedPayload(Request $request): array
    {
        $data = $request->validate([
            'comparison_id' => ['nullable', 'integer', 'min:1'],
            'comparison_slug' => ['nullable', 'string', 'max:180'],
            'type' => ['nullable', 'in:tool,model'],
            'item_ids' => ['nullable', 'array', 'min:2', 'max:4'],
            'item_ids.*' => ['integer', 'distinct', 'min:1'],
            'title' => ['nullable', 'string', 'max:255'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        if (! empty($data['comparison_slug']) && empty($data['comparison_id'])) {
            $comparison = Comparison::query()
                ->where('status', 'published')
                ->where('slug', $data['comparison_slug'])
                ->firstOrFail();

            $data['comparison_id'] = $comparison->id;
            $data['type'] = $comparison->comparable_type;
            $data['item_ids'] = array_map('intval', (array) $comparison->item_ids);
            $data['title'] = $comparison->title;
        }

        if (! empty($data['comparison_id']) && (empty($data['type']) || empty($data['item_ids']))) {
            $comparison = Comparison::query()
                ->where('status', 'published')
                ->findOrFail((int) $data['comparison_id']);

            $data['type'] = $comparison->comparable_type;
            $data['item_ids'] = array_map('intval', (array) $comparison->item_ids);
            $data['title'] = $comparison->title;
        }

        abort_unless(! empty($data['type']) && count((array) ($data['item_ids'] ?? [])) >= 2, 422);

        $this->history->validateItems($data['type'], $data['item_ids']);

        return $data;
    }

    private function safeReturnUrl(Request $request, string $returnTo): string
    {
        $fallback = url()->previous() ?: route('comparisons.index');

        if ($returnTo === '' || str_starts_with($returnTo, '//')) {
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
