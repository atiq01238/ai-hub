<?php
namespace App\Console\Commands;
use App\Models\NewsItem;use App\Services\NewsEntityLinker;use App\Services\NewsIntelligenceService;use Illuminate\Console\Command;
class RefreshNewsIntelligence extends Command {
 protected $signature='news:refresh-intelligence {--dry-run}'; protected $description='Recalculate source-aware trending scores and entity links.';
 public function handle(NewsIntelligenceService $scores, NewsEntityLinker $linker):int {
  $q=NewsItem::with('newsSource')->whereNull('duplicate_of_id');$count=$q->count();$this->info("Eligible news items: {$count}");
  if($this->option('dry-run')){$this->info('Dry run complete. No database changes made.');return self::SUCCESS;}
  $linked=['tools'=>0,'models'=>0,'companies'=>0];$q->chunkById(200,function($items)use($scores,$linker,&$linked){foreach($items as $n){$r=$linker->link($n);foreach($linked as $k=>$v)$linked[$k]+=$r[$k];$scores->refresh($n->fresh());}});
  $this->info("Refreshed {$count} news records; matched {$linked['tools']} tool, {$linked['models']} model and {$linked['companies']} company mentions.");return self::SUCCESS;
 }
}
