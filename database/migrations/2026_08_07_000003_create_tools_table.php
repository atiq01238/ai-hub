<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tools', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subcategory')->nullable();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('website')->nullable();
            $table->date('launch_date')->nullable();

            $table->string('short_description')->nullable();
            $table->text('description')->nullable();

            // Simplification: instead of separate pivot tables for now,
            // store these small option lists as JSON arrays, e.g. ["Free","Freemium"].
            $table->json('pricing_models')->nullable();
            $table->json('tags')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('platforms')->nullable();

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');

            // Star rating shown in listings (0.0–5.0) and the 0–100 sub-scores from the form.
            $table->decimal('rating', 2, 1)->default(0);
            $table->unsignedTinyInteger('popularity')->default(0);
            $table->json('rating_breakdown')->nullable();

            $table->string('seo_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->string('og_image_path')->nullable();

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
