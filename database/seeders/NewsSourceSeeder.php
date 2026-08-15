<?php

namespace Database\Seeders;

use App\Models\NewsSource;
use Illuminate\Database\Seeder;

class NewsSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            ['name' => 'OpenAI News', 'url' => 'https://openai.com/news/rss.xml', 'default_category' => 'Product Update'],
            ['name' => 'Google DeepMind', 'url' => 'https://deepmind.google/blog/rss.xml', 'default_category' => 'Research'],
            ['name' => 'TechCrunch AI', 'url' => 'https://techcrunch.com/category/artificial-intelligence/feed/', 'default_category' => 'Breaking News'],
            ['name' => 'VentureBeat AI', 'url' => 'https://venturebeat.com/category/ai/feed/', 'default_category' => 'Breaking News'],
            ['name' => 'The Verge', 'url' => 'https://www.theverge.com/rss/index.xml', 'default_category' => 'Product Update'],
        ];

        foreach ($sources as $source) {
            NewsSource::updateOrCreate(
                ['name' => $source['name']],
                $source + ['type' => 'rss', 'status' => 'active']
            );
        }
    }
}
