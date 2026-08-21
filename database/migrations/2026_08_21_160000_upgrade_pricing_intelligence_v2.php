<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->string('currency', 10)->default('USD')->after('plan_name');
            $table->string('billing_type', 30)->default('subscription')->after('currency');
            $table->string('billing_unit', 80)->nullable()->after('billing_type');
            $table->timestamp('last_verified_at')->nullable()->after('limits');
        });

        Schema::create('model_pricing_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_model_id')->constrained('ai_models')->cascadeOnDelete();
            $table->string('metric', 50);
            $table->string('source_name')->nullable();
            $table->text('source_url');
            $table->string('source_type', 30)->default('auto');
            $table->text('extraction_rule')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('unit')->default('per 1M tokens');
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->string('last_check_status', 30)->nullable();
            $table->text('last_check_message')->nullable();
            $table->text('last_detected_value')->nullable();
            $table->timestamps();
            $table->index(['ai_model_id','metric','enabled']);
        });

        Schema::create('model_pricing_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_model_id')->constrained('ai_models')->cascadeOnDelete();
            $table->string('metric', 50);
            $table->decimal('old_value', 14, 6)->nullable();
            $table->decimal('new_value', 14, 6)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('unit')->default('per 1M tokens');
            $table->text('source_url')->nullable();
            $table->string('change_type', 30)->default('update');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['ai_model_id','metric','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_pricing_history');
        Schema::dropIfExists('model_pricing_sources');
        Schema::table('pricing_plans', function (Blueprint $table) {
            $table->dropColumn(['currency','billing_type','billing_unit','last_verified_at']);
        });
    }
};
