<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use App\Models\PricingSource;
use App\Models\Tool;
use App\Services\Imports\ImportPreviewStore;
use App\Services\Imports\SpreadsheetReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Throwable;

class PricingImportController extends Controller
{
    public function template(){ $p=storage_path('app/import-templates/ai-hub-pricing-import.csv'); abort_unless(File::exists($p),404); return response()->download($p,'ai-hub-pricing-import.csv'); }
    public function preview(Request $request, SpreadsheetReader $reader, ImportPreviewStore $store){
        $request->validate(['file'=>['required','file','mimes:csv,txt,xlsx','max:10240']]);
        try{$rows=$reader->read($request->file('file'));}catch(Throwable $e){return back()->withErrors(['file'=>$e->getMessage()]);}
        $tools=Tool::get(['id','name'])->keyBy(fn($t)=>mb_strtolower(trim($t->name))); $preview=[];$seen=[];
        foreach($rows as $r){$n=$this->normalize($r);$errors=[];$tool=$tools->get(mb_strtolower($n['tool']));
            if($n['tool']===''||!$tool)$errors[]='Tool not found in database: '.$n['tool']; if($n['plan_name']==='')$errors[]='Plan name is required.';
            foreach(['monthly_price','yearly_price'] as $f)if($n[$f]!==null&&$n[$f]<0)$errors[]='Price cannot be negative.';
            if($n['source_url']!==''&&!filter_var($n['source_url'],FILTER_VALIDATE_URL))$errors[]='Source URL is invalid.';
            if(!in_array($n['metric'],['monthly_price','yearly_price','api_price_label'],true))$errors[]='Metric is invalid.';
            $key=mb_strtolower($n['tool'].'|'.$n['plan_name']); if(isset($seen[$key]))$errors[]='Duplicate pricing plan inside file.';$seen[$key]=true;
            $existing=$tool?PricingPlan::where('tool_id',$tool->id)->where('plan_name',$n['plan_name'])->first():null;
            $preview[]=$n+['tool_id'=>$tool?->id,'tool_match'=>$tool?->name,'existing_id'=>$existing?->id,'errors'=>array_values(array_unique($errors)),'state'=>$errors?'invalid':($existing?'existing':'ready')];
        }
        $token=$store->put('pricing',$request->user()->id,$preview);$stats=$this->stats($preview);return view('data-import.pricing-preview',compact('preview','stats','token'));
    }
    public function commit(Request $request, ImportPreviewStore $store){$d=$request->validate(['token'=>['required','string','size:40'],'existing_action'=>['required',Rule::in(['skip','update'])]]);$payload=$store->get($d['token'],$request->user()->id,'pricing');$c=$u=$s=$i=0;
        DB::transaction(function()use($payload,$d,&$c,&$u,&$s,&$i){foreach($payload['rows']??[] as $r){if(($r['state']??'')==='invalid'||!empty($r['errors'])){$i++;continue;}
            $vals=['tool_id'=>$r['tool_id'],'plan_name'=>$r['plan_name'],'monthly_price'=>$r['monthly_price'],'yearly_price'=>$r['yearly_price'],'api_price_label'=>$r['api_price_label']?:null,'credits'=>$r['credits']?:null,'limits'=>$r['limits']?:null];
            $plan=PricingPlan::where('tool_id',$r['tool_id'])->where('plan_name',$r['plan_name'])->first(); if($plan){if($d['existing_action']==='skip'){$s++;continue;}$plan->update($vals);$u++;}else{$plan=PricingPlan::create($vals);$c++;}
            if(!empty($r['source_url'])) PricingSource::updateOrCreate(['pricing_plan_id'=>$plan->id,'metric'=>$r['metric'],'source_url'=>$r['source_url']],['source_name'=>$r['source_name']?:'Official pricing','source_type'=>'auto','currency'=>$r['currency']?:'USD','unit'=>$r['unit']?:null,'enabled'=>true]);
        }});$store->forget($d['token']);return redirect()->route('admin.pricing.index')->with('status',"Pricing import complete: {$c} created, {$u} updated, {$s} existing skipped, {$i} invalid skipped.");}
    private function normalize(array$r):array{$num=fn($v)=>trim((string)$v)===''?null:(float)$v;return['row_number'=>(int)($r['row_number']??0),'tool'=>trim((string)($r['tool']??'')),'plan_name'=>trim((string)($r['plan_name']??'')),'monthly_price'=>$num($r['monthly_price']??''),'yearly_price'=>$num($r['yearly_price']??''),'api_price_label'=>trim((string)($r['api_price_label']??'')),'credits'=>trim((string)($r['credits']??'')),'limits'=>trim((string)($r['limits']??'')),'metric'=>trim((string)($r['metric']??'monthly_price'))?:'monthly_price','source_name'=>trim((string)($r['source_name']??'')),'source_url'=>trim((string)($r['source_url']??'')),'currency'=>strtoupper(trim((string)($r['currency']??'USD')))?:'USD','unit'=>trim((string)($r['unit']??''))];}
    private function stats(array$r):array{return['total'=>count($r),'ready'=>collect($r)->where('state','ready')->count(),'existing'=>collect($r)->where('state','existing')->count(),'invalid'=>collect($r)->where('state','invalid')->count()];}
}
