<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tool_sources')) {
            Schema::create('tool_sources', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
                $table->string('source_type', 40)->default('official_product')->index();
                $table->string('source_name')->nullable();
                $table->text('source_url');
                $table->boolean('is_primary')->default(false)->index();
                $table->string('verification_status', 24)->default('pending')->index();
                $table->timestamp('last_checked_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->boolean('enabled')->default(true)->index();
                $table->timestamps();

                $table->index(['tool_id', 'source_type', 'enabled']);
                $table->index(['tool_id', 'verification_status']);
            });
        }

        if (! Schema::hasTable('tool_fact_evidence')) {
            Schema::create('tool_fact_evidence', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
                $table->foreignId('tool_source_id')->nullable()->constrained('tool_sources')->nullOnDelete();
                $table->string('fact_type', 60)->index();
                $table->string('fact_key', 120)->nullable();
                $table->string('verification_status', 24)->default('pending')->index();
                $table->timestamp('verified_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['tool_id', 'fact_type']);
                $table->index(['tool_source_id', 'fact_type']);
            });
        }

        if (! Schema::hasTable('platforms')) {
            Schema::create('platforms', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('slug')->unique();
                $table->string('group', 40)->default('platform')->index();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedSmallInteger('sort_order')->default(0)->index();
                $table->timestamps();
            });

            $canonical = [
                ['Web', 'web', 'access', 10],
                ['Windows', 'windows', 'desktop_os', 20],
                ['macOS', 'macos', 'desktop_os', 30],
                ['Linux', 'linux', 'desktop_os', 40],
                ['Desktop', 'desktop', 'generic_client', 50],
                ['iOS', 'ios', 'mobile_os', 60],
                ['Android', 'android', 'mobile_os', 70],
                ['Mobile App', 'mobile-app', 'generic_client', 80],
                ['Browser Extension', 'browser-extension', 'extension', 90],
                ['Chrome Extension', 'chrome-extension', 'extension', 100],
                ['Firefox Extension', 'firefox-extension', 'extension', 110],
                ['VS Code', 'vs-code', 'ide', 120],
                ['JetBrains', 'jetbrains', 'ide', 130],
                ['CLI', 'cli', 'developer', 140],
                ['API', 'api', 'developer', 150],
                ['Self Hosted', 'self-hosted', 'deployment', 160],
                ['Cloud', 'cloud', 'deployment', 170],
            ];

            $now = now();
            DB::table('platforms')->insert(array_map(fn ($row) => [
                'name' => $row[0],
                'slug' => $row[1] ?: Str::slug($row[0]),
                'group' => $row[2],
                'is_active' => true,
                'sort_order' => $row[3],
                'created_at' => $now,
                'updated_at' => $now,
            ], $canonical));
        }

        if (! Schema::hasTable('platform_tool')) {
            Schema::create('platform_tool', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tool_id')->constrained('tools')->cascadeOnDelete();
                $table->foreignId('platform_id')->constrained('platforms')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['tool_id', 'platform_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_tool');
        Schema::dropIfExists('platforms');
        Schema::dropIfExists('tool_fact_evidence');
        Schema::dropIfExists('tool_sources');
    }
};
