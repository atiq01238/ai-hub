<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('news_item_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('platform', ['x', 'facebook', 'instagram', 'linkedin', 'youtube', 'tiktok']);
            $table->text('content');
            $table->string('image_path')->nullable();

            // Draft/Scheduled/Published are labels the admin sets manually —
            // nothing here actually posts to the real platform yet.
            $table->enum('status', ['draft', 'scheduled', 'published'])->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
