<?php
namespace App\Services;
use App\Models\BenchmarkResult;
use Illuminate\Support\Collection;
class ComparisonIntelligenceService {
 public function build(Collection $items,string $type): array {
  $benchmarkMatrix=[];$benchmarkMeta=[];
  foreach($items as $item){
   $rows=$item->benchmarkResults()->with('benchmark')->where('verified',true)->where('status','verified')->orderByDesc('tested_at')->orderByDesc('id')->get()->unique('benchmark_id');
   foreach($rows as $r){if(!$r->benchmark)continue;$key=$r->benchmark->slug;$benchmarkMeta[$key]=$r->benchmark;$benchmarkMatrix[$key][$item->id]=$r;}
  }
  $wins=[]; foreach($items as $item)$wins[$item->id]=0;
  foreach($benchmarkMatrix as $key=>$scores){$b=$benchmarkMeta[$key];$eligible=collect($scores);if($eligible->count()<2)continue;$best=$b->higher_is_better?$eligible->sortByDesc('score')->first():$eligible->sortBy('score')->first();$wins[$best->benchmarkable_id]++;}
  $pricing=[];
  foreach($items as $item){
   if($type==='model'){$pricing[$item->id]=['input'=>$item->input_price_per_million,'output'=>$item->output_price_per_million];}
   else {$plans=$item->pricingPlans()->orderByRaw('monthly_price is null')->orderBy('monthly_price')->get();$pricing[$item->id]=['plans'=>$plans,'starting'=>$plans->whereNotNull('monthly_price')->min('monthly_price')];}
  }
  $overall=$items->sortByDesc(fn($i)=>((float)($i->benchmark_score??0))*0.8 + (($wins[$i->id]??0)*5))->first();
  $valueWinner=$items->sortBy(function($i)use($type,$pricing){$score=max((float)($i->benchmark_score??0),1);$cost=$type==='model'?((float)($pricing[$i->id]['input']??0)+(float)($pricing[$i->id]['output']??0)):(float)($pricing[$i->id]['starting']??999999);return $cost>0?$cost/$score:0;})->first();
  return compact('benchmarkMatrix','benchmarkMeta','wins','pricing','overall','valueWinner');
 }
}
