<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CommunityComment;
use App\Services\Frontend\CommunityModerationService;
use App\Services\Frontend\ModerationRiskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommunityCommentController extends Controller
{
    public function __construct(private readonly CommunityModerationService $moderation, private readonly ModerationRiskService $risk)
    {
    }
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        $query = CommunityComment::query()->with(['user', 'moderator'])->withCount('reports');

        if ($status = $request->string('status')->toString()) {
            if (in_array($status, ['pending', 'published', 'hidden', 'spam'], true)) {
                $query->where('status', $status);
            }
        }

        if ($trust = $request->string('trust')->toString()) {
            if (in_array($trust, ['normal', 'trusted', 'restricted'], true)) {
                $query->whereHas('user', fn ($user) => $user->where('community_trust_level', $trust));
            }
        }

        if ($type = $request->string('type')->toString()) {
            if (in_array($type, ['news', 'article', 'comparison', 'benchmark', 'test'], true)) {
                $query->where('commentable_type', $type);
            }
        }

        if ($search = trim($request->string('search')->toString())) {
            $query->where(function ($q) use ($search) {
                $q->where('body', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $base = CommunityComment::query();
        $comments = $query->latest()->paginate(25)->withQueryString();

        $comments->getCollection()->each(
            fn ($comment) => $comment->setAttribute('risk', $this->risk->score($comment))
        );

        return view('community.comments.index', [
            'comments' => $comments,
            'counts' => [
                'all' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'published' => (clone $base)->where('status', 'published')->count(),
                'hidden' => (clone $base)->where('status', 'hidden')->count(),
                'spam' => (clone $base)->where('status', 'spam')->count(),
            ],
        ]);
    }

    public function update(Request $request, CommunityComment $comment): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate([
            'status' => ['required', 'in:pending,published,hidden,spam'],
            'moderation_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $oldStatus = $comment->status;

        $comment->update([
            'status' => $data['status'],
            'auto_published' => false,
            'moderation_note' => $data['moderation_note'] ?? null,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        $this->moderation->afterAdminModeration($comment->fresh('user'), $oldStatus);

        if (class_exists(\App\Services\NotificationService::class) && $oldStatus !== $comment->status) {
            $notifications = app(\App\Services\NotificationService::class);
            $title = match ($comment->status) {
                'published' => $comment->parent_id ? 'Your reply was approved' : 'Your comment was approved',
                'hidden' => 'Your comment was hidden',
                'spam' => 'Your comment was not published',
                default => 'Your comment is awaiting moderation',
            };

            $notifications->user(
                $comment->user_id,
                $title,
                $data['moderation_note'] ?? ('Moderation status: ' . ucfirst($comment->status)),
                route('account.comments'),
                'message-square',
                $comment->status === 'published' ? 'pos' : 'warn',
                'comment_moderation'
            );
        }

        return back()->with('status', 'Comment moderation updated.');
    }

    public function destroy(Request $request, CommunityComment $comment): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $comment->delete();

        return back()->with('status', 'Comment moved to the recovery bin.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()
            && $request->user()->role === 'admin'
            && $request->user()->status === 'active',
            403
        );
    }
}
