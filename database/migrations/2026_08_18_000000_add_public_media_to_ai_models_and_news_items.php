<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | AI Models
        |--------------------------------------------------------------------------
        | logo_path may already exist in the original ai_models migration,
        | so we only add columns that are actually missing.
        */

        if (!Schema::hasColumn('ai_models', 'logo_path')) {
            Schema::table('ai_models', function (Blueprint $table) {
                $table->string('logo_path')->nullable()->after('slug');
            });
        }

        if (!Schema::hasColumn('ai_models', 'cover_image_path')) {
            Schema::table('ai_models', function (Blueprint $table) {
                $table->string('cover_image_path')->nullable()->after('logo_path');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | News Items
        |--------------------------------------------------------------------------
        */

        if (!Schema::hasColumn('news_items', 'image_path')) {
            Schema::table('news_items', function (Blueprint $table) {
                $table->string('image_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        /*
        | Do NOT remove logo_path here because it may belong
        | to your original ai_models migration.
        */

        if (Schema::hasColumn('ai_models', 'cover_image_path')) {
            Schema::table('ai_models', function (Blueprint $table) {
                $table->dropColumn('cover_image_path');
            });
        }

        if (Schema::hasColumn('news_items', 'image_path')) {
            Schema::table('news_items', function (Blueprint $table) {
                $table->dropColumn('image_path');
            });
        }
    }
};