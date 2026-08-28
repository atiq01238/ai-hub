<?php

namespace App\Services\Frontend;

use App\Models\AiModel;
use App\Models\AppNotification;
use App\Models\Article;
use App\Models\Comparison;
use App\Models\Review;
use App\Models\Tool;
use App\Models\User;
use App\Models\UserInteraction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class QuickFeedbackService
{
    public function validateTarget(string $kind, string $type, int $id): Model
    {
        abort_unless($id > 0, 422);

        if ($kind === 'rating') {
            abort_unless(in_array($type, ['tool', 'model', 'comparison'], true), 422);

            return match ($type) {
                'tool' => Tool::query()->whereKey($id)->where('status', 'published')->firstOrFail(),
                'model' => AiModel::query()->whereKey($id)->whereIn('status', ['active', 'preview'])->firstOrFail(),
                'comparison' => Comparison::query()->whereKey($id)->where('status', 'published')->firstOrFail(),
            };
        }

        abort_unless($kind === 'vote', 422);
        abort_unless(in_array($type, ['article', 'pricing'], true), 422);

        return match ($type) {
            'article' => Article::query()
                ->whereKey($id)
                ->where('status', 'published')
                ->where('approval_status', 'approved')
                ->firstOrFail(),
            'pricing' => Tool::query()
                ->whereKey($id)
                ->where('status', 'published')
                ->whereHas('pricingPlans')
                ->firstOrFail(),
        };
    }

    public function storeRating(User $user, string $type, int $id, int $score): array
    {
        abort_unless($score >= 1 && $score <= 5, 422);
        $target = $this->validateTarget('rating', $type, $id);

        if ($type === 'tool' || $type === 'model') {
            $review = Review::withTrashed()
                ->where('user_id', $user->id)
                ->where('review_type', 'user')
                ->when(
                    $type === 'tool',
                    fn ($query) => $query->where('tool_id', $id)->whereNull('model_id'),
                    fn ($query) => $query->where('model_id', $id)->whereNull('tool_id')
                )
                ->first();

            if (! $review) {
                $review = new Review([
                    'user_id' => $user->id,
                    'review_type' => 'user',
                    'tool_id' => $type === 'tool' ? $id : null,
                    'model_id' => $type === 'model' ? $id : null,
                    'rating' => $score,
                    'status' => 'published',
                    'moderated_at' => now(),
                ]);
                $review->save();
            } else {
                if ($review->trashed()) {
                    $review->restore();
                }

                $review->rating = $score;

                // A star-only rating contains no text that needs moderation.
                // If a written review already exists, preserve its moderation state.
                if (! filled($review->body)) {
                    $review->status = 'published';
                    $review->moderation_note = null;
                    $review->moderated_by = null;
                    $review->moderated_at = now();
                }

                $review->save();
            }
        } else {
            UserInteraction::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'action' => 'quick_rating',
                    'target_type' => 'comparison',
                    'target_id' => $target->id,
                ],
                ['metadata' => ['score' => $score]]
            );
        }

        return array_merge(
            $this->ratingSummary($type, $id, $user),
            ['message' => "Your {$score}-star rating was saved."]
        );
    }

    public function ratingSummary(string $type, int $id, ?User $viewer = null): array
    {
        $this->validateTarget('rating', $type, $id);

        if ($type === 'tool' || $type === 'model') {
            $query = Review::query()
                ->where('review_type', 'user')
                ->where('status', 'published');

            $type === 'tool'
                ? $query->where('tool_id', $id)
                : $query->where('model_id', $id);

            $count = (int) (clone $query)->count();
            $average = $count > 0 ? round((float) (clone $query)->avg('rating'), 1) : null;

            $viewerScore = null;
            if ($viewer) {
                $viewerQuery = Review::query()
                    ->where('user_id', $viewer->id)
                    ->where('review_type', 'user');

                $type === 'tool'
                    ? $viewerQuery->where('tool_id', $id)
                    : $viewerQuery->where('model_id', $id);

                $viewerScore = $viewerQuery->value('rating');
            }

            return [
                'average' => $average,
                'count' => $count,
                'viewer_score' => $viewerScore !== null ? (float) $viewerScore : null,
            ];
        }

        $rows = UserInteraction::query()
            ->where('action', 'quick_rating')
            ->where('target_type', 'comparison')
            ->where('target_id', $id)
            ->get(['user_id', 'metadata']);

        $scores = $rows->map(fn (UserInteraction $row) => (float) data_get($row->metadata, 'score'))
            ->filter(fn (float $score) => $score >= 1 && $score <= 5)
            ->values();

        $viewerScore = $viewer
            ? $this->viewerInteractionValue($rows, $viewer->id, 'score')
            : null;

        return [
            'average' => $scores->isNotEmpty() ? round((float) $scores->avg(), 1) : null,
            'count' => $scores->count(),
            'viewer_score' => $viewerScore !== null ? (float) $viewerScore : null,
        ];
    }

    public function storeVote(User $user, string $type, int $id, string $choice): array
    {
        $target = $this->validateTarget('vote', $type, $id);

        $allowed = $type === 'article'
            ? ['helpful', 'not_helpful']
            : ['accurate', 'outdated'];

        abort_unless(in_array($choice, $allowed, true), 422);

        $identity = [
            'user_id' => $user->id,
            'action' => 'quick_feedback',
            'target_type' => $type,
            'target_id' => $id,
        ];

        $existing = UserInteraction::where($identity)->first();
        $previousChoice = $existing ? (string) data_get($existing->metadata, 'choice') : null;

        UserInteraction::updateOrCreate(
            $identity,
            ['metadata' => ['choice' => $choice]]
        );

        if ($type === 'pricing' && $choice === 'outdated' && $previousChoice !== 'outdated') {
            AppNotification::broadcast(
                'flag',
                'warn',
                'Pricing flagged by community',
                ($target->name ?? 'A tool') . ' pricing was reported as potentially outdated.',
                url('/admin/pricing'),
                'pricing_feedback'
            );
        }

        $message = match ($choice) {
            'helpful' => 'Thanks — glad this article helped.',
            'not_helpful' => 'Thanks — your feedback will help improve this article.',
            'accurate' => 'Thanks for confirming this pricing.',
            'outdated' => 'Thanks — this pricing was flagged for review.',
        };

        return array_merge(
            $this->voteSummary($type, $id, $user),
            ['message' => $message]
        );
    }

    public function voteSummary(string $type, int $id, ?User $viewer = null): array
    {
        $this->validateTarget('vote', $type, $id);

        $rows = UserInteraction::query()
            ->where('action', 'quick_feedback')
            ->where('target_type', $type)
            ->where('target_id', $id)
            ->get(['user_id', 'metadata']);

        $choices = $rows->map(fn (UserInteraction $row) => (string) data_get($row->metadata, 'choice'));
        $viewerChoice = $viewer
            ? $this->viewerInteractionValue($rows, $viewer->id, 'choice')
            : null;

        $counts = $type === 'article'
            ? [
                'helpful' => $choices->filter(fn ($value) => $value === 'helpful')->count(),
                'not_helpful' => $choices->filter(fn ($value) => $value === 'not_helpful')->count(),
            ]
            : [
                'accurate' => $choices->filter(fn ($value) => $value === 'accurate')->count(),
                'outdated' => $choices->filter(fn ($value) => $value === 'outdated')->count(),
            ];

        return [
            'counts' => $counts,
            'total' => array_sum($counts),
            'viewer_choice' => $viewerChoice,
        ];
    }

    public function store(User $user, string $kind, string $type, int $id, int|string $value): array
    {
        return $kind === 'rating'
            ? $this->storeRating($user, $type, $id, (int) $value)
            : $this->storeVote($user, $type, $id, (string) $value);
    }

    private function viewerInteractionValue(Collection $rows, int $userId, string $key): mixed
    {
        $row = $rows->first(fn (UserInteraction $item) => (int) $item->user_id === $userId);

        return $row ? data_get($row->metadata, $key) : null;
    }
}
