<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('comparisons',function(Blueprint $t){$t->string('comparison_version',30)->default('v2');$t->text('summary')->nullable();$t->string('primary_intent',80)->nullable()->index();$t->timestamp('last_verified_at')->nullable();$t->boolean('auto_generated')->default(false)->index();$t->json('seo_faq')->nullable();}); }
 public function down(): void { Schema::table('comparisons',fn(Blueprint $t)=>$t->dropColumn(['comparison_version','summary','primary_intent','last_verified_at','auto_generated','seo_faq'])); }
};
