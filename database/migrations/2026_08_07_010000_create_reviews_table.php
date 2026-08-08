<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('rating', 2, 1); // 1.0–5.0, the number the visitor picks

            // Optional extras — filled in by staff writing a full editorial review,
            // left blank by an everyday visitor who just leaves a star rating.
            $table->string('verdict')->nullable();
            $table->text('body')->nullable();
            $table->json('pros')->nullable();
            $table->json('cons')->nullable();
            $table->json('rating_breakdown')->nullable();

            $table->enum('status', ['pending', 'published', 'flagged'])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
