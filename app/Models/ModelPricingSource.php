<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ModelPricingSource extends Model {
    protected $fillable=['ai_model_id','metric','source_name','source_url','source_type','extraction_rule','currency','unit','enabled','last_checked_at','last_check_status','last_check_message','last_detected_value'];
    protected $casts=['enabled'=>'boolean','last_checked_at'=>'datetime'];
    public function model(){ return $this->belongsTo(AiModel::class,'ai_model_id'); }
}
