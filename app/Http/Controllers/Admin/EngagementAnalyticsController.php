<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{CommunityComment,Review,SavedItem,SearchEvent,User,UserComparison,UserInteraction};
use Illuminate\Http\Request;
class EngagementAnalyticsController extends Controller {
 public function index(Request $r){
  $days=max(7,min(365,(int)$r->query('days',30)));$since=now()->subDays($days);
  $metrics=[
   'new_users'=>User::where('created_at','>=',$since)->count(),
   'saves'=>SavedItem::where('created_at','>=',$since)->count(),
   'follows'=>UserInteraction::where('action','follow')->where('created_at','>=',$since)->count(),
   'reviews'=>Review::where('review_type','user')->where('created_at','>=',$since)->count(),
   'comments'=>CommunityComment::where('created_at','>=',$since)->count(),
   'comparisons'=>UserComparison::where('created_at','>=',$since)->count(),
   'searches'=>SearchEvent::where('created_at','>=',$since)->count(),
   'zero_searches'=>SearchEvent::where('created_at','>=',$since)->where('result_count',0)->count(),
  ];
  $topSearches=SearchEvent::selectRaw('query, COUNT(*) total, SUM(result_count = 0) zero_count')->where('created_at','>=',$since)->groupBy('query')->orderByDesc('total')->limit(15)->get();
  $topFollowed=UserInteraction::selectRaw('target_type,target_id,COUNT(*) total')->where('action','follow')->where('created_at','>=',$since)->groupBy('target_type','target_id')->orderByDesc('total')->limit(15)->get();
  $daily=UserInteraction::selectRaw('DATE(created_at) day,COUNT(*) total')->where('created_at','>=',$since)->groupBy('day')->orderBy('day')->get();
  return view('analytics.engagement',compact('days','metrics','topSearches','topFollowed','daily'));
 }
}