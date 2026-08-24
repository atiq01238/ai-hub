<?php
namespace App\Console\Commands;
use App\Models\AiModel;use App\Models\Tool;use App\Models\Comparison;use Illuminate\Console\Command;use Illuminate\Support\Str;
class ImportComparisonIntelligence extends Command {
 protected $signature='comparisons:import-intelligence {--dry-run} {--dataset=v1-2026-08-21}'; protected $description='Create curated SEO comparison definitions using exact catalog names.';
 public function handle(): int {
  if($this->option('dataset')!=='v1-2026-08-21'){ $this->error('Unsupported comparison dataset.');return self::FAILURE; }
  $file=storage_path('app/import-templates/comparison-intelligence-v1-2026-08-21.csv');$h=fopen($file,'r');$head=fgetcsv($h);$rows=[];while(($v=fgetcsv($h))!==false)$rows[]=array_combine($head,$v);fclose($h);
  $missing=[];$ready=[];foreach($rows as $r){$class=$r['type']==='model'?AiModel::class:Tool::class;$names=[$r['item_1'],$r['item_2']];$found=$class::whereIn('name',$names)->pluck('id','name');foreach($names as $n)if(!$found->has($n))$missing[]=$n;if(count($missing)===0||collect($names)->every(fn($n)=>$found->has($n)))$ready[]=[$r,$found];}
  $missing=array_values(array_unique($missing));$this->info('Comparison Intelligence v1: '.count($rows).' curated comparisons ('.collect($rows)->where('type','model')->count().' model + '.collect($rows)->where('type','tool')->count().' tool).');if($missing)$this->warn('Missing catalog names (affected comparisons skipped): '.implode(', ',$missing));if($this->option('dry-run')){$this->info('Dry run complete. No database changes made.');return self::SUCCESS;}
  $made=0;$skipped=0;foreach($rows as $r){$class=$r['type']==='model'?AiModel::class:Tool::class;$names=[$r['item_1'],$r['item_2']];$found=$class::whereIn('name',$names)->pluck('id','name');if(!collect($names)->every(fn($n)=>$found->has($n))){$skipped++;continue;}$ids=collect($names)->map(fn($n)=>(int)$found[$n])->all();$slug=Str::slug($r['item_1'].'-vs-'.$r['item_2']);Comparison::updateOrCreate(['slug'=>$slug],['title'=>$r['item_1'].' vs '.$r['item_2'],'comparable_type'=>$r['type'],'item_ids'=>$ids,'status'=>'published','comparison_version'=>'v2','summary'=>$r['summary'],'primary_intent'=>$r['intent'],'last_verified_at'=>now(),'auto_generated'=>true,'seo_faq'=>[['question'=>'Which is better: '.$r['item_1'].' or '.$r['item_2'].'?','answer'=>'The better choice depends on your use case. AI Orbit compares current catalog data, verified benchmark results where available, pricing and capabilities side by side.'],['question'=>'How is this comparison kept current?','answer'=>'Dynamic benchmark and pricing sections use the latest verified data stored in AI Orbit.']]]);$made++;}
  $this->info("Published/updated {$made} comparisons; {$skipped} skipped for missing catalog items.");return self::SUCCESS;
 }
}
