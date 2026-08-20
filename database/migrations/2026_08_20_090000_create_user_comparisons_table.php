<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('comparison_id')->nullable()->constrained('comparisons')->nullOnDelete();
            $table->enum('comparable_type', ['tool', 'model']);
            $table->json('item_ids');
            $table->string('title');
            $table->string('signature', 64);
            $table->boolean('is_saved')->default(false);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'signature']);
            $table->index(['user_id', 'is_saved']);
            $table->index(['user_id', 'last_viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_comparisons');
    }
};
