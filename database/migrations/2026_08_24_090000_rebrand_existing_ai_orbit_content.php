<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            $siteName = DB::table('settings')->where('key', 'site_name');
            if ($siteName->exists()) {
                $siteName->update(['value' => 'AI Orbit', 'updated_at' => now()]);
            } else {
                DB::table('settings')->insert([
                    'key' => 'site_name',
                    'value' => 'AI Orbit',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // These fields are platform-authored content. User submissions/messages are
        // deliberately excluded so a visitor's original wording is never rewritten.
        $this->replaceBrandInTable('ai_tests', [
            'source_note', 'seo_title', 'meta_description', 'methodology', 'criteria',
        ]);

        $this->replaceBrandInTable('articles', [
            'title', 'content', 'summary', 'seo_title', 'meta_description',
        ]);
    }

    public function down(): void
    {
        // Brand migrations are intentionally one-way: reversing a global text
        // replacement could alter content created after the AI Orbit launch.
    }

    private function replaceBrandInTable(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::table($table)
                ->where($column, 'like', '%AI Hub%')
                ->update([$column => DB::raw("REPLACE(`{$column}`, 'AI Hub', 'AI Orbit')")]);
        }
    }
};
