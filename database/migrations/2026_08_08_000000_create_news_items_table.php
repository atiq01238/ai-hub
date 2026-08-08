<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

            $table->string('headline');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->text('why_it_matters')->nullable();

            $table->string('category')->nullable();     // e.g. "Breaking News", "Funding"
            $table->string('source')->nullable();        // e.g. "TechCrunch"
            $table->string('source_url')->nullable();

            $table->enum('sentiment', ['positive', 'neutral', 'negative'])->default('neutral');
            $table->unsignedTinyInteger('importance')->default(50); // 0–100

            $table->enum('verification_status', ['unverified', 'needs_verification', 'verified'])
                ->default('unverified');

            $table->json('tags')->nullable();
            $table->json('related_tools')->nullable(); // simple text list, e.g. ["ChatGPT"]

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_items');
    }
};
