<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Feature;
use App\Models\Subcategory;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AiHubTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'AI Chat', 'AI Image', 'AI Video', 'AI Coding', 'AI Writing',
            'AI Voice', 'AI Music', 'AI Agents', 'AI Search', 'AI Productivity',
        ] as $name) {
            Category::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        foreach ([
            'AI Assistant', 'Image Generator', 'Video Generator', 'Code Assistant',
            'Writing Assistant', 'Voice Generator', 'Research Assistant', 'Developer Platform',
            'Knowledge Assistant', 'Creative Suite',
        ] as $name) {
            Subcategory::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        foreach ([
            'Text Generation', 'Reasoning', 'Web Search', 'Image Generation', 'Video Generation',
            'Code Generation', 'Voice Synthesis', 'Multimodal', 'File Analysis', 'Research',
            'API Access', 'Team Collaboration', 'Prompting', 'Agent Workflows',
        ] as $name) {
            Feature::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }

        foreach ([
            'Popular', 'Editor Pick', 'Free', 'Freemium', 'Paid', 'Chatbot', 'Coding',
            'Image', 'Video', 'Voice', 'Research', 'Productivity', 'Developer', 'Enterprise',
        ] as $name) {
            Tag::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
