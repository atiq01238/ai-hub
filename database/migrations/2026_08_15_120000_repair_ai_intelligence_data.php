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
        if (Schema::hasTable('news_sources')) {
            DB::table('news_sources')
                ->where('url', 'https://openai.com/blog/rss.xml')
                ->update([
                    'name' => 'OpenAI News',
                    'url' => 'https://openai.com/news/rss.xml',
                    'updated_at' => now(),
                ]);

            DB::table('news_sources')
                ->where('url', 'https://www.theverge.com/ai-artificial-intelligence/rss/index.xml')
                ->update([
                    'name' => 'The Verge',
                    'url' => 'https://www.theverge.com/rss/index.xml',
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('news_items') && Schema::hasColumn('news_items', 'source_url')) {
            try {
                Schema::table('news_items', function (Blueprint $table) {
                    $table->string('source_url', 2048)->nullable()->change();
                });
            } catch (\Throwable) {
                // Some older SQLite builds do not support this alteration.
                // canonical_url already supports 2048 chars, so data remains safe.
            }
        }

        if (
            Schema::hasTable('news_items')
            && Schema::hasColumn('news_items', 'normalized_headline')
            && Schema::hasColumn('news_items', 'content_hash')
        ) {
            DB::table('news_items')
                ->select(['id', 'headline', 'summary', 'normalized_headline', 'content_hash'])
                ->orderBy('id')
                ->chunkById(200, function ($items) {
                    foreach ($items as $item) {
                        $headline = $this->normalize($item->headline);
                        $summary = $this->normalize($item->summary);
                        $content = trim(($headline ?? '') . "\n" . ($summary ?? ''));

                        DB::table('news_items')
                            ->where('id', $item->id)
                            ->update([
                                'normalized_headline' => $headline,
                                'content_hash' => $content !== '' ? hash('sha256', $content) : null,
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        // Data repair migration is intentionally non-destructive on rollback.
    }

    private function normalize(?string $value): ?string
    {
        $value = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = Str::lower(trim($value));
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        return $value !== '' ? Str::limit($value, 255, '') : null;
    }
};
