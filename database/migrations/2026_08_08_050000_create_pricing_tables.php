<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();

            $table->string('plan_name');
            $table->decimal('monthly_price', 8, 2)->nullable();
            $table->decimal('yearly_price', 8, 2)->nullable();
            $table->string('api_price_label')->nullable(); // e.g. "$3/1M" — formats vary too much for a plain number
            $table->string('credits')->nullable();          // e.g. "30 hr/mo GPU"
            $table->string('limits')->nullable();            // e.g. "40 msgs/3hr"

            $table->timestamps();
        });

        Schema::create('pricing_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();

            $table->string('plan_name');
            $table->decimal('old_price', 8, 2)->nullable(); // null = plan didn't exist before (new plan)
            $table->decimal('new_price', 8, 2)->nullable(); // null = plan was removed
            $table->enum('change_type', ['increase', 'decrease', 'new_plan', 'removed_plan']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_history');
        Schema::dropIfExists('pricing_plans');
    }
};
