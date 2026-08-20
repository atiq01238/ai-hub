<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('tool_id')->nullable()->change();

            $table->foreignId('model_id')
                ->nullable()
                ->after('tool_id')
                ->constrained('ai_models')
                ->cascadeOnDelete();

            $table->index(['model_id', 'status'], 'reviews_model_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_model_status_index');
            $table->dropConstrainedForeignId('model_id');
        });

        // Existing data may contain model-only reviews, so restoring tool_id to
        // NOT NULL automatically would be unsafe. Leave it nullable on rollback.
    }
};
