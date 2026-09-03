<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            $table->string('pricing_type', 40)->nullable()->after('profile_verified_at');
            $table->string('pricing_basis')->nullable()->after('pricing_type');
            $table->string('pricing_unit_label', 120)->nullable()->after('pricing_basis');
            $table->text('pricing_summary')->nullable()->after('pricing_unit_label');
            $table->string('pricing_verification_status', 40)->nullable()->after('pricing_summary');
            $table->timestamp('pricing_verified_at')->nullable()->after('pricing_verification_status');
        });

        Schema::create('model_evidence_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_model_id')->constrained('ai_models')->cascadeOnDelete();
            $table->string('evidence_type', 40)->index();
            $table->string('source_name')->nullable();
            $table->text('source_url');
            $table->string('source_type', 30)->default('official');
            $table->string('verification_status', 60)->nullable();
            $table->timestamp('verified_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->string('source_hash', 64)->unique();
            $table->timestamps();

            $table->index(['ai_model_id', 'evidence_type'], 'model_evidence_model_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_evidence_sources');

        Schema::table('ai_models', function (Blueprint $table) {
            $table->dropColumn([
                'pricing_type',
                'pricing_basis',
                'pricing_unit_label',
                'pricing_summary',
                'pricing_verification_status',
                'pricing_verified_at',
            ]);
        });
    }
};
