<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SearchEvent extends Model {
 protected $fillable=['user_id','query','type','result_count','clicked','clicked_type','clicked_id','session_key'];
 protected $casts=['clicked'=>'boolean'];
}