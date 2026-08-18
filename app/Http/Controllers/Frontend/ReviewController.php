<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'in:editorial,user'],
            'rating' => ['nullable', 'in:3,4,4.5'],
            'sort' => ['nullable', 'in:newest,rating,oldest'],
        ]);

        $query = Review::query()
            ->with(['tool.company', 'user'])
            ->published()
            ->whereHas('tool', fn ($q) => $q->where('status', 'published'));

        if ($q = trim($filters['q'] ?? '')) {
            $query->where(function (Builder $builder) use ($q) {
                $builder->where('verdict', 'like', "%{$q}%")
                    ->orWhere('body', 'like', "%{$q}%")
                    ->orWhereHas('tool', fn ($toolQuery) => $toolQuery->where('name', 'like', "%{$q}%"));
            });
        }

        if (!empty($filters['type'])) {
            $query->where('review_type', $filters['type']);
        }

        if (!empty($filters['rating'])) {
            $query->where('rating', '>=', (float) $filters['rating']);
        }

        match ($filters['sort'] ?? 'newest') {
            'rating' => $query->orderByDesc('rating')->latest(),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        $reviews = $query->paginate(12)->withQueryString();

        $stats = [
            'reviews' => Review::published()->count(),
            'editorial' => Review::published()->where('review_type', 'editorial')->count(),
            'community' => Review::published()->where('review_type', 'user')->count(),
            'average' => (float) (Review::published()->avg('rating') ?? 0),
        ];

        $topTools = Tool::query()
            ->select('tools.*')
            ->addSelect([
                'review_avg' => Review::selectRaw('AVG(rating)')
                    ->whereColumn('reviews.tool_id', 'tools.id')
                    ->where('status', 'published'),
                'review_count' => Review::selectRaw('COUNT(*)')
                    ->whereColumn('reviews.tool_id', 'tools.id')
                    ->where('status', 'published'),
            ])
            ->where('status', 'published')
            ->whereHas('reviews', fn ($q) => $q->published())
            ->orderByDesc('review_avg')
            ->orderByDesc('review_count')
            ->take(6)
            ->get();

        return view('frontend.reviews.index', compact('reviews', 'stats', 'topTools'));
    }

    public function show(Review $review)
    {
        abort_unless($review->status === 'published', 404);

        $review->load(['tool.company', 'tool.category', 'user']);
        abort_unless($review->tool && $review->tool->status === 'published', 404);

        $toolReviewStats = Review::published()
            ->where('tool_id', $review->tool_id)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();

        $relatedReviews = Review::query()
            ->with(['tool.company', 'user'])
            ->published()
            ->whereKeyNot($review->id)
            ->where(function (Builder $query) use ($review) {
                $query->where('tool_id', $review->tool_id)
                    ->orWhereHas('tool', fn ($toolQuery) => $toolQuery->where('category_id', $review->tool?->category_id));
            })
            ->latest()
            ->take(4)
            ->get();

        return view('frontend.reviews.show', compact('review', 'toolReviewStats', 'relatedReviews'));
    }
}
