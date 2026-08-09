<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // author
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->string('featured_image_path')->nullable();
            $table->longText('content')->nullable();
            $table->text('summary')->nullable();

            $table->string('category')->nullable();
            $table->json('tags')->nullable();
            $table->json('related_tools')->nullable();
            $table->json('related_models')->nullable();

            $table->string('seo_title')->nullable();
            $table->string('meta_description')->nullable();

            $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft');
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
