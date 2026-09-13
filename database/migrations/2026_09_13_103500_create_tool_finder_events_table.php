<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_finder_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('task', 220)->nullable();
            $table->string('shortcut', 40)->nullable();
            $table->string('budget', 20)->default('any');
            $table->string('experience', 20)->default('any');
            $table->string('priority', 20)->default('any');
            $table->json('filters')->nullable();
            $table->json('detected_use_cases')->nullable();
            $table->json('detected_preferences')->nullable();
            $table->unsignedInteger('result_count')->default(0);
            $table->foreignId('top_tool_id')->nullable()->constrained('tools')->nullOnDelete();
            $table->foreignId('clicked_tool_id')->nullable()->constrained('tools')->nullOnDelete();
            $table->string('clicked_action', 20)->nullable();
            $table->string('session_key', 100)->nullable();
            $table->timestamps();

            $table->index(['created_at', 'shortcut']);
            $table->index(['created_at', 'clicked_action']);
            $table->index(['session_key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_finder_events');
    }
};
