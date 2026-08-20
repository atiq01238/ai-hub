<?php
namespace App\Services;
use App\Models\AppNotification;
use App\Models\CommunityComment;
use App\Models\Review;
class NotificationService
{
 public function admin(string $title,?string $description=null,?string $url=null,string $icon='bell',string $tone='info',?string $type=null): void {
  AppNotification::broadcast($icon,$tone,$title,$description,$url,$type);
 }
 public function user(int $userId,string $title,?string $description=null,?string $url=null,string $icon='bell',string $tone='info',?string $type=null): void {
  AppNotification::sendTo($userId,$icon,$tone,$title,$description,$url,$type);
 }
 public function commentUrl(CommunityComment $c): string {
  return url('/account/comments');
 }
 public function reviewUrl(Review $r): string {
  if($r->tool) return url('/ai-tools/'.$r->tool->slug).'#community-reviews';
  if($r->model) return url('/ai-models/'.$r->model->slug).'#community-reviews';
  return url('/account/reviews');
 }
}
