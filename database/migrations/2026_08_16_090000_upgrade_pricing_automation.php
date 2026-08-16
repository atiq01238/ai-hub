<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_plan_id')->constrained('pricing_plans')->cascadeOnDelete();
            $table->string('metric', 40)->default('monthly_price');
            $table->string('source_name')->nullable();
            $table->text('source_url');
            $table->string('source_type', 30)->default('auto');
            $table->text('extraction_rule')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('unit')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->string('last_check_status', 30)->nullable();
            $table->text('last_check_message')->nullable();
            $table->text('last_detected_value')->nullable();
            $table->timestamps();

            $table->index(['enabled', 'metric']);
        });

        Schema::create('detected_price_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_plan_id')->constrained('pricing_plans')->cascadeOnDelete();
            $table->foreignId('pricing_source_id')->nullable()->constrained('pricing_sources')->nullOnDelete();
            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
            $table->string('metric', 40);
            $table->text('current_value')->nullable();
            $table->text('detected_value')->nullable();
            $table->string('currency', 10)->nullable();
            $table->text('source_url')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('review_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'detected_at']);
            $table->index(['pricing_plan_id', 'metric', 'status']);
        });

        Schema::table('pricing_history', function (Blueprint $table) {
            $table->string('metric', 40)->default('monthly_price')->after('plan_name');
            $table->text('old_value')->nullable()->after('metric');
            $table->text('new_value')->nullable()->after('old_value');
            $table->text('source_url')->nullable()->after('change_type');
            $table->foreignId('detected_change_id')->nullable()->after('source_url')->constrained('detected_price_changes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pricing_history', function (Blueprint $table) {
            $table->dropConstrainedForeignId('detected_change_id');
            $table->dropColumn(['metric', 'old_value', 'new_value', 'source_url']);
        });

        Schema::dropIfExists('detected_price_changes');
        Schema::dropIfExists('pricing_sources');
    }
};
