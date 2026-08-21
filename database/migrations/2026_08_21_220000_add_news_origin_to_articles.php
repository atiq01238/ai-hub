<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration{public function up():void{Schema::table('articles',fn(Blueprint $t)=>$t->foreignId('origin_news_item_id')->nullable()->unique()->constrained('news_items')->nullOnDelete());}public function down():void{Schema::table('articles',function(Blueprint $t){$t->dropForeign(['origin_news_item_id']);$t->dropColumn('origin_news_item_id');});}};
