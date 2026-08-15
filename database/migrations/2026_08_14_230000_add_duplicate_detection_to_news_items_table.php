<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->foreignId('duplicate_of_id')
                ->nullable()
                ->after('content_hash')
                ->constrained('news_items')
                ->nullOnDelete();

            $table->decimal('duplicate_score', 5, 2)
                ->nullable()
                ->after('duplicate_of_id');

            $table->enum('duplicate_status', [
                'unique',
                'possible',
                'duplicate',
            ])->default('unique')->after('duplicate_score');

            $table->timestamp('duplicate_checked_at')
                ->nullable()
                ->after('duplicate_status');

            $table->index(['duplicate_status', 'duplicate_checked_at']);
            $table->index('duplicate_of_id');
        });
    }

    public function down(): void
    {
        Schema::table('news_items', function (Blueprint $table) {
            $table->dropForeign(['duplicate_of_id']);
            $table->dropIndex(['duplicate_status', 'duplicate_checked_at']);
            $table->dropIndex(['duplicate_of_id']);
            $table->dropColumn([
                'duplicate_of_id',
                'duplicate_score',
                'duplicate_status',
                'duplicate_checked_at',
            ]);
        });
    }
};
