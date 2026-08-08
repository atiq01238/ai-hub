<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tool_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('version')->nullable();
            $table->date('release_date')->nullable();

            // Kept as a free-text string (e.g. "2M", "128K") to match how it's
            // displayed in the table — simpler than tracking raw token counts for now.
            $table->string('context_window')->nullable();

            $table->decimal('input_price_per_million', 8, 2)->nullable();
            $table->decimal('output_price_per_million', 8, 2)->nullable();

            $table->json('capabilities')->nullable();
            $table->text('capability_notes')->nullable();

            // Composite score (e.g. 94.2) plus the per-benchmark breakdown
            // (e.g. {"MMLU": 91, "HumanEval": 88}) shown on the detail page.
            $table->decimal('benchmark_score', 4, 1)->default(0);
            $table->json('benchmarks')->nullable();

            $table->enum('status', ['active', 'deprecated', 'preview'])->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_models');
    }
};
