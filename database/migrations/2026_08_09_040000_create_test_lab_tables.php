<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('prompt');
            $table->string('category')->nullable(); // Text, Image, Video, Audio, Coding, Reasoning
            $table->string('criteria')->nullable();
            $table->text('expected_output')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_test_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_model_id')->constrained()->cascadeOnDelete();

            $table->text('response_text')->nullable();

            $table->unsignedTinyInteger('score_quality')->nullable();
            $table->unsignedTinyInteger('score_accuracy')->nullable();
            $table->unsignedTinyInteger('score_prompt_adherence')->nullable();
            $table->unsignedTinyInteger('score_creativity')->nullable();
            $table->unsignedTinyInteger('score_speed')->nullable();
            $table->decimal('overall_score', 4, 1)->default(0); // auto-calculated average

            $table->timestamps();

            $table->unique(['ai_test_id', 'ai_model_id']); // one result per model per test
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_test_results');
        Schema::dropIfExists('ai_tests');
    }
};
