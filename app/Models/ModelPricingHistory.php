<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ModelPricingHistory extends Model {
    protected $table='model_pricing_history';
    protected $fillable=['ai_model_id','metric','old_value','new_value','currency','unit','source_url','change_type','verified_at'];
    protected $casts=['old_value'=>'decimal:6','new_value'=>'decimal:6','verified_at'=>'datetime'];
    public function model(){ return $this->belongsTo(AiModel::class,'ai_model_id'); }
}
