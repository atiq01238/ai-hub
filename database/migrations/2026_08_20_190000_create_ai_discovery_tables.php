<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovery_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_source_id')->unique()->constrained('news_sources')->cascadeOnDelete();
            $table->boolean('enabled')->default(true)->index();
            $table->boolean('trusted')->default(false)->index();
            $table->boolean('detect_tools')->default(true);
            $table->boolean('detect_models')->default(true);
            $table->unsignedTinyInteger('minimum_confidence')->default(55);
            $table->timestamp('last_discovery_at')->nullable();
            $table->unsignedInteger('discoveries_count')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_discoveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_item_id')->nullable()->unique()->constrained('news_items')->nullOnDelete();
            $table->foreignId('news_source_id')->nullable()->constrained('news_sources')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('matched_tool_id')->nullable()->constrained('tools')->nullOnDelete();
            $table->foreignId('matched_model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->string('entity_type', 30)->index(); // tool, model, tool_update, model_update
            $table->string('candidate_name');
            $table->string('headline');
            $table->text('summary')->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->unsignedTinyInteger('confidence')->default(0)->index();
            $table->string('status', 30)->default('pending')->index(); // pending, approved, merged, ignored
            $table->json('signals')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'status']);
            $table->index(['company_id', 'status']);
        });

        if (Schema::hasTable('news_sources')) {
            $now = now();
            $rows = DB::table('news_sources')->select('id', 'company_id', 'name')->get()->map(function ($source) use ($now) {
                $name = strtolower((string) $source->name);
                $trusted = !empty($source->company_id)
                    || str_contains($name, 'openai')
                    || str_contains($name, 'deepmind')
                    || str_contains($name, 'anthropic')
                    || str_contains($name, 'mistral')
                    || str_contains($name, 'meta');

                return [
                    'news_source_id' => $source->id,
                    'enabled' => true,
                    'trusted' => $trusted,
                    'detect_tools' => true,
                    'detect_models' => true,
                    'minimum_confidence' => $trusted ? 50 : 60,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            if ($rows !== []) {
                DB::table('discovery_sources')->insert($rows);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_discoveries');
        Schema::dropIfExists('discovery_sources');
    }
};
