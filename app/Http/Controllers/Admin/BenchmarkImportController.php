<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Benchmark;
use App\Models\BenchmarkResult;
use App\Models\Tool;
use App\Services\Imports\ImportPreviewStore;
use App\Services\Imports\SpreadsheetReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class BenchmarkImportController extends Controller
{
    public function template(){ $p=storage_path('app/import-templates/ai-hub-benchmarks-import.csv'); abort_unless(File::exists($p),404); return response()->download($p,'ai-hub-benchmarks-import.csv'); }
    public function preview(Request $request,SpreadsheetReader $reader,ImportPreviewStore $store){$request->validate(['file'=>['required','file','mimes:csv,txt,xlsx','max:10240']]);try{$rows=$reader->read($request->file('file'));}catch(Throwable$e){return back()->withErrors(['file'=>$e->getMessage()]);}$preview=[];
        foreach($rows as$r){$n=$this->normalize($r);$errors=[];$entity=null;$definitionOnly=$n['entity_type']===''&&$n['entity_name']===''&&$n['score']===null;if($n['benchmark_name']==='')$errors[]='Benchmark name is required.';if(!$definitionOnly){if(!in_array($n['entity_type'],['model','tool'],true))$errors[]='Entity type must be model or tool.';if($n['entity_name']==='')$errors[]='Entity name is required.';if($n['score']===null)$errors[]='Score is required.';elseif($n['score']<0)$errors[]='Score cannot be negative.';}if($n['source_url']!==''&&!filter_var($n['source_url'],FILTER_VALIDATE_URL))$errors[]='Source URL is invalid.';
            if(!$errors&&!$definitionOnly){$entity=$n['entity_type']==='model'?AiModel::where('name',$n['entity_name'])->first():Tool::where('name',$n['entity_name'])->first();if(!$entity)$errors[]='Entity not found: '.$n['entity_name'];}
            $preview[]=$n+['definition_only'=>$definitionOnly,'entity_id'=>$entity?->id,'entity_class'=>$entity ? $entity::class : null,'errors'=>$errors,'state'=>$errors?'invalid':'ready'];}
        $token=$store->put('benchmarks',$request->user()->id,$preview);$stats=['total'=>count($preview),'ready'=>collect($preview)->where('state','ready')->count(),'existing'=>0,'invalid'=>collect($preview)->where('state','invalid')->count()];return view('data-import.benchmarks-preview',compact('preview','stats','token'));}
    public function commit(Request$request,ImportPreviewStore$store){$d=$request->validate(['token'=>['required','string','size:40']]);$payload=$store->get($d['token'],$request->user()->id,'benchmarks');$created=$invalid=0;DB::transaction(function()use($payload,&$created,&$invalid){foreach($payload['rows']??[]as$r){if(($r['state']??'')!=='ready'||!empty($r['errors'])){$invalid++;continue;}$benchmark=Benchmark::firstOrCreate(['name'=>$r['benchmark_name']],['slug'=>$this->uniqueSlug($r['benchmark_name']),'category'=>$r['category']?:'General','description'=>$r['description']?:null,'weight'=>$r['weight']??1,'max_score'=>$r['max_score']??100,'higher_is_better'=>$r['higher_is_better'],'official_url'=>$r['benchmark_url']?:null,'is_active'=>true]);
            if(!empty($r['definition_only'])){$created++;continue;}
            BenchmarkResult::create(['benchmark_id'=>$benchmark->id,'benchmarkable_type'=>$r['entity_class'],'benchmarkable_id'=>$r['entity_id'],'score'=>$r['score'],'tested_at'=>$r['tested_at']?:null,'source_name'=>$r['source_name']?:null,'source_url'=>$r['source_url']?:null,'notes'=>$r['notes']?:null,'verified'=>$r['verified']]);$item=$r['entity_class']===AiModel::class?AiModel::find($r['entity_id']):Tool::find($r['entity_id']);if($item){$b=$item->benchmarks??[];$b[$benchmark->name]=(float)$r['score'];$item->benchmarks=$b;$item->benchmark_score=$this->composite($item);$item->save();}$created++;}});$store->forget($d['token']);return redirect()->route('admin.benchmarks.results')->with('status',"Benchmark import complete: {$created} results imported, {$invalid} invalid skipped.");}
    private function normalize(array$r):array{$num=fn($v,$d=null)=>trim((string)$v)===''?$d:(float)$v;$bool=fn($v,$d=false)=>in_array(strtolower(trim((string)$v)),['1','true','yes','y'],true)?true:(trim((string)$v)===''?$d:false);return['row_number'=>(int)($r['row_number']??0),'entity_type'=>strtolower(trim((string)($r['entity_type']??''))),'entity_name'=>trim((string)($r['entity_name']??'')),'benchmark_name'=>trim((string)($r['benchmark_name']??'')),'category'=>trim((string)($r['category']??'')),'description'=>trim((string)($r['description']??'')),'weight'=>$num($r['weight']??'',1),'max_score'=>$num($r['max_score']??'',100),'higher_is_better'=>$bool($r['higher_is_better']??'yes',true),'benchmark_url'=>trim((string)($r['benchmark_url']??'')),'score'=>$num($r['score']??'',null),'tested_at'=>trim((string)($r['tested_at']??'')),'source_name'=>trim((string)($r['source_name']??'')),'source_url'=>trim((string)($r['source_url']??'')),'notes'=>trim((string)($r['notes']??'')),'verified'=>$bool($r['verified']??'no',false)];}
    private function uniqueSlug(string$name):string{$base=Str::slug($name)?:'benchmark';$s=$base;$i=2;while(Benchmark::where('slug',$s)->exists())$s=$base.'-'.$i++;return$s;}
    private function composite($item):float{$latest=BenchmarkResult::with('benchmark')->where('benchmarkable_type',$item::class)->where('benchmarkable_id',$item->id)->orderByDesc('tested_at')->orderByDesc('id')->get()->unique('benchmark_id');if($latest->isEmpty())return 0;$weighted=$weights=0;foreach($latest as$result){$w=max((float)($result->benchmark->weight??1),.01);$max=max((float)($result->benchmark->max_score??100),.01);$weighted+=min(100,max(0,((float)$result->score/$max)*100))*$w;$weights+=$w;}return round($weighted/$weights,1);}
}
