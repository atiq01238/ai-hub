<?php
namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\Benchmark;
use App\Models\BenchmarkResult;
use App\Services\BenchmarkScoringService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportVerifiedBenchmarks extends Command
{
    protected $signature = 'benchmarks:import-verified {--dry-run : Validate exact catalog matches without writing} {--dataset=v1-2026-08-21 : Verified dataset version (v1-2026-08-21 or v2-2026-08-21)}';
    protected $description = 'Import the bundled primary-source verified benchmark dataset safely.';

    public function handle(BenchmarkScoringService $scoring): int
    {
        $dataset=(string)$this->option('dataset');
        $files=[
            'v1-2026-08-21'=>'verified-model-benchmarks-v1-2026-08-21.csv',
            'v2-2026-08-21'=>'verified-model-benchmarks-v2-2026-08-21.csv',
        ];
        if (!isset($files[$dataset])) {
            $this->error('Refusing import: unsupported/stale dataset. Supported: '.implode(', ',array_keys($files)));
            return self::FAILURE;
        }
        $file=storage_path('app/import-templates/'.$files[$dataset]);
        if(!is_file($file)){ $this->error('Verified benchmark dataset is missing.'); return self::FAILURE; }
        $rows=$this->csv($file); $missing=[]; $invalid=[];
        foreach($rows as $i=>$r){
            if(($r['entity_type']??'')!=='model') $invalid[]='row '.($i+2).' entity_type';
            if(!AiModel::whereRaw('LOWER(name)=?',[mb_strtolower($r['entity_name'])])->exists()) $missing[]=$r['entity_name'];
            if(!is_numeric($r['score']??null)) $invalid[]='row '.($i+2).' score';
            if(empty($r['source_url']) || !filter_var($r['source_url'],FILTER_VALIDATE_URL)) $invalid[]='row '.($i+2).' source_url';
        }
        $missing=array_values(array_unique($missing));
        $this->info('Verified benchmark dataset '.$dataset.': '.count($rows).' model results across '.count(array_unique(array_column($rows,'benchmark_name'))).' benchmarks.');
        if($missing) $this->warn('Models not found (will skip): '.implode(', ',$missing));
        if($invalid){ $this->error('Dataset validation failed: '.implode(', ',$invalid)); return self::FAILURE; }
        if($this->option('dry-run')){ $this->info('Dry run complete. No database changes made.'); return self::SUCCESS; }

        $created=0;$existing=0;$touched=[];
        DB::transaction(function() use($rows,$scoring,&$created,&$existing,&$touched){
            foreach($rows as $r){
                $model=AiModel::whereRaw('LOWER(name)=?',[mb_strtolower($r['entity_name'])])->first(); if(!$model) continue;
                $benchmark=Benchmark::updateOrCreate(['name'=>$r['benchmark_name']],[
                    'slug'=>Str::slug($r['benchmark_name']),'category'=>$r['category'] ?: 'General','benchmark_class'=>Benchmark::CLASS_TECHNICAL,'entity_scope'=>'model',
                    'metric_type'=>$r['metric_type'] ?: 'percentage','unit'=>$r['unit'] ?: '%','min_score'=>(float)($r['min_score'] ?: 0),
                    'max_score'=>(float)($r['max_score'] ?: 100),'higher_is_better'=>$this->bool($r['higher_is_better']),
                    'version'=>$r['version'] ?: null,'variant'=>$r['variant'] ?: null,'official_url'=>$r['methodology_url'] ?: $r['source_url'],
                    'methodology_url'=>$r['methodology_url'] ?: null,'weight'=>$this->weight($r['benchmark_name']),'is_active'=>true,
                ]);
                $fp=$scoring->fingerprint($benchmark->id,$model::class,$model->id,$r['tested_at'] ?: null,$r['source_url'],(float)$r['score']);
                if(BenchmarkResult::where('fingerprint',$fp)->exists()){ $existing++; $touched[$model->id]=$model; continue; }
                BenchmarkResult::create([
                    'benchmark_id'=>$benchmark->id,'benchmarkable_type'=>$model::class,'benchmarkable_id'=>$model->id,'score'=>(float)$r['score'],
                    'model_version'=>$r['model_version'] ?: null,'tested_at'=>$r['tested_at'] ?: null,'source_type'=>$r['source_type'] ?: 'official',
                    'source_name'=>$r['source_name'] ?: null,'source_url'=>$r['source_url'],'notes'=>$r['notes'] ?: null,
                    'status'=>'verified','verified'=>true,'verified_at'=>now(),'fingerprint'=>$fp,
                ]);
                $created++;$touched[$model->id]=$model;
            }
            foreach($touched as $model) $scoring->sync($model->fresh());
        });
        $this->info("Imported {$created} verified benchmark results; {$existing} duplicates already existed.");
        $this->info('Recalculated composite scores for '.count($touched).' models using verified results only.');
        return self::SUCCESS;
    }

    private function csv(string $path): array { $h=fopen($path,'r');$head=fgetcsv($h);$rows=[];while(($v=fgetcsv($h))!==false){if(count($v)===count($head))$rows[]=array_combine($head,$v);}fclose($h);return $rows; }
    private function bool(?string $v): bool { return in_array(strtolower(trim((string)$v)),['1','true','yes','y'],true); }
    private function weight(string $name): float { return match($name){'SWE-bench Verified'=>1.25,'GPQA Diamond'=>1.20,'MMMU'=>1.10,default=>1.0}; }
}
