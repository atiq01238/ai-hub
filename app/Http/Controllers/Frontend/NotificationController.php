<?php
namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
class NotificationController extends Controller
{
 public function index(Request $request): View {
  $notifications=AppNotification::where('user_id',$request->user()->id)->latest()->paginate(25);
  $unreadCount=AppNotification::where('user_id',$request->user()->id)->unread()->count();
  return view('frontend.account.notifications',compact('notifications','unreadCount'));
 }
 public function open(Request $request,AppNotification $notification): RedirectResponse {
  abort_unless((int)$notification->user_id===(int)$request->user()->id,403);
  if(!$notification->read_at) $notification->update(['read_at'=>now()]);
  return redirect()->to($this->safe($request,$notification->action_url));
 }
 public function markAllRead(Request $request): RedirectResponse {
  AppNotification::where('user_id',$request->user()->id)->unread()->update(['read_at'=>now()]);
  return back()->with('status','All notifications marked as read.');
 }
 public function destroy(Request $request,AppNotification $notification): RedirectResponse {
  abort_unless((int)$notification->user_id===(int)$request->user()->id,403); $notification->delete(); return back();
 }
 private function safe(Request $request,?string $url): string {
  if(!$url) return route('account.notifications'); $p=parse_url($url); if($p===false) return route('account.notifications');
  if(isset($p['host']) && $p['host']!==$request->getHost()) return route('account.notifications');
  return $url;
 }
}
