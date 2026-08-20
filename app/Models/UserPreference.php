<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class UserPreference extends Model {
 protected $fillable=['user_id','interests','use_cases','experience_level','onboarding_completed','onboarding_completed_at'];
 protected $casts=['interests'=>'array','use_cases'=>'array','onboarding_completed'=>'boolean','onboarding_completed_at'=>'datetime'];
 public function user(){return $this->belongsTo(User::class);}
}