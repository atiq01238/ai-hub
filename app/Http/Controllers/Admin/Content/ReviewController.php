<?php

namespace App\Http\Controllers\Admin\Content;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = Review::with(['tool', 'user'])
            ->latest()
            ->paginate(20);

        return view('content.reviews.index', compact('reviews'));
    }

    public function editor()
    {
        return view('content.reviews.editor');
    }

    public function show(int $id)
    {
        $review = Review::with(['tool', 'user'])->findOrFail($id);

        return view('content.reviews.show', compact('review'));
    }

    public function approve(int $id)
    {
        $review = Review::findOrFail($id);
        $review->status = 'published';
        $review->save(); // triggers ReviewObserver -> recalculates the tool's rating

        return redirect()
            ->back()
            ->with('status', 'Review approved.');
    }

    public function flag(int $id)
    {
        $review = Review::findOrFail($id);
        $review->status = 'flagged';
        $review->save();

        return redirect()
            ->back()
            ->with('status', 'Review flagged.');
    }

    public function destroy(int $id)
    {
        Review::findOrFail($id)->delete(); // triggers ReviewObserver -> recalculates the tool's rating

        return redirect()
            ->route('admin.content.reviews.index')
            ->with('status', 'Review deleted.');
    }
}
