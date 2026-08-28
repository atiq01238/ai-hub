<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Frontend\PendingUserActionService;
use App\Services\Frontend\QuickFeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function __construct(
        private readonly QuickFeedbackService $feedback,
        private readonly PendingUserActionService $pending,
    ) {
    }

    public function status(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(['rating', 'vote'])],
            'type' => ['required', Rule::in(['tool', 'model', 'comparison', 'article', 'pricing'])],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $payload = $data['kind'] === 'rating'
            ? $this->feedback->ratingSummary($data['type'], (int) $data['id'], $request->user())
            : $this->feedback->voteSummary($data['type'], (int) $data['id'], $request->user());

        return response()->json(array_merge($payload, [
            'authenticated' => (bool) $request->user(),
        ]));
    }

    public function intent(Request $request): JsonResponse
    {
        $data = $this->validatedAction($request, true);
        $value = $this->valueFrom($data);

        $this->feedback->validateTarget($data['kind'], $data['type'], (int) $data['id']);

        if ($request->user()) {
            return response()->json(['authenticated' => true]);
        }

        $this->pending->remember($request, 'quick_feedback', [
            'kind' => $data['kind'],
            'type' => $data['type'],
            'id' => (int) $data['id'],
            'value' => $value,
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

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedAction($request);

        $result = $this->feedback->store(
            $request->user(),
            $data['kind'],
            $data['type'],
            (int) $data['id'],
            $this->valueFrom($data),
        );

        return response()->json($result);
    }

    private function validatedAction(Request $request, bool $withReturn = false): array
    {
        $rules = [
            'kind' => ['required', Rule::in(['rating', 'vote'])],
            'type' => ['required', Rule::in(['tool', 'model', 'comparison', 'article', 'pricing'])],
            'id' => ['required', 'integer', 'min:1'],
            'score' => ['nullable', 'integer', 'between:1,5', 'required_if:kind,rating'],
            'choice' => ['nullable', 'string', Rule::in(['helpful', 'not_helpful', 'accurate', 'outdated']), 'required_if:kind,vote'],
        ];

        if ($withReturn) {
            $rules['return_to'] = ['nullable', 'string', 'max:2000'];
        }

        $data = $request->validate($rules);

        if ($data['kind'] === 'rating') {
            abort_unless(in_array($data['type'], ['tool', 'model', 'comparison'], true), 422);
        } else {
            abort_unless(in_array($data['type'], ['article', 'pricing'], true), 422);
            $allowed = $data['type'] === 'article'
                ? ['helpful', 'not_helpful']
                : ['accurate', 'outdated'];
            abort_unless(in_array((string) $data['choice'], $allowed, true), 422);
        }

        return $data;
    }

    private function valueFrom(array $data): int|string
    {
        return $data['kind'] === 'rating'
            ? (int) $data['score']
            : (string) $data['choice'];
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
