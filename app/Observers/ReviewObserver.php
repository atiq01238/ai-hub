<?php
namespace App\Observers;
use App\Models\AppNotification;
use App\Models\NotificationRule;
use App\Models\Review;
use App\Services\NotificationService;
class ReviewObserver
{
 public function __construct(private readonly NotificationService $notifications) {}
 public function created(Review $review): void {
  if($review->status==='pending' && NotificationRule::isEnabled('pending_review')) {
   $item=$review->model?->name ?? $review->tool?->name ?? 'AI item';
   AppNotification::broadcast('star','info','Review awaiting approval',$item.' review by '.($review->user?->name ?? 'a user'),url('/admin/community/reviews'),'pending_review');
  }
  $review->tool?->recalculateRating();
 }
 public function updated(Review $review): void {
  $review->tool?->recalculateRating();
  if(!$review->wasChanged('status') || !$review->user_id) return;
  $status=$review->status;
  $title=match($status){'published'=>'Your review was approved','flagged'=>'Your review needs attention',default=>'Your review status changed'};
  $tone=$status==='published'?'pos':($status==='flagged'?'warn':'info');
  $this->notifications->user($review->user_id,$title,$review->moderation_note ?: 'Review status: '.ucfirst($status),$this->notifications->reviewUrl($review),'star',$tone,'review_moderation');
 }
 public function deleted(Review $review): void { $review->tool?->recalculateRating(); }
}
