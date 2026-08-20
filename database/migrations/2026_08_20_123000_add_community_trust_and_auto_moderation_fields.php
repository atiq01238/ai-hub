<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('community_trust_level', 20)
                ->default('normal')
                ->after('status');

            $table->timestamp('community_trusted_at')->nullable();
            $table->timestamp('community_restricted_at')->nullable();
            $table->text('community_restriction_reason')->nullable();
            $table->foreignId('community_trust_updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->index('community_trust_level');
        });

        Schema::table('community_comments', function (Blueprint $table) {
            $table->string('moderation_reason', 255)
                ->nullable()
                ->after('status');

            $table->boolean('auto_published')
                ->default(false)
                ->after('moderation_reason');

            $table->index(['auto_published', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('community_comments', function (Blueprint $table) {
            $table->dropIndex(['auto_published', 'status']);
            $table->dropColumn(['moderation_reason', 'auto_published']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['community_trust_level']);
            $table->dropConstrainedForeignId('community_trust_updated_by');
            $table->dropColumn([
                'community_trust_level',
                'community_trusted_at',
                'community_restricted_at',
                'community_restriction_reason',
            ]);
        });
    }
};
