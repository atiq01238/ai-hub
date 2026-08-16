<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('company_id')->constrained('categories')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('approval_status', 32)->default('draft')->after('status')->index();
            $table->timestamp('submitted_for_review_at')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('submitted_for_review_at');
        });

        Schema::create('article_tool', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'tool_id']);
        });

        Schema::create('ai_model_article', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ai_model_id')->constrained('ai_models')->cascadeOnDelete();
            $table->primary(['article_id', 'ai_model_id']);
        });

        Schema::create('article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'tag_id']);
        });

        Schema::create('article_workflow_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('action', 64);
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->index(['article_id', 'created_at']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->string('review_type', 24)->default('user')->after('user_id')->index();
        });

        // Preserve already-live content when the approval layer is introduced.
        DB::table('articles')->whereIn('status', ['published', 'scheduled'])->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
        ]);

        // Make the former hard-coded article categories available through Taxonomy.
        foreach (['New Model', 'Product Update', 'Pricing Change', 'Research', 'Benchmark', 'Guide', 'Opinion'] as $name) {
            DB::table('categories')->insertOrIgnore([
                'name' => $name,
                'slug' => \Illuminate\Support\Str::slug($name),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('review_type');
        });

        Schema::dropIfExists('article_workflow_events');
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('ai_model_article');
        Schema::dropIfExists('article_tool');

        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('reviewer_id');
            $table->dropColumn(['approval_status', 'submitted_for_review_at', 'approved_at']);
        });
    }
};
