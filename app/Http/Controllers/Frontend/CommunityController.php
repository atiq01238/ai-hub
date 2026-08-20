<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CommunityComment;
use App\Models\CommunityReaction;
use App\Models\Report;
use App\Models\Review;
use App\Services\Frontend\CommunityTargetService;
use App\Services\Frontend\CommunityModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunityController extends Controller
{
    public function __construct(
        private readonly CommunityTargetService $targets,
        private readonly CommunityModerationService $moderation,
    ) {
    }

    public function context(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:1800'],
        ]);

        $context = $this->targets->resolvePath($request, $data['path']);

        return response()->json([
            'context' => $context,
            'authenticated' => (bool) $request->user(),
            'login_url' => route('login'),
        ]);
    }

    public function comments(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:news,article,comparison,benchmark,test'],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $this->targets->resolve($data['type'], (int) $data['id']);

        $viewerId = $request->user()?->id;

        $query = CommunityComment::query()
            ->with('user:id,name')
            ->where('commentable_type', $data['type'])
            ->where('commentable_id', (int) $data['id'])
            ->whereNull('parent_id')
            ->where(function ($q) use ($viewerId) {
                $q->where('status', 'published');
                if ($viewerId) {
                    $q->orWhere('user_id', $viewerId);
                }
            })
            ->latest();

        $comments = $query->limit(40)->get();

        $ids = $comments->pluck('id');
        $replies = CommunityComment::query()
            ->with('user:id,name')
            ->whereIn('parent_id', $ids)
            ->where(function ($q) use ($viewerId) {
                $q->where('status', 'published');
                if ($viewerId) {
                    $q->orWhere('user_id', $viewerId);
                }
            })
            ->oldest()
            ->get()
            ->groupBy('parent_id');

        $allIds = $ids->merge($replies->flatten()->pluck('id'))->unique()->values();

        $helpfulCounts = CommunityReaction::query()
            ->where('reactable_type', 'comment')
            ->where('reaction', 'helpful')
            ->whereIn('reactable_id', $allIds)
            ->selectRaw('reactable_id, COUNT(*) as total')
            ->groupBy('reactable_id')
            ->pluck('total', 'reactable_id');

        $viewerHelpful = collect();
        if ($viewerId) {
            $viewerHelpful = CommunityReaction::query()
                ->where('user_id', $viewerId)
                ->where('reactable_type', 'comment')
                ->where('reaction', 'helpful')
                ->whereIn('reactable_id', $allIds)
                ->pluck('reactable_id')
                ->flip();
        }

        $serialize = function (CommunityComment $comment) use ($helpfulCounts, $viewerHelpful, $viewerId): array {
            return [
                'id' => $comment->id,
                'body' => $comment->body,
                'status' => $comment->status,
                'mine' => $viewerId && (int) $comment->user_id === (int) $viewerId,
                'helpful_count' => (int) ($helpfulCounts[$comment->id] ?? 0),
                'helpful' => $viewerHelpful->has($comment->id),
                'created_at' => $comment->created_at?->toIso8601String(),
                'created_human' => $comment->created_at?->diffForHumans(),
                'user' => [
                    'id' => $comment->user?->id,
                    'name' => $comment->user?->name ?? 'Community member',
                    'initial' => strtoupper(substr($comment->user?->name ?? 'U', 0, 1)),
                ],
            ];
        };

        return response()->json([
            'authenticated' => (bool) $viewerId,
            'count' => CommunityComment::query()
                ->where('commentable_type', $data['type'])
                ->where('commentable_id', (int) $data['id'])
                ->where('status', 'published')
                ->count(),
            'comments' => $comments->map(function (CommunityComment $comment) use ($replies, $serialize) {
                $row = $serialize($comment);
                $row['replies'] = collect($replies->get($comment->id, []))->map($serialize)->values();
                return $row;
            })->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:news,article,comparison,benchmark,test'],
            'id' => ['required', 'integer', 'min:1'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'body' => ['required', 'string', 'min:2', 'max:3000'],
        ]);

        $this->targets->resolve($data['type'], (int) $data['id']);

        $parent = null;
        if (! empty($data['parent_id'])) {
            $parent = CommunityComment::query()
                ->whereKey((int) $data['parent_id'])
                ->where('commentable_type', $data['type'])
                ->where('commentable_id', (int) $data['id'])
                ->whereNull('parent_id')
                ->firstOrFail();
        }

        $decision = $this->moderation->decide($request->user(), $data['body']);

        $comment = CommunityComment::create([
            'user_id' => $request->user()->id,
            'parent_id' => $parent?->id,
            'commentable_type' => $data['type'],
            'commentable_id' => (int) $data['id'],
            'body' => trim($data['body']),
            'status' => $decision['status'],
            'moderation_reason' => $decision['reason'],
            'auto_published' => $decision['auto_published'],
        ]);

        $message = $comment->status === 'published'
            ? ($parent
                ? 'Reply published instantly.'
                : 'Comment published instantly.')
            : 'Your contribution was sent to moderation.';

        // Notification package is optional. If it has already been installed,
        // only pending items alert admins and auto-published replies alert the
        // original participant immediately.
        if (class_exists(\App\Services\NotificationService::class)) {
            $notifications = app(\App\Services\NotificationService::class);

            if ($comment->status === 'pending') {
                $notifications->admin(
                    $parent ? 'Reply awaiting approval' : 'Comment awaiting approval',
                    ($request->user()->name ?? 'A member') . ' · ' . $decision['reason'],
                    route('admin.community.comments.index', ['status' => 'pending']),
                    'message-square',
                    'info',
                    'pending_comment'
                );
            } elseif ($parent && (int) $parent->user_id !== (int) $comment->user_id) {
                $notifications->user(
                    $parent->user_id,
                    'New reply to your comment',
                    ($request->user()->name ?? 'Someone') . ' replied to your discussion.',
                    route('account.comments'),
                    'reply',
                    'info',
                    'comment_reply'
                );
            }
        }

        return response()->json([
            'message' => $message,
            'auto_published' => $comment->auto_published,
            'comment' => [
                'id' => $comment->id,
                'status' => $comment->status,
            ],
        ], 201);
    }

    public function update(Request $request, CommunityComment $comment): JsonResponse
    {
        abort_unless((int) $comment->user_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:3000'],
        ]);

        $decision = $this->moderation->decide($request->user(), $data['body']);

        $comment->update([
            'body' => trim($data['body']),
            'status' => $decision['status'],
            'moderation_reason' => $decision['reason'],
            'auto_published' => $decision['auto_published'],
            'moderation_note' => null,
            'moderated_by' => null,
            'moderated_at' => null,
        ]);

        return response()->json([
            'message' => $comment->status === 'published'
                ? 'Comment updated and published.'
                : 'Comment updated and sent to moderation.',
            'auto_published' => $comment->auto_published,
        ]);
    }

    public function destroy(Request $request, CommunityComment $comment): JsonResponse
    {
        abort_unless((int) $comment->user_id === (int) $request->user()->id, 403);

        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }

    public function toggleHelpful(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:comment,review'],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        if ($data['type'] === 'comment') {
            CommunityComment::query()->where('status', 'published')->findOrFail((int) $data['id']);
        } else {
            Review::query()->where('status', 'published')->findOrFail((int) $data['id']);
        }

        $key = [
            'user_id' => $request->user()->id,
            'reactable_type' => $data['type'],
            'reactable_id' => (int) $data['id'],
            'reaction' => 'helpful',
        ];

        $existing = CommunityReaction::where($key)->first();

        if ($existing) {
            $existing->delete();
            $active = false;
        } else {
            CommunityReaction::create($key);
            $active = true;
        }

        $count = CommunityReaction::query()
            ->where('reactable_type', $data['type'])
            ->where('reactable_id', (int) $data['id'])
            ->where('reaction', 'helpful')
            ->count();

        return response()->json([
            'active' => $active,
            'count' => $count,
            'message' => $active ? 'Marked as helpful.' : 'Helpful vote removed.',
        ]);
    }

    public function report(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:comment,review'],
            'id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'in:spam,harassment,misinformation,off_topic,other'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $reportable = $data['type'] === 'comment'
            ? CommunityComment::query()->findOrFail((int) $data['id'])
            : Review::query()->findOrFail((int) $data['id']);

        $alreadyOpen = Report::query()
            ->where('reporter_id', $request->user()->id)
            ->where('reportable_type', $reportable::class)
            ->where('reportable_id', $reportable->id)
            ->whereIn('status', ['pending', 'reviewing'])
            ->exists();

        if (! $alreadyOpen) {
            Report::create([
                'reporter_id' => $request->user()->id,
                'reportable_type' => $reportable::class,
                'reportable_id' => $reportable->id,
                'reason' => $data['reason'],
                'description' => $data['description'] ?? null,
                'priority' => 'medium',
                'status' => 'pending',
            ]);
        }

        return response()->json([
            'message' => $alreadyOpen
                ? 'You already reported this item.'
                : 'Report submitted for moderation.',
        ]);
    }

    public function reviews(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:tool,model'],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $this->targets->reviewTarget($data['type'], (int) $data['id']);

        $query = Review::query()
            ->with('user:id,name')
            ->where('review_type', 'user')
            ->where('status', 'published');

        $data['type'] === 'tool'
            ? $query->where('tool_id', (int) $data['id'])
            : $query->where('model_id', (int) $data['id']);

        $reviews = $query->latest('moderated_at')->latest()->limit(20)->get();
        $ids = $reviews->pluck('id');

        $counts = CommunityReaction::query()
            ->where('reactable_type', 'review')
            ->where('reaction', 'helpful')
            ->whereIn('reactable_id', $ids)
            ->selectRaw('reactable_id, COUNT(*) as total')
            ->groupBy('reactable_id')
            ->pluck('total', 'reactable_id');

        $viewerHelpful = collect();
        if ($request->user()) {
            $viewerHelpful = CommunityReaction::query()
                ->where('user_id', $request->user()->id)
                ->where('reactable_type', 'review')
                ->where('reaction', 'helpful')
                ->whereIn('reactable_id', $ids)
                ->pluck('reactable_id')
                ->flip();
        }

        return response()->json([
            'authenticated' => (bool) $request->user(),
            'count' => $reviews->count(),
            'average' => round((float) ($reviews->avg('rating') ?? 0), 1),
            'reviews' => $reviews->map(fn (Review $review) => [
                'id' => $review->id,
                'rating' => (float) $review->rating,
                'body' => $review->body,
                'created_human' => $review->created_at?->diffForHumans(),
                'helpful_count' => (int) ($counts[$review->id] ?? 0),
                'helpful' => $viewerHelpful->has($review->id),
                'user' => [
                    'name' => $review->user?->name ?? 'Community member',
                    'initial' => strtoupper(substr($review->user?->name ?? 'U', 0, 1)),
                ],
            ])->values(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $returnTo = (string) $request->query('return_to', url()->previous());

        $parts = parse_url($returnTo);
        $host = $parts['host'] ?? null;
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        $safe = $returnTo;
        if (
            str_starts_with($returnTo, '//')
            || ($scheme !== '' && ! in_array($scheme, ['http', 'https'], true))
            || ($host !== null && $host !== $request->getHost())
        ) {
            $safe = route('home');
        }

        $request->session()->put('url.intended', $safe);

        return redirect()->route('login');
    }
}
