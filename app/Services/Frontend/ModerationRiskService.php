<?php
namespace App\Services\Frontend;
use App\Models\CommunityComment;
class ModerationRiskService {
 public function score(CommunityComment $c): array {
  $score=0;$reasons=[];$body=mb_strtolower($c->body);
  if($c->reports_count??0){$score+=min(45,($c->reports_count*15));$reasons[]='reports';}
  if(preg_match('/https?:\/\/|www\./u',$body)){$score+=25;$reasons[]='external link';}
  if(preg_match('/(.)\1{7,}/u',$body)){$score+=20;$reasons[]='repetition';}
  if($c->user?->community_trust_level==='restricted'){$score+=35;$reasons[]='restricted user';}
  if($c->user?->community_trust_level==='trusted')$score-=15;
  return ['score'=>max(0,min(100,$score)),'level'=>$score>=60?'high':($score>=30?'medium':'low'),'reasons'=>$reasons];
 }
}