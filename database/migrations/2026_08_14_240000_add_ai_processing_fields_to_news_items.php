<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->string('ai_topic', 100)->nullable()->after('category');
            $table->json('ai_tags')->nullable()->after('ai_topic');
            $table->text('ai_summary')->nullable()->after('ai_tags');
            $table->text('ai_why_it_matters')->nullable()->after('ai_summary');
            $table->unsignedTinyInteger('ai_confidence')->nullable()->after('ai_why_it_matters');
            $table->string('ai_processor', 50)->nullable()->after('ai_confidence');

            $table->index('ai_topic');
            $table->index('ai_confidence');
        });
    }

    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropIndex(['ai_topic']);
            $table->dropIndex(['ai_confidence']);

            $table->dropColumn([
                'ai_topic',
                'ai_tags',
                'ai_summary',
                'ai_why_it_matters',
                'ai_confidence',
                'ai_processor',
            ]);
        });
    }
};
