<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tools', function (Blueprint $table) {
            if (! Schema::hasColumn('tools', 'product_status')) {
                $table->string('product_status', 32)->default('unknown')->index()->after('status');
            }
            if (! Schema::hasColumn('tools', 'product_status_note')) {
                $table->text('product_status_note')->nullable()->after('product_status');
            }
            if (! Schema::hasColumn('tools', 'product_status_source_id')) {
                $table->foreignId('product_status_source_id')->nullable()->after('product_status_note')->constrained('tool_sources')->nullOnDelete();
            }
            if (! Schema::hasColumn('tools', 'product_status_verified_at')) {
                $table->timestamp('product_status_verified_at')->nullable()->after('product_status_source_id');
            }
        });

        Schema::table('feature_tool', function (Blueprint $table) {
            if (! Schema::hasColumn('feature_tool', 'description')) {
                $table->text('description')->nullable()->after('feature_id');
            }
            if (! Schema::hasColumn('feature_tool', 'verification_status')) {
                $table->string('verification_status', 24)->default('pending')->index()->after('description');
            }
            if (! Schema::hasColumn('feature_tool', 'tool_source_id')) {
                $table->foreignId('tool_source_id')->nullable()->after('verification_status')->constrained('tool_sources')->nullOnDelete();
            }
            if (! Schema::hasColumn('feature_tool', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('tool_source_id');
            }
            if (! Schema::hasColumn('feature_tool', 'notes')) {
                $table->text('notes')->nullable()->after('verified_at');
            }
        });

        Schema::table('tool_use_case', function (Blueprint $table) {
            if (! Schema::hasColumn('tool_use_case', 'fit_note')) {
                $table->text('fit_note')->nullable()->after('use_case_id');
            }
            if (! Schema::hasColumn('tool_use_case', 'verification_status')) {
                $table->string('verification_status', 24)->default('pending')->index()->after('fit_note');
            }
            if (! Schema::hasColumn('tool_use_case', 'tool_source_id')) {
                $table->foreignId('tool_source_id')->nullable()->after('verification_status')->constrained('tool_sources')->nullOnDelete();
            }
            if (! Schema::hasColumn('tool_use_case', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('tool_source_id');
            }
            if (! Schema::hasColumn('tool_use_case', 'notes')) {
                $table->text('notes')->nullable()->after('verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tool_use_case', function (Blueprint $table) {
            foreach (['tool_source_id'] as $column) {
                if (Schema::hasColumn('tool_use_case', $column)) $table->dropConstrainedForeignId($column);
            }
            $columns = array_values(array_filter(['fit_note','verification_status','verified_at','notes'], fn ($column) => Schema::hasColumn('tool_use_case', $column)));
            if ($columns) $table->dropColumn($columns);
        });

        Schema::table('feature_tool', function (Blueprint $table) {
            foreach (['tool_source_id'] as $column) {
                if (Schema::hasColumn('feature_tool', $column)) $table->dropConstrainedForeignId($column);
            }
            $columns = array_values(array_filter(['description','verification_status','verified_at','notes'], fn ($column) => Schema::hasColumn('feature_tool', $column)));
            if ($columns) $table->dropColumn($columns);
        });

        Schema::table('tools', function (Blueprint $table) {
            if (Schema::hasColumn('tools', 'product_status_source_id')) $table->dropConstrainedForeignId('product_status_source_id');
            $columns = array_values(array_filter(['product_status','product_status_note','product_status_verified_at'], fn ($column) => Schema::hasColumn('tools', $column)));
            if ($columns) $table->dropColumn($columns);
        });
    }
};
