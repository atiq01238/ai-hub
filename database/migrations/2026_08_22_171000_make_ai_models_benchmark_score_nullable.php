<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->decimal('benchmark_score', 4, 1)
                ->nullable()
                ->default(null)
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('ai_models')
            ->whereNull('benchmark_score')
            ->update(['benchmark_score' => 0]);

        Schema::table('ai_models', function (Blueprint $table) {
            $table->decimal('benchmark_score', 4, 1)
                ->nullable(false)
                ->default(0)
                ->change();
        });
    }
};
