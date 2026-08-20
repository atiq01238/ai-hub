<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('notifications', function(Blueprint $table){
  $table->string('type',60)->nullable()->after('tone');
  $table->string('action_url',1800)->nullable()->after('description');
  $table->index(['user_id','read_at','created_at'],'notifications_user_read_index');
 }); }
 public function down(): void { Schema::table('notifications', function(Blueprint $table){
  $table->dropIndex('notifications_user_read_index'); $table->dropColumn(['type','action_url']);
 }); }
};
