<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleWorkflowEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApprovalWorkflowController extends Controller
{
    private const STAGES = ['draft', 'in_review', 'needs_changes', 'approved'];

    public function index(Request $request)
    {
        $articles = Article::with(['author', 'reviewer'])
            ->whereIn('approval_status', self::STAGES)
            ->latest('updated_at')
            ->get()
            ->groupBy('approval_status');

        $published = Article::with(['author', 'reviewer'])
            ->whereIn('status', ['scheduled', 'published'])
            ->latest('updated_at')
            ->take(20)
            ->get();

        $history = ArticleWorkflowEvent::with(['article', 'user'])->latest()->take(30)->get();

        return view('content.approval-workflow', [
            'articlesByStage' => $articles,
            'publishedArticles' => $published,
            'history' => $history,
            'reviewers' => User::orderBy('name')->get(),
        ]);
    }

    public function submit(Request $request, int $id)
    {
        $article = Article::findOrFail($id);
        $data = $request->validate(['reviewer_id' => ['nullable', 'exists:users,id']]);
        $this->transition($article, 'in_review', 'submitted_for_review', 'Submitted for review.', $data['reviewer_id'] ?? $article->reviewer_id);
        return back()->with('status', 'Article submitted for review.');
    }

    public function requestChanges(Request $request, int $id)
    {
        $article = Article::findOrFail($id);
        $data = $request->validate(['comment' => ['required', 'string', 'max:2000']]);
        abort_unless(in_array($article->approval_status, ['in_review', 'approved'], true), 422);
        $this->transition($article, 'needs_changes', 'changes_requested', $data['comment']);
        return back()->with('status', 'Changes requested.');
    }

    public function approve(Request $request, int $id)
    {
        $article = Article::findOrFail($id);
        $data = $request->validate(['comment' => ['nullable', 'string', 'max:2000']]);
        abort_unless(in_array($article->approval_status, ['in_review', 'needs_changes'], true), 422);
        $this->transition($article, 'approved', 'approved', $data['comment'] ?? 'Approved for publication.');
        return back()->with('status', 'Article approved. It can now be scheduled or published.');
    }

    public function resubmit(Request $request, int $id)
    {
        $article = Article::findOrFail($id);
        abort_unless($article->approval_status === 'needs_changes', 422);
        $data = $request->validate(['comment' => ['nullable', 'string', 'max:2000']]);
        $this->transition($article, 'in_review', 'resubmitted', $data['comment'] ?? 'Changes completed and resubmitted.');
        return back()->with('status', 'Article resubmitted.');
    }

    private function transition(Article $article, string $to, string $action, ?string $comment, ?int $reviewerId = null): void
    {
        DB::transaction(function () use ($article, $to, $action, $comment, $reviewerId) {
            $from = $article->approval_status;
            $updates = ['approval_status' => $to];
            if ($reviewerId !== null) $updates['reviewer_id'] = $reviewerId;
            if ($to === 'in_review') $updates['submitted_for_review_at'] = now();
            if ($to === 'approved') $updates['approved_at'] = now();
            $article->update($updates);

            ArticleWorkflowEvent::create([
                'article_id' => $article->id,
                'user_id' => auth()->id(),
                'from_status' => $from,
                'to_status' => $to,
                'action' => $action,
                'comment' => $comment,
            ]);
        });
    }
}
