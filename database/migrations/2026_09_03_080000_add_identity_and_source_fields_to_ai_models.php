<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->text('official_source_url')->nullable();
            $table->string('official_name')->nullable();
            $table->string('official_model_id', 191)->nullable();
            $table->string('identity_status', 30)->default('unreviewed')->index();
            $table->text('identity_notes')->nullable();
            $table->timestamp('identity_verified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->dropIndex(['identity_status']);
            $table->dropColumn([
                'official_source_url',
                'official_name',
                'official_model_id',
                'identity_status',
                'identity_notes',
                'identity_verified_at',
            ]);
        });
    }
};
