<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            // Mirrors ai_models.benchmarks / benchmark_score so tools can be
            // ranked on the same Benchmarks page as AI models.
            $table->json('benchmarks')->nullable()->after('rating_breakdown');
            $table->decimal('benchmark_score', 4, 1)->default(0)->after('benchmarks');
        });
    }

    public function down(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            $table->dropColumn(['benchmarks', 'benchmark_score']);
        });
    }
};
