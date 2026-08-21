<?php
namespace App\Services;
use App\Models\NewsItem;
class NewsIntelligenceService {
 public function trendingScore(NewsItem $n): float {
  $age=max(0,optional($n->published_at)->diffInHours(now())??720);$fresh=100*exp(-$age/168);
  $authority=(int)($n->newsSource?->authority_score??55);$verified=$n->verification_status==='verified'?100:45;
  $unique=($n->duplicate_of_id||$n->duplicate_status==='duplicate')?0:100;
  return round(((int)$n->importance*.35)+($fresh*.25)+($authority*.20)+($verified*.15)+($unique*.05),2);
 }
 public function refresh(NewsItem $n): void {$n->loadMissing('newsSource');$n->forceFill(['trending_score'=>$this->trendingScore($n)])->saveQuietly();}
}
