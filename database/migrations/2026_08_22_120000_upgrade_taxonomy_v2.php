<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'type')) $table->string('type', 30)->default('product')->index();
            if (!Schema::hasColumn('categories', 'short_description')) $table->string('short_description', 500)->nullable();
            if (!Schema::hasColumn('categories', 'description')) $table->text('description')->nullable();
            if (!Schema::hasColumn('categories', 'meta_title')) $table->string('meta_title', 80)->nullable();
            if (!Schema::hasColumn('categories', 'meta_description')) $table->string('meta_description', 180)->nullable();
            if (!Schema::hasColumn('categories', 'is_active')) $table->boolean('is_active')->default(true)->index();
            if (!Schema::hasColumn('categories', 'is_indexable')) $table->boolean('is_indexable')->default(true)->index();
            if (!Schema::hasColumn('categories', 'sort_order')) $table->unsignedSmallInteger('sort_order')->default(0)->index();
        });

        Schema::table('subcategories', function (Blueprint $table) {
            if (!Schema::hasColumn('subcategories', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('subcategories', 'short_description')) $table->string('short_description', 500)->nullable();
            if (!Schema::hasColumn('subcategories', 'description')) $table->text('description')->nullable();
            if (!Schema::hasColumn('subcategories', 'meta_title')) $table->string('meta_title', 80)->nullable();
            if (!Schema::hasColumn('subcategories', 'meta_description')) $table->string('meta_description', 180)->nullable();
            if (!Schema::hasColumn('subcategories', 'is_active')) $table->boolean('is_active')->default(true)->index();
            if (!Schema::hasColumn('subcategories', 'is_indexable')) $table->boolean('is_indexable')->default(true)->index();
            if (!Schema::hasColumn('subcategories', 'sort_order')) $table->unsignedSmallInteger('sort_order')->default(0)->index();
        });

        Schema::table('features', function (Blueprint $table) {
            if (!Schema::hasColumn('features', 'short_description')) $table->string('short_description', 500)->nullable();
            if (!Schema::hasColumn('features', 'description')) $table->text('description')->nullable();
            if (!Schema::hasColumn('features', 'group')) $table->string('group', 80)->nullable()->index();
            if (!Schema::hasColumn('features', 'icon')) $table->string('icon', 80)->nullable();
            if (!Schema::hasColumn('features', 'meta_title')) $table->string('meta_title', 80)->nullable();
            if (!Schema::hasColumn('features', 'meta_description')) $table->string('meta_description', 180)->nullable();
            if (!Schema::hasColumn('features', 'is_active')) $table->boolean('is_active')->default(true)->index();
            if (!Schema::hasColumn('features', 'is_indexable')) $table->boolean('is_indexable')->default(true)->index();
            if (!Schema::hasColumn('features', 'sort_order')) $table->unsignedSmallInteger('sort_order')->default(0)->index();
        });

        Schema::table('tags', function (Blueprint $table) {
            if (!Schema::hasColumn('tags', 'short_description')) $table->string('short_description', 500)->nullable();
            if (!Schema::hasColumn('tags', 'description')) $table->text('description')->nullable();
            if (!Schema::hasColumn('tags', 'meta_title')) $table->string('meta_title', 80)->nullable();
            if (!Schema::hasColumn('tags', 'meta_description')) $table->string('meta_description', 180)->nullable();
            if (!Schema::hasColumn('tags', 'is_active')) $table->boolean('is_active')->default(true)->index();
            if (!Schema::hasColumn('tags', 'is_indexable')) $table->boolean('is_indexable')->default(false)->index();
            if (!Schema::hasColumn('tags', 'sort_order')) $table->unsignedSmallInteger('sort_order')->default(0)->index();
        });

        if (!Schema::hasTable('use_cases')) {
            Schema::create('use_cases', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->string('short_description', 500)->nullable();
                $table->text('description')->nullable();
                $table->string('icon', 80)->nullable();
                $table->string('meta_title', 80)->nullable();
                $table->string('meta_description', 180)->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_indexable')->default(true)->index();
                $table->unsignedSmallInteger('sort_order')->default(0)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_model_feature')) {
            Schema::create('ai_model_feature', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_model_id')->constrained('ai_models')->cascadeOnDelete();
                $table->foreignId('feature_id')->constrained('features')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['ai_model_id', 'feature_id']);
            });
        }

        if (!Schema::hasTable('tool_use_case')) {
            Schema::create('tool_use_case', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
                $table->foreignId('use_case_id')->constrained('use_cases')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['tool_id', 'use_case_id']);
            });
        }

        if (!Schema::hasTable('ai_model_use_case')) {
            Schema::create('ai_model_use_case', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_model_id')->constrained('ai_models')->cascadeOnDelete();
                $table->foreignId('use_case_id')->constrained('use_cases')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['ai_model_id', 'use_case_id']);
            });
        }

        if (!Schema::hasTable('ai_model_tag')) {
            Schema::create('ai_model_tag', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_model_id')->constrained('ai_models')->cascadeOnDelete();
                $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['ai_model_id', 'tag_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_model_tag');
        Schema::dropIfExists('ai_model_use_case');
        Schema::dropIfExists('tool_use_case');
        Schema::dropIfExists('ai_model_feature');
        Schema::dropIfExists('use_cases');

        Schema::table('subcategories', function (Blueprint $table) {
            if (Schema::hasColumn('subcategories', 'category_id')) $table->dropConstrainedForeignId('category_id');
        });

        foreach ([
            'categories' => ['type','short_description','description','meta_title','meta_description','is_active','is_indexable','sort_order'],
            'subcategories' => ['short_description','description','meta_title','meta_description','is_active','is_indexable','sort_order'],
            'features' => ['short_description','description','group','icon','meta_title','meta_description','is_active','is_indexable','sort_order'],
            'tags' => ['short_description','description','meta_title','meta_description','is_active','is_indexable','sort_order'],
        ] as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
                $existing = array_values(array_filter($columns, fn ($column) => Schema::hasColumn($tableName, $column)));
                if ($existing) $table->dropColumn($existing);
            });
        }
    }
};
