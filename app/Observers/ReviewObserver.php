<?php

namespace App\Observers;

use App\Models\AppNotification;
use App\Models\Review;

class ReviewObserver
{
    public function created(Review $review): void
    {
        if ($review->status === 'pending') {
            AppNotification::broadcast(
                'star',
                'info',
                'Review awaiting approval',
                ($review->tool->name ?? 'A tool') . ' review by ' . ($review->user->name ?? 'a visitor')
            );
        }

        $review->tool?->recalculateRating();
    }

    public function updated(Review $review): void
    {
        $review->tool?->recalculateRating();
    }

    public function deleted(Review $review): void
    {
        $review->tool?->recalculateRating();
    }
}
