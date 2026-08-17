<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->text('moderation_note')->nullable()->after('status');
            $table->foreignId('moderated_by')->nullable()->after('moderation_note')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            $table->softDeletes();
            $table->index(['review_type', 'status'], 'community_reviews_type_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropIndex('community_reviews_type_status_index');
            $table->dropColumn(['moderation_note', 'moderated_at', 'deleted_at']);
        });
    }
};
