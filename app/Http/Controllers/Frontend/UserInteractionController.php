<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiTest;
use App\Models\UserInteraction;
use App\Services\Frontend\PendingUserActionService;
use App\Services\Frontend\UserInteractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserInteractionController extends Controller
{
    public function __construct(
        private readonly UserInteractionService $interactions,
        private readonly PendingUserActionService $pending,
    ) {
    }

    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:follow,helpful'],
            'target_type' => ['required', 'in:tool,model,company,review'],
            'target_id' => ['required', 'integer', 'min:1'],
        ]);

        if (! $request->user()) {
            return response()->json(['authenticated' => false, 'active' => false]);
        }

        return response()->json([
            'authenticated' => true,
            'active' => $this->interactions->isActive(
                $request->user(),
                $data['action'],
                $data['target_type'],
                (int) $data['target_id']
            ),
        ]);
    }

    public function intent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:follow,helpful'],
            'target_type' => ['required', 'in:tool,model,company,review'],
            'target_id' => ['required', 'integer', 'min:1'],
            'return_to' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->interactions->validateTarget(
            $data['action'],
            $data['target_type'],
            (int) $data['target_id']
        );

        if ($request->user()) {
            return response()->json(['authenticated' => true]);
        }

        $this->pending->remember($request, 'interaction', [
            'interaction_action' => $data['action'],
            'target_type' => $data['target_type'],
            'target_id' => (int) $data['target_id'],
        ]);

        $request->session()->put(
            'url.intended',
            $this->safeReturnUrl($request, (string) ($data['return_to'] ?? ''))
        );

        return response()->json([
            'authenticated' => false,
            'login_url' => route('login'),
        ]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:follow,helpful'],
            'target_type' => ['required', 'in:tool,model,company,review'],
            'target_id' => ['required', 'integer', 'min:1'],
        ]);

        $active = $this->interactions->toggle(
            $request->user(),
            $data['action'],
            $data['target_type'],
            (int) $data['target_id']
        );

        return response()->json([
            'active' => $active,
            'message' => match ($data['action']) {
                'helpful' => $active ? 'Marked as helpful.' : 'Helpful vote removed.',
                default => $active ? 'Following.' : 'Unfollowed.',
            },
        ]);
    }

    public function testHistory(Request $request): View
    {
        $history = UserInteraction::query()
            ->where('user_id', $request->user()->id)
            ->where('action', 'test_viewed')
            ->where('target_type', 'test')
            ->latest('updated_at')
            ->paginate(20);

        $tests = AiTest::query()
            ->withCount('results')
            ->whereIn('id', $history->getCollection()->pluck('target_id'))
            ->get()
            ->keyBy('id');

        return view('frontend.testlab.history', compact('history', 'tests'));
    }

    private function safeReturnUrl(Request $request, string $returnTo): string
    {
        $fallback = url()->previous() ?: route('home');

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
