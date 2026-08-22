<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Review;
use App\Models\Tool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

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

        $query = $this->publicReviews()
            ->with(['tool.company', 'model.company', 'user']);

        if ($q = trim($filters['q'] ?? '')) {
            $query->where(function (Builder $builder) use ($q) {
                $builder->where('verdict', 'like', "%{$q}%")
                    ->orWhere('body', 'like', "%{$q}%")
                    ->orWhereHas('tool', fn (Builder $tool) => $tool->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('model', fn (Builder $model) => $model->where('name', 'like', "%{$q}%"));
            });
        }

        if (! empty($filters['type'])) {
            $query->where('review_type', $filters['type']);
        }

        if (! empty($filters['rating'])) {
            $query->where('rating', '>=', (float) $filters['rating']);
        }

        match ($filters['sort'] ?? 'newest') {
            'rating' => $query->orderByDesc('rating')->latest(),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        $reviews = $query->paginate(12)->withQueryString();
        $statsQuery = $this->publicReviews();

        $stats = [
            'reviews' => (clone $statsQuery)->count(),
            'editorial' => (clone $statsQuery)->where('review_type', 'editorial')->count(),
            'community' => (clone $statsQuery)->where('review_type', 'user')->count(),
            'average' => (float) ((clone $statsQuery)->avg('rating') ?? 0),
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

        $review->load(['tool.company', 'tool.category', 'model.company', 'user']);
        $item = $review->model ?: $review->tool;
        abort_unless($item, 404);

        $itemType = $review->model ? 'model' : 'tool';
        if ($itemType === 'model') {
            abort_unless(in_array($item->status, ['active', 'preview'], true), 404);
            $itemReviewStats = Review::published()
                ->where('model_id', $review->model_id)
                ->selectRaw('COUNT(*) as total, AVG(rating) as average')
                ->first();

            $relatedReviews = $this->publicReviews()
                ->with(['model.company', 'user'])
                ->where('id', '!=', $review->id)
                ->where('model_id', $review->model_id)
                ->latest()
                ->take(4)
                ->get();
        } else {
            abort_unless($item->status === 'published', 404);
            $itemReviewStats = Review::published()
                ->where('tool_id', $review->tool_id)
                ->selectRaw('COUNT(*) as total, AVG(rating) as average')
                ->first();

            $relatedReviews = $this->publicReviews()
                ->with(['tool.company', 'user'])
                ->where('id', '!=', $review->id)
                ->where(function (Builder $query) use ($review) {
                    $query->where('tool_id', $review->tool_id)
                        ->orWhereHas('tool', fn (Builder $tool) => $tool->where('category_id', $review->tool?->category_id));
                })
                ->latest()
                ->take(4)
                ->get();
        }

        return view('frontend.reviews.show', compact(
            'review', 'item', 'itemType', 'itemReviewStats', 'relatedReviews'
        ));
    }

    private function publicReviews(): Builder
    {
        return Review::query()
            ->published()
            ->where(function (Builder $query) {
                $query->whereHas('tool', fn (Builder $tool) => $tool->where('status', 'published'))
                    ->orWhereHas('model', fn (Builder $model) => $model->whereIn('status', ['active', 'preview']));
            });
    }
}
