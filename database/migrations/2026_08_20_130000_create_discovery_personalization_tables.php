<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('search_events', function(Blueprint $t){
   $t->id(); $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
   $t->string('query',180); $t->string('type',30)->default('all'); $t->unsignedInteger('result_count')->default(0);
   $t->boolean('clicked')->default(false); $t->string('clicked_type',40)->nullable(); $t->unsignedBigInteger('clicked_id')->nullable();
   $t->string('session_key',100)->nullable(); $t->timestamps();
   $t->index(['query','created_at']); $t->index(['user_id','created_at']);
  });
  Schema::create('saved_searches', function(Blueprint $t){
   $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->string('query',180); $t->string('type',30)->default('all');
   $t->json('filters')->nullable(); $t->timestamps(); $t->unique(['user_id','query','type']);
  });
  Schema::create('user_preferences', function(Blueprint $t){
   $t->id(); $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
   $t->json('interests')->nullable(); $t->json('use_cases')->nullable(); $t->string('experience_level',30)->nullable();
   $t->boolean('onboarding_completed')->default(false); $t->timestamp('onboarding_completed_at')->nullable(); $t->timestamps();
  });
 }
 public function down(): void { Schema::dropIfExists('user_preferences'); Schema::dropIfExists('saved_searches'); Schema::dropIfExists('search_events'); }
};