<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparisons', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->string('slug')->unique();

            // 'tool' or 'model' — which table `item_ids` points into.
            $table->enum('comparable_type', ['tool', 'model']);
            $table->json('item_ids'); // e.g. [1, 3] — the 2–4 tools/models being compared

            $table->unsignedInteger('views')->default(0);
            $table->enum('status', ['draft', 'published'])->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparisons');
    }
};
