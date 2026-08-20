<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('community_comments')->cascadeOnDelete();
            $table->string('commentable_type', 32);
            $table->unsignedBigInteger('commentable_id');
            $table->text('body');
            $table->enum('status', ['pending', 'published', 'hidden', 'spam'])->default('pending');
            $table->text('moderation_note')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['commentable_type', 'commentable_id', 'status', 'created_at'], 'community_thread_index');
            $table->index(['user_id', 'status', 'created_at'], 'community_user_status_index');
            $table->index(['parent_id', 'status'], 'community_parent_status_index');
        });

        Schema::create('community_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reactable_type', 24);
            $table->unsignedBigInteger('reactable_id');
            $table->string('reaction', 24)->default('helpful');
            $table->timestamps();

            $table->unique(
                ['user_id', 'reactable_type', 'reactable_id', 'reaction'],
                'community_reaction_unique'
            );
            $table->index(
                ['reactable_type', 'reactable_id', 'reaction'],
                'community_reaction_target_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_reactions');
        Schema::dropIfExists('community_comments');
    }
};
