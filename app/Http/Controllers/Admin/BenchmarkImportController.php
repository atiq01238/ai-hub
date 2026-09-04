<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\Benchmark;
use App\Models\BenchmarkResult;
use App\Models\Tool;
use App\Services\Imports\ImportPreviewStore;
use App\Services\Imports\SpreadsheetReader;
use App\Services\BenchmarkScoringService;
use App\Services\BenchmarkSemanticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class BenchmarkImportController extends Controller
{
    public function template(){ $p=storage_path('app/import-templates/ai-hub-benchmarks-import.csv'); abort_unless(File::exists($p),404); return response()->download($p,'ai-hub-benchmarks-import.csv'); }
    public function preview(Request $request,SpreadsheetReader $reader,ImportPreviewStore $store){$request->validate(['file'=>['required','file','mimes:csv,txt,xlsx','max:10240']]);try{$rows=$reader->read($request->file('file'));}catch(Throwable$e){return back()->withErrors(['file'=>$e->getMessage()]);}$preview=[];
        foreach($rows as$r){$n=$this->normalize($r);$errors=[];$entity=null;$definitionOnly=$n['entity_type']===''&&$n['entity_name']===''&&$n['score']===null;if($n['benchmark_name']==='')$errors[]='Benchmark name is required.';if(!$definitionOnly){if(!in_array($n['entity_type'],['model','tool'],true))$errors[]='Entity type must be model or tool.';if($n['entity_name']==='')$errors[]='Entity name is required.';if($n['score']===null)$errors[]='Score is required.';elseif($n['score']<0)$errors[]='Score cannot be negative.';}if($n['source_url']!==''&&!filter_var($n['source_url'],FILTER_VALIDATE_URL))$errors[]='Source URL is invalid.';if($n['benchmark_class']!==''&&!in_array($n['benchmark_class'],Benchmark::CLASSES,true))$errors[]='Unknown benchmark class: '.$n['benchmark_class'];
            $existingBenchmark=$n['benchmark_name']!==''?Benchmark::findEquivalent($n['benchmark_name']):null;
            if(!$errors&&$existingBenchmark&&$n['benchmark_class']!==''){
                $existingClass=$existingBenchmark->benchmark_class?:Benchmark::CLASS_UNCLASSIFIED;
                if($existingClass!==Benchmark::CLASS_UNCLASSIFIED&&$existingClass!==$n['benchmark_class'])$errors[]='Benchmark class conflicts with existing definition: '.Benchmark::classLabel($existingClass);
            }
            if(!$errors&&!$definitionOnly){$entity=$n['entity_type']==='model'?AiModel::where('name',$n['entity_name'])->first():Tool::where('name',$n['entity_name'])->first();if(!$entity)$errors[]='Entity not found: '.$n['entity_name'];}
            $preview[]=$n+['definition_only'=>$definitionOnly,'entity_id'=>$entity?->id,'entity_class'=>$entity ? $entity::class : null,'errors'=>$errors,'state'=>$errors?'invalid':'ready'];}
        $token=$store->put('benchmarks',$request->user()->id,$preview);$stats=['total'=>count($preview),'ready'=>collect($preview)->where('state','ready')->count(),'existing'=>0,'invalid'=>collect($preview)->where('state','invalid')->count()];return view('data-import.benchmarks-preview',compact('preview','stats','token'));}
    public function commit(Request $request, ImportPreviewStore $store, BenchmarkScoringService $scoring, BenchmarkSemanticsService $semantics)
    {
        $d=$request->validate(['token'=>['required','string','size:40']]);
        $payload=$store->get($d['token'],$request->user()->id,'benchmarks'); $created=$invalid=$duplicates=0; $touched=[];
        DB::transaction(function() use($payload,$request,$scoring,&$created,&$invalid,&$duplicates,&$touched){
            foreach($payload['rows']??[] as $r){
                if(($r['state']??'')!=='ready'||!empty($r['errors'])){$invalid++;continue;}
                $benchmark=Benchmark::firstOrNewEquivalent($r['benchmark_name']);
                $benchmark->fill([
                    'slug'=>$benchmark->slug ?: $this->uniqueSlug($r['benchmark_name']),
                    'category'=>$r['category']?:'General','entity_scope'=>$r['entity_scope']?:($r['entity_type']?:'model'),
                    'metric_type'=>$r['metric_type']?:'percentage','unit'=>$r['unit']?:'%','min_score'=>$r['min_score']??0,
                    'description'=>$r['description']?:null,'weight'=>$r['weight']??1,'max_score'=>$r['max_score']??100,
                    'version'=>$r['version']?:null,'variant'=>$r['variant']?:null,'higher_is_better'=>$r['higher_is_better'],
                    'official_url'=>$r['benchmark_url']?:null,'methodology_url'=>$r['methodology_url']?:null,'is_active'=>true,
                ]);
                $providedClass=$r['benchmark_class']!==''?$semantics->normalize($r['benchmark_class']):null;
                $currentClass=$benchmark->benchmark_class?:Benchmark::CLASS_UNCLASSIFIED;
                if($providedClass && (!$benchmark->exists || $currentClass===Benchmark::CLASS_UNCLASSIFIED)){
                    $benchmark->benchmark_class=$providedClass;
                } elseif(!$benchmark->exists || $currentClass===Benchmark::CLASS_UNCLASSIFIED) {
                    $inference=$semantics->inferFromMetadata([
                        'name'=>$r['benchmark_name'],'category'=>$r['category'],'description'=>$r['description'],
                        'official_url'=>$r['benchmark_url'],'methodology_url'=>$r['methodology_url'],
                        'source_type'=>$r['source_type'],'source_name'=>$r['source_name'],'source_url'=>$r['source_url'],
                    ]);
                    $benchmark->benchmark_class=$inference['class'];
                }
                $benchmark->save();
                if(!empty($r['definition_only'])){$created++;continue;}
                $fp=$scoring->fingerprint($benchmark->id,$r['entity_class'],$r['entity_id'],$r['tested_at']?:null,$r['source_url']?:null,(float)$r['score']);
                if(BenchmarkResult::where('fingerprint',$fp)->exists()){$duplicates++;continue;}
                $verified=(bool)$r['verified'];
                BenchmarkResult::create([
                    'benchmark_id'=>$benchmark->id,'benchmarkable_type'=>$r['entity_class'],'benchmarkable_id'=>$r['entity_id'],
                    'score'=>$r['score'],'model_version'=>$r['model_version']?:null,'tested_at'=>$r['tested_at']?:null,
                    'source_type'=>$r['source_type']?:'independent','source_name'=>$r['source_name']?:null,'source_url'=>$r['source_url']?:null,
                    'notes'=>$r['notes']?:null,'verified'=>$verified,'status'=>$verified?'verified':'pending',
                    'verified_by'=>$verified?$request->user()?->id:null,'verified_at'=>$verified?now():null,'fingerprint'=>$fp,
                ]);
                $item=$r['entity_class']===AiModel::class?AiModel::find($r['entity_id']):Tool::find($r['entity_id']);
                if($item)$touched[$r['entity_class'].':'.$r['entity_id']]=$item; $created++;
            }
            foreach($touched as $item)$scoring->sync($item);
        });
        $store->forget($d['token']);
        return redirect()->route('admin.benchmarks.results')->with('status',"Benchmark import complete: {$created} imported, {$duplicates} duplicates skipped, {$invalid} invalid skipped.");
    }

    private function normalize(array $r): array
    {
        $num=fn($v,$d=null)=>trim((string)$v)===''?$d:(float)$v;
        $bool=fn($v,$d=false)=>in_array(strtolower(trim((string)$v)),['1','true','yes','y'],true)?true:(trim((string)$v)===''?$d:false);
        return [
            'row_number'=>(int)($r['row_number']??0),'entity_type'=>strtolower(trim((string)($r['entity_type']??''))),
            'entity_name'=>trim((string)($r['entity_name']??'')),'benchmark_name'=>trim((string)($r['benchmark_name']??'')),
            'category'=>trim((string)($r['category']??'')),'benchmark_class'=>str_replace([' ','-'],'_',strtolower(trim((string)($r['benchmark_class']??'')))),'entity_scope'=>strtolower(trim((string)($r['entity_scope']??''))),
            'metric_type'=>trim((string)($r['metric_type']??'percentage')),'unit'=>trim((string)($r['unit']??'%')),
            'min_score'=>$num($r['min_score']??'',0),'description'=>trim((string)($r['description']??'')),'weight'=>$num($r['weight']??'',1),
            'max_score'=>$num($r['max_score']??'',100),'version'=>trim((string)($r['version']??'')),'variant'=>trim((string)($r['variant']??'')),
            'higher_is_better'=>$bool($r['higher_is_better']??'yes',true),'benchmark_url'=>trim((string)($r['benchmark_url']??'')),
            'methodology_url'=>trim((string)($r['methodology_url']??'')),'score'=>$num($r['score']??'',null),
            'model_version'=>trim((string)($r['model_version']??'')),'tested_at'=>trim((string)($r['tested_at']??'')),
            'source_type'=>trim((string)($r['source_type']??'independent')),'source_name'=>trim((string)($r['source_name']??'')),
            'source_url'=>trim((string)($r['source_url']??'')),'notes'=>trim((string)($r['notes']??'')),'verified'=>$bool($r['verified']??'no',false),
        ];
    }
    private function uniqueSlug(string$name):string{$base=Str::slug($name)?:'benchmark';$s=$base;$i=2;while(Benchmark::where('slug',$s)->exists())$s=$base.'-'.$i++;return$s;}
}
