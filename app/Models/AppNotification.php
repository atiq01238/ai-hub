<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AppNotification extends Model
{
 protected $table='notifications';
 protected $fillable=['user_id','icon','tone','type','title','description','action_url','read_at'];
 protected $casts=['read_at'=>'datetime'];
 public function scopeUnread($q){ return $q->whereNull('read_at'); }
 public static function broadcast(string $icon,string $tone,string $title,?string $description=null,?string $actionUrl=null,?string $type=null): void {
  static::create(['user_id'=>null,'icon'=>$icon,'tone'=>$tone,'type'=>$type,'title'=>$title,'description'=>$description,'action_url'=>$actionUrl]);
 }
 public static function sendTo(int $userId,string $icon,string $tone,string $title,?string $description=null,?string $actionUrl=null,?string $type=null): void {
  static::create(['user_id'=>$userId,'icon'=>$icon,'tone'=>$tone,'type'=>$type,'title'=>$title,'description'=>$description,'action_url'=>$actionUrl]);
 }
}
