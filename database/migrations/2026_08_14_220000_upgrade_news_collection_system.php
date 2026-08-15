<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_sources', function (Blueprint $table) {
            $table->timestamp('last_started_at')->nullable()->after('last_fetched_at');
            $table->timestamp('last_success_at')->nullable()->after('last_started_at');

            $table->unsignedInteger('last_items_seen')->default(0)->after('articles_collected');
            $table->unsignedInteger('last_items_created')->default(0)->after('last_items_seen');
            $table->unsignedInteger('last_items_skipped')->default(0)->after('last_items_created');

            $table->unsignedInteger('last_duration_ms')->nullable()->after('last_items_skipped');
            $table->unsignedInteger('consecutive_failures')->default(0)->after('last_duration_ms');

            $table->index('last_success_at');
            $table->index('consecutive_failures');
        });

        Schema::table('news_items', function (Blueprint $table) {
            // The feed GUID/id lets us recognize the same feed entry even when
            // a publisher changes the article URL slightly.
            $table->string('source_item_id', 191)
                ->nullable()
                ->after('source_url');

            // Canonical URL is normalized for exact duplicate checks.
            $table->string('canonical_url', 2048)
                ->nullable()
                ->after('source_item_id');

            $table->index(['news_source_id', 'source_item_id']);
            $table->index('canonical_url');
        });
    }

    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropIndex(['news_source_id', 'source_item_id']);
            $table->dropIndex(['canonical_url']);
            $table->dropColumn(['source_item_id', 'canonical_url']);
        });

        Schema::table('news_sources', function (Blueprint $table) {
            $table->dropIndex(['last_success_at']);
            $table->dropIndex(['consecutive_failures']);
            $table->dropColumn([
                'last_started_at',
                'last_success_at',
                'last_items_seen',
                'last_items_created',
                'last_items_skipped',
                'last_duration_ms',
                'consecutive_failures',
            ]);
        });
    }
};
