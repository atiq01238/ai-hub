<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SavedSearch extends Model {
 protected $fillable=['user_id','query','type','filters']; protected $casts=['filters'=>'array'];
}