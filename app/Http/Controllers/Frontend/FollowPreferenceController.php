<?php
namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use App\Models\UserInteraction;
use Illuminate\Http\Request;
class FollowPreferenceController extends Controller {
 public function update(Request $r,UserInteraction $interaction){
  abort_unless($interaction->user_id===$r->user()->id && $interaction->action==='follow',403);
  $d=$r->validate([
   'alerts'=>'nullable|array|max:4',
   'alerts.*'=>'in:news,pricing,benchmark,major_update',
  ]);
  $meta=$interaction->metadata??[];
  $meta['alerts']=array_values(array_unique($d['alerts']??[]));
  $interaction->update(['metadata'=>$meta]);
  return back()->with('status','Follow alert preferences updated.');
 }
}