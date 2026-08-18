<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class AiHubCompanySeeder extends Seeder
{
    public function run(): void
    {
        $companies = [
            ['OpenAI','openai','storage/ai-hub/companies/openai.png','https://openai.com',2015],
            ['Anthropic','anthropic','storage/ai-hub/companies/anthropic.png','https://anthropic.com',2021],
            ['Google','google','storage/ai-hub/companies/google.png','https://ai.google',1998],
            ['Midjourney','midjourney','storage/ai-hub/companies/midjourney.png','https://midjourney.com',2022],
            ['Runway','runway','storage/ai-hub/companies/runway.png','https://runwayml.com',2018],
            ['Perplexity','perplexity','storage/ai-hub/companies/perplexity.png','https://perplexity.ai',2022],
            ['ElevenLabs','elevenlabs','storage/ai-hub/companies/elevenlabs.png','https://elevenlabs.io',2022],
            ['Adobe','adobe','storage/ai-hub/companies/adobe.png','https://adobe.com',1982],
            ['Microsoft','microsoft','storage/ai-hub/companies/microsoft.png','https://microsoft.com',1975],
            ['Mistral AI','mistral-ai','storage/ai-hub/companies/mistral-ai.png','https://mistral.ai',2023],
        ];

        foreach ($companies as [$name,$slug,$logo,$website,$founded]) {
            Company::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'logo_path' => $logo,
                'website' => $website,
                'description' => $name . ' develops AI products, models and developer services.',
                'status' => 'active',
                'founded_year' => $founded,
            ]);
        }
    }
}
