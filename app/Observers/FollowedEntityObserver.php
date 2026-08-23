<?php
namespace App\Observers;
use App\Models\AppNotification;
use App\Models\UserInteraction;
use Illuminate\Database\Eloquent\Model;
use App\Jobs\FanOutIntelligenceEmail;
class FollowedEntityObserver {
 public function updated(Model $model): void {
  $type=match(true){$model instanceof \App\Models\Tool=>'tool',$model instanceof \App\Models\AiModel=>'model',$model instanceof \App\Models\Company=>'company',default=>null}; if(!$type)return;
  $changes=collect($model->getChanges())->keys()->reject(fn($k)=>$k==='updated_at'); if($changes->isEmpty())return;
  $event=$changes->contains(fn($k)=>str_contains($k,'price')||str_contains($k,'pricing'))?'pricing':($changes->contains(fn($k)=>str_contains($k,'benchmark'))?'benchmark':'major_update');
  $name=$model->name??ucfirst($type); $url=match($type){'tool'=>url('/ai-tools/'.$model->slug),'model'=>url('/ai-models/'.$model->slug),'company'=>url('/companies/'.$model->slug)};
  UserInteraction::where('action','follow')->where('target_type',$type)->where('target_id',$model->getKey())->get()->each(function($follow)use($name,$type,$url,$event){
   $alerts=$follow->metadata['alerts']??['news','pricing','benchmark','major_update']; if(!in_array($event,$alerts,true))return;
   AppNotification::sendTo((int)$follow->user_id,'bell-ring','info',$name.' was updated','A '.ucfirst($type).' you follow has a '.str_replace('_',' ',$event).' update.',$url,'follow_'.$event);
  });
  $publicationTransition = ($model instanceof \App\Models\Tool && $model->wasChanged('status') && $model->status === 'published')
   || ($model instanceof \App\Models\AiModel && $model->wasChanged('status') && $model->status === 'active');
  if(! $publicationTransition) FanOutIntelligenceEmail::dispatch('followed_update',(int)$model->getKey(),$type,$event);
 }
}