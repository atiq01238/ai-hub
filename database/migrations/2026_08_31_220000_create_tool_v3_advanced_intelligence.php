<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('integrations')) {
            Schema::create('integrations', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->string('website')->nullable();
                $table->string('category', 80)->nullable()->index();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedSmallInteger('sort_order')->default(0)->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('integration_tool')) {
            Schema::create('integration_tool', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
                $table->foreignId('integration_id')->constrained('integrations')->cascadeOnDelete();
                $table->foreignId('tool_source_id')->nullable()->constrained('tool_sources')->nullOnDelete();
                $table->string('verification_status', 24)->default('pending')->index();
                $table->timestamp('verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['tool_id', 'integration_id']);
                $table->index(['tool_id', 'verification_status']);
            });
        }

        if (! Schema::hasTable('tool_technical_profiles')) {
            Schema::create('tool_technical_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tool_id')->unique()->constrained('tools')->cascadeOnDelete();

                $table->string('api_status', 32)->default('unknown')->index();
                $table->text('api_docs_url')->nullable();
                $table->foreignId('api_source_id')->nullable()->constrained('tool_sources')->nullOnDelete();

                $table->string('open_source_status', 32)->default('unknown')->index();
                $table->string('license_name')->nullable();
                $table->text('repository_url')->nullable();
                $table->foreignId('repository_source_id')->nullable()->constrained('tool_sources')->nullOnDelete();

                $table->string('self_hosting_status', 32)->default('unknown')->index();
                $table->json('deployment_modes')->nullable();
                $table->foreignId('deployment_source_id')->nullable()->constrained('tool_sources')->nullOnDelete();
                $table->string('commercial_use_status', 32)->default('unknown')->index();
                $table->foreignId('terms_source_id')->nullable()->constrained('tool_sources')->nullOnDelete();

                $table->json('supported_languages')->nullable();
                $table->json('region_availability')->nullable();
                $table->foreignId('availability_source_id')->nullable()->constrained('tool_sources')->nullOnDelete();

                $table->string('data_training_policy', 32)->default('unknown')->index();
                $table->text('data_retention_note')->nullable();
                $table->text('privacy_summary')->nullable();
                $table->foreignId('privacy_source_id')->nullable()->constrained('tool_sources')->nullOnDelete();

                $table->text('security_summary')->nullable();
                $table->json('security_certifications')->nullable();
                $table->json('compliance_certifications')->nullable();
                $table->json('data_residency')->nullable();
                $table->string('sso_status', 32)->default('unknown')->index();
                $table->foreignId('security_source_id')->nullable()->constrained('tool_sources')->nullOnDelete();

                $table->timestamp('last_reviewed_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_technical_profiles');
        Schema::dropIfExists('integration_tool');
        Schema::dropIfExists('integrations');
    }
};
