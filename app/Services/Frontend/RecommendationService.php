<?php
namespace App\Services\Frontend;
use App\Models\{AiModel,Company,SavedItem,Tool,User,UserInteraction};
use Illuminate\Support\Collection;
class RecommendationService {
 public function for(User $user,int $limit=8): array {
  $pref=$user->preference; $interests=collect($pref?->interests??[])->map(fn($x)=>mb_strtolower($x));
  $savedToolIds=SavedItem::where('user_id',$user->id)->where('saveable_type',Tool::class)->pluck('saveable_id');
  $followToolIds=UserInteraction::where('user_id',$user->id)->where('action','follow')->where('target_type','tool')->pluck('target_id');
  $seedIds=$savedToolIds->merge($followToolIds)->unique();
  $categoryIds=Tool::whereIn('id',$seedIds)->pluck('category_id')->filter()->unique();
  $exclude=$seedIds;
  $tools=Tool::with(['company','category'])->where('status','published')->whereNotIn('id',$exclude)
   ->when($categoryIds->isNotEmpty()||$interests->isNotEmpty(),function($q)use($categoryIds,$interests){$q->where(function($x)use($categoryIds,$interests){if($categoryIds->isNotEmpty())$x->whereIn('category_id',$categoryIds);foreach($interests as $term)$x->orWhere('name','like','%'.$term.'%')->orWhere('short_description','like','%'.$term.'%');});})
   ->orderByDesc('rating')->orderByDesc('popularity')->take($limit)->get();
  if($tools->count()<4)$tools=$tools->merge(Tool::with('company')->where('status','published')->whereNotIn('id',$exclude->merge($tools->pluck('id')))->orderByDesc('popularity')->take($limit-$tools->count())->get());
  $followCompanyIds=UserInteraction::where('user_id',$user->id)->where('action','follow')->where('target_type','company')->pluck('target_id');
  $models=AiModel::with('company')->whereIn('status',['active','preview'])->when($followCompanyIds->isNotEmpty(),fn($q)=>$q->whereIn('company_id',$followCompanyIds))->orderByDesc('benchmark_score')->take(4)->get();
  return ['tools'=>$tools->take($limit),'models'=>$models,'reason'=>$seedIds->isNotEmpty()?'Based on your saves and follows':($interests->isNotEmpty()?'Based on your interests':'Popular on AI Hub')];
 }
}