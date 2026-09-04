<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_targets', function (Blueprint $table) {
            $table->id();
            $table->string('target_key', 191)->unique();
            $table->string('route_name', 120);
            $table->string('page_type', 80);
            $table->string('targetable_type', 150)->nullable();
            $table->unsignedBigInteger('targetable_id')->nullable();
            $table->string('primary_keyword');
            $table->json('secondary_keywords')->nullable();
            $table->string('search_intent', 60)->default('informational');
            $table->string('topic_cluster', 160)->nullable();
            $table->string('source', 30)->default('auto');
            $table->boolean('is_locked')->default(false);
            $table->timestamp('last_researched_at')->nullable();
            $table->timestamps();

            $table->index(['route_name', 'page_type']);
            $table->index(['targetable_type', 'targetable_id']);
            $table->index(['source', 'is_locked']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_targets');
    }
};
