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
        if (Schema::hasTable('tools') && Schema::hasTable('subcategories') && !Schema::hasColumn('tools', 'subcategory_id')) {
            Schema::table('tools', function (Blueprint $table) {
                $table->foreignId('subcategory_id')->nullable()->after('category_id')
                    ->constrained('subcategories')->nullOnDelete();
            });
        }

        if (Schema::hasTable('features') && Schema::hasTable('tools') && !Schema::hasTable('feature_tool')) {
            Schema::create('feature_tool', function (Blueprint $table) {
                $table->id();
                $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
                $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['feature_id', 'tool_id']);
            });
        }

        if (Schema::hasTable('tags') && Schema::hasTable('tools') && !Schema::hasTable('tag_tool')) {
            Schema::create('tag_tool', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
                $table->foreignId('tool_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['tag_id', 'tool_id']);
            });
        }

        if (!Schema::hasTable('tools')) return;

        $subcategories = Schema::hasTable('subcategories')
            ? DB::table('subcategories')->get()->keyBy(fn ($row) => strtolower(trim($row->name)))
            : collect();

        $features = Schema::hasTable('features')
            ? DB::table('features')->get()->keyBy(fn ($row) => strtolower(trim($row->name)))
            : collect();

        $tags = Schema::hasTable('tags')
            ? DB::table('tags')->get()->keyBy(fn ($row) => strtolower(trim($row->name)))
            : collect();

        DB::table('tools')->orderBy('id')->chunkById(100, function ($tools) use (&$subcategories, &$features, &$tags) {
            foreach ($tools as $tool) {
                if (!empty($tool->subcategory) && Schema::hasTable('subcategories')) {
                    $key = strtolower(trim($tool->subcategory));
                    $term = $subcategories->get($key);
                    if (!$term) {
                        $base = Str::slug($tool->subcategory) ?: 'subcategory';
                        $slug = $base; $i = 2;
                        while (DB::table('subcategories')->where('slug', $slug)->exists()) $slug = $base . '-' . $i++;
                        $id = DB::table('subcategories')->insertGetId(['name' => trim($tool->subcategory), 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
                        $term = (object) ['id' => $id, 'name' => trim($tool->subcategory)];
                        $subcategories->put($key, $term);
                    }
                    DB::table('tools')->where('id', $tool->id)->update(['subcategory_id' => $term->id]);
                }

                foreach (json_decode($tool->capabilities ?? '[]', true) ?: [] as $name) {
                    if (!Schema::hasTable('features')) break;
                    $key = strtolower(trim($name));
                    if ($key === '') continue;
                    $term = $features->get($key);
                    if (!$term) {
                        $base = Str::slug($name) ?: 'feature'; $slug = $base; $i = 2;
                        while (DB::table('features')->where('slug', $slug)->exists()) $slug = $base . '-' . $i++;
                        $id = DB::table('features')->insertGetId(['name' => trim($name), 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
                        $term = (object) ['id' => $id, 'name' => trim($name)];
                        $features->put($key, $term);
                    }
                    DB::table('feature_tool')->updateOrInsert(['feature_id' => $term->id, 'tool_id' => $tool->id], ['updated_at' => now(), 'created_at' => now()]);
                }

                foreach (json_decode($tool->tags ?? '[]', true) ?: [] as $name) {
                    if (!Schema::hasTable('tags')) break;
                    $key = strtolower(trim($name));
                    if ($key === '') continue;
                    $term = $tags->get($key);
                    if (!$term) {
                        $base = Str::slug($name) ?: 'tag'; $slug = $base; $i = 2;
                        while (DB::table('tags')->where('slug', $slug)->exists()) $slug = $base . '-' . $i++;
                        $id = DB::table('tags')->insertGetId(['name' => trim($name), 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
                        $term = (object) ['id' => $id, 'name' => trim($name)];
                        $tags->put($key, $term);
                    }
                    DB::table('tag_tool')->updateOrInsert(['tag_id' => $term->id, 'tool_id' => $tool->id], ['updated_at' => now(), 'created_at' => now()]);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_tool');
        Schema::dropIfExists('tag_tool');
        if (Schema::hasTable('tools') && Schema::hasColumn('tools', 'subcategory_id')) {
            Schema::table('tools', function (Blueprint $table) {
                $table->dropConstrainedForeignId('subcategory_id');
            });
        }
    }
};
