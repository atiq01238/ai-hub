<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('ai_relevance_score')->nullable()->after('ai_confidence');
            $table->string('ai_relevance_status', 20)->default('pending')->after('ai_relevance_score');
            $table->json('ai_relevance_reasons')->nullable()->after('ai_relevance_status');
            $table->string('ai_relevance_override', 20)->default('auto')->after('ai_relevance_reasons');
            $table->string('ai_relevance_version', 30)->nullable()->after('ai_relevance_override');
            $table->timestamp('ai_relevance_checked_at')->nullable()->after('ai_relevance_version');

            $table->index(['status', 'ai_relevance_status'], 'news_status_relevance_idx');
            $table->index('ai_relevance_score');
        });
    }

    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropIndex('news_status_relevance_idx');
            $table->dropIndex(['ai_relevance_score']);
            $table->dropColumn([
                'ai_relevance_score',
                'ai_relevance_status',
                'ai_relevance_reasons',
                'ai_relevance_override',
                'ai_relevance_version',
                'ai_relevance_checked_at',
            ]);
        });
    }
};
