<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\ModelPricingHistory;
use App\Models\ModelPricingSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ModelPricingController extends Controller
{
    private const METRICS=['input_price_per_million','output_price_per_million'];

    public function index(Request $request)
    {
        $models=AiModel::with(['company','pricingSources'])->whereIn('status',['active','preview'])
            ->when($request->filled('q'), fn($q)=>$q->where('name','like','%'.$request->q.'%'))
            ->orderBy('name')->paginate(30)->withQueryString();
        return view('pricing.model-index',compact('models'));
    }

    public function sources(AiModel $model)
    {
        $model->load(['company','pricingSources','pricingHistory'=>fn($q)=>$q->latest()->limit(20)]);
        return view('pricing.model-sources',compact('model'));
    }

    public function storeSource(Request $request, AiModel $model)
    {
        $data=$request->validate([
            'metric'=>['required','in:'.implode(',',self::METRICS)],
            'source_name'=>['nullable','string','max:120'],'source_url'=>['required','url','max:2000'],
            'source_type'=>['required','in:auto,regex,json_path'],'extraction_rule'=>['nullable','string','max:4000'],
            'currency'=>['required','string','max:10'],'unit'=>['required','string','max:80'],
        ]);
        $model->pricingSources()->create($data+['enabled'=>true]);
        return back()->with('status','Official model pricing source added.');
    }

    public function verify(AiModel $model, ModelPricingSource $source)
    {
        abort_unless($source->ai_model_id===$model->id,404);
        try {
            $response=Http::timeout(18)->withHeaders(['User-Agent'=>'AI-Hub-Pricing-Monitor/2.0'])->get($source->source_url);
            abort_unless($response->successful(),422,'Pricing source request failed.');
            $detected=$this->extract($response->body(),$response->json(),$source);
            $current=$model->{$source->metric};
            $source->update(['last_checked_at'=>now(),'last_check_status'=>'ok','last_check_message'=>null,'last_detected_value'=>$detected]);
            if ($current===null || abs((float)$current-(float)$detected)>0.000001) {
                return back()->with('pricing_candidate',['source_id'=>$source->id,'metric'=>$source->metric,'current'=>$current,'detected'=>$detected]);
            }
            return back()->with('status','Verified: official source matches the live model price.');
        } catch (\Throwable $e) {
            $source->update(['last_checked_at'=>now(),'last_check_status'=>'failed','last_check_message'=>$e->getMessage()]);
            return back()->with('error','Verification failed: '.$e->getMessage());
        }
    }

    public function approve(Request $request, AiModel $model, ModelPricingSource $source)
    {
        abort_unless($source->ai_model_id===$model->id,404);
        $data=$request->validate(['detected_value'=>['required','numeric','min:0']]);
        $old=$model->{$source->metric}; $new=(float)$data['detected_value'];
        $model->forceFill([$source->metric=>$new])->saveQuietly();
        ModelPricingHistory::create([
            'ai_model_id'=>$model->id,'metric'=>$source->metric,'old_value'=>$old,'new_value'=>$new,
            'currency'=>$source->currency,'unit'=>$source->unit,'source_url'=>$source->source_url,
            'change_type'=>$old===null?'new_price':($new>=(float)$old?'increase':'decrease'),'verified_at'=>now(),
        ]);
        $source->update(['last_checked_at'=>now(),'last_check_status'=>'approved','last_detected_value'=>$new]);
        return back()->with('status','Verified model API price approved and history recorded.');
    }

    public function destroySource(AiModel $model, ModelPricingSource $source)
    {
        abort_unless($source->ai_model_id===$model->id,404); $source->delete();
        return back()->with('status','Model pricing source removed.');
    }

    private function extract(string $body, mixed $json, ModelPricingSource $source): string
    {
        if ($source->source_type==='json_path') {
            $v=data_get($json,$source->extraction_rule); if(!is_scalar($v)) throw new \RuntimeException('JSON path did not return a scalar value.');
            return preg_replace('/[^0-9.]/','',(string)$v);
        }
        if ($source->source_type==='regex') {
            if(!$source->extraction_rule || @preg_match($source->extraction_rule,$body,$m)!==1) throw new \RuntimeException('Regex did not find a value.');
            return preg_replace('/[^0-9.]/','',(string)($m['price']??$m[1]??$m[0]));
        }
        $text=preg_replace('/\s+/',' ',strip_tags($body));
        if(preg_match('/\$?\s*([0-9]+(?:\.[0-9]+)?)\s*(?:\/|per)\s*(?:1M|million)/i',$text,$m)) return $m[1];
        throw new \RuntimeException('Automatic extraction was not confident. Configure Regex or JSON Path.');
    }
}
