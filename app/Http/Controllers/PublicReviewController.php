<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\Review;
use App\Models\Tool;
use Illuminate\Http\Request;

class PublicReviewController extends Controller
{
    /**
     * Backward compatibility for the original Tool review routes:
     * PublicReviewController::create / ::store
     */
    public function create(Tool $tool)
    {
        return $this->createTool($tool);
    }

    public function store(Request $request, Tool $tool)
    {
        return $this->storeTool($request, $tool);
    }

    public function createTool(Tool $tool)
    {
        abort_unless($tool->status === 'published', 404);

        return $this->form('tool', $tool);
    }

    public function storeTool(Request $request, Tool $tool)
    {
        abort_unless($tool->status === 'published', 404);

        return $this->storeReview($request, 'tool', $tool);
    }

    public function createModel(AiModel $model)
    {
        abort_unless(in_array($model->status, ['active', 'preview'], true), 404);

        return $this->form('model', $model);
    }

    public function storeModel(Request $request, AiModel $model)
    {
        abort_unless(in_array($model->status, ['active', 'preview'], true), 404);

        return $this->storeReview($request, 'model', $model);
    }

    private function form(string $type, Tool|AiModel $reviewable)
    {
        $existingReview = Review::query()
            ->where('user_id', auth()->id())
            ->where('review_type', 'user')
            ->when(
                $type === 'tool',
                fn ($query) => $query->where('tool_id', $reviewable->id),
                fn ($query) => $query->where('model_id', $reviewable->id)
            )
            ->first();

        return view('reviews.create', compact('reviewable', 'type', 'existingReview'));
    }

    private function storeReview(Request $request, string $type, Tool|AiModel $reviewable)
    {
        $data = $request->validate([
            'rating' => ['required', 'numeric', 'min:1', 'max:5', 'multiple_of:0.5'],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $identity = [
            'user_id' => $request->user()->id,
            'review_type' => 'user',
            'tool_id' => $type === 'tool' ? $reviewable->id : null,
            'model_id' => $type === 'model' ? $reviewable->id : null,
        ];

        $review = Review::updateOrCreate(
            $identity,
            [
                'rating' => $data['rating'],
                'body' => trim((string) ($data['body'] ?? '')) ?: null,
                'status' => 'pending',
                'moderation_note' => null,
                'moderated_by' => null,
                'moderated_at' => null,
            ]
        );

        return redirect()
            ->to(
                $type === 'tool'
                    ? url('/ai-tools/' . $reviewable->slug) . '#community-reviews'
                    : url('/ai-models/' . $reviewable->slug) . '#community-reviews'
            )
            ->with(
                'status',
                $review->wasRecentlyCreated
                    ? 'Thanks! Your review will appear after moderation.'
                    : 'Your review was updated and returned to moderation.'
            );
    }
}
