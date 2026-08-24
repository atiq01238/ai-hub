<?php
namespace App\Http\Controllers\Frontend;
use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use Illuminate\Http\Request;
class OnboardingController extends Controller {
 public function show(Request $r){return view('frontend.account.onboarding',['preference'=>$r->user()->preference]);}
 public function store(Request $r){
  $d=$r->validate(['interests'=>'required|array|min:1|max:8','interests.*'=>'in:Coding,Writing,Research,Image,Video,Audio,Productivity,Marketing,Data,Automation','use_cases'=>'nullable|array|max:6','use_cases.*'=>'in:Personal,Freelance,Startup,Business,Education,Development,Content','experience_level'=>'required|in:beginner,intermediate,advanced']);
  UserPreference::updateOrCreate(['user_id'=>$r->user()->id],$d+['onboarding_completed'=>true,'onboarding_completed_at'=>now()]);
  return redirect()->route('account.dashboard')->with('status','Your AI Orbit is now personalized.');
 }
}