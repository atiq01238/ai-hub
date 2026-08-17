<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Tool;
use Illuminate\Http\Request;

class PublicReviewController extends Controller
{
    public function create(Tool $tool)
    {
        $existingReview = $tool->reviews()
            ->where('user_id', auth()->id())
            ->where('review_type', 'user')
            ->first();

        return view('reviews.create', compact('tool', 'existingReview'));
    }

    public function store(Request $request, Tool $tool)
    {
        $data = $request->validate([
            'rating' => ['required', 'numeric', 'min:1', 'max:5', 'multiple_of:0.5'],
            'body'   => ['nullable', 'string', 'max:2000'],
        ]);

        $review = Review::updateOrCreate(
            [
                'tool_id' => $tool->id,
                'user_id' => $request->user()->id,
                'review_type' => 'user',
            ],
            [
                'rating' => $data['rating'],
                'body' => $data['body'] ?? null,
                'status' => 'pending',
                'moderation_note' => null,
                'moderated_by' => null,
                'moderated_at' => null,
            ]
        );

        return redirect()
            ->back()
            ->with('status', $review->wasRecentlyCreated
                ? 'Thanks! Your review will show once it is approved.'
                : 'Your review was updated and returned to the moderation queue.');
    }
}