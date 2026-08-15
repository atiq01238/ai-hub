<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            // Connect each article to the actual NewsSource record.
            $table->foreignId('news_source_id')
                ->nullable()
                ->after('company_id')
                ->constrained('news_sources')
                ->nullOnDelete();

            // Normalized values are used later for duplicate detection/search.
            $table->string('normalized_headline', 255)
                ->nullable()
                ->after('headline');

            $table->string('content_hash', 64)
                ->nullable()
                ->after('source_url');

            // Tracks where an article is in the intelligence pipeline.
            $table->enum('processing_status', [
                'pending',
                'processing',
                'processed',
                'failed',
            ])->default('pending')->after('related_tools');

            $table->timestamp('fetched_at')
                ->nullable()
                ->after('published_at');

            $table->timestamp('ai_processed_at')
                ->nullable()
                ->after('fetched_at');

            $table->timestamp('verified_at')
                ->nullable()
                ->after('ai_processed_at');

            $table->text('verification_notes')
                ->nullable()
                ->after('verified_at');

            $table->index('news_source_id');
            $table->index('normalized_headline');
            $table->index('content_hash');
            $table->index('processing_status');
            $table->index('verification_status');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropForeign(['news_source_id']);

            $table->dropIndex(['news_source_id']);
            $table->dropIndex(['normalized_headline']);
            $table->dropIndex(['content_hash']);
            $table->dropIndex(['processing_status']);
            $table->dropIndex(['verification_status']);
            $table->dropIndex(['published_at']);

            $table->dropColumn([
                'news_source_id',
                'normalized_headline',
                'content_hash',
                'processing_status',
                'fetched_at',
                'ai_processed_at',
                'verified_at',
                'verification_notes',
            ]);
        });
    }
};
