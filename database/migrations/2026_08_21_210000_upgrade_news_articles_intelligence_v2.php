<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('news_sources',function(Blueprint $t){$t->string('authority_type',40)->default('publication')->index();$t->unsignedTinyInteger('authority_score')->default(60)->index();});
  Schema::table('news_items',function(Blueprint $t){$t->decimal('trending_score',8,2)->default(0)->index();});
  Schema::create('news_item_tool',function(Blueprint $t){$t->foreignId('news_item_id')->constrained()->cascadeOnDelete();$t->foreignId('tool_id')->constrained()->cascadeOnDelete();$t->primary(['news_item_id','tool_id']);});
  Schema::create('ai_model_news_item',function(Blueprint $t){$t->foreignId('news_item_id')->constrained()->cascadeOnDelete();$t->foreignId('ai_model_id')->constrained('ai_models')->cascadeOnDelete();$t->primary(['news_item_id','ai_model_id']);});
 }
 public function down(): void {
  Schema::dropIfExists('ai_model_news_item');Schema::dropIfExists('news_item_tool');
  Schema::table('news_items',fn(Blueprint $t)=>$t->dropColumn('trending_score'));
  Schema::table('news_sources',fn(Blueprint $t)=>$t->dropColumn(['authority_type','authority_score']));
 }
};
