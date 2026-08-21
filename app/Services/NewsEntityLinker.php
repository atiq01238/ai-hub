<?php
namespace App\Services;
use App\Models\{AiModel,Company,NewsItem,Tool};
use Illuminate\Support\Str;
class NewsEntityLinker {
 public function link(NewsItem $n): array {
  $text=Str::lower(strip_tags(implode(' ',array_filter([$n->headline,$n->summary,$n->ai_summary,$n->why_it_matters]))));
  $match=fn($name)=>mb_strlen(trim($name))>=3 && preg_match('/(?<![\pL\pN])'.preg_quote(Str::lower($name),'/').'(?![\pL\pN])/u',$text);
  $tools=Tool::select('id','name')->get()->filter(fn($x)=>$match($x->name))->pluck('id');
  $models=AiModel::select('id','name','company_id')->get()->filter(fn($x)=>$match($x->name))->pluck('id');
  $companies=Company::select('id','name')->get()->filter(fn($x)=>$match($x->name))->pluck('id');
  $n->relatedToolTerms()->sync($tools); $n->relatedModelTerms()->sync($models);
  if(!$n->company_id && $companies->count()===1) $n->forceFill(['company_id'=>$companies->first()])->saveQuietly();
  $n->forceFill(['related_tools'=>Tool::whereIn('id',$tools)->pluck('name')->values()->all()])->saveQuietly();
  return ['tools'=>$tools->count(),'models'=>$models->count(),'companies'=>$companies->count()];
 }
}
