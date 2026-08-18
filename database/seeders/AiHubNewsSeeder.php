<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\NewsItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AiHubNewsSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['OpenAI expands advanced reasoning capabilities','OpenAI','Breaking News','openai-reasoning.png',92,'A major model update expands reasoning, coding and multimodal workflows.',['ChatGPT','GPT-5.6 Sol']],
            ['Google rolls out a faster Gemini update','Google','Product Launch','gemini-update.png',86,'A new Gemini update focuses on speed, multimodal interaction and developer workflows.',['Gemini','Google AI Studio']],
            ['New research compares leading AI systems','Google','Research','ai-research.png',78,'Fresh benchmark research highlights where leading AI systems perform differently.',['ChatGPT','Claude','Gemini']],
            ['AI security teams publish new deployment guidance','Microsoft','Security','ai-security.png',82,'New security guidance focuses on safer deployment, evaluation and monitoring of AI systems.',['Microsoft Copilot','GitHub Copilot']],
            ['Runway improves creator workflows for AI video','Runway','Product Launch','ai-research.png',75,'The latest creator workflow update targets faster iteration for generated video projects.',['Runway']],
            ['Anthropic updates Claude for complex knowledge work','Anthropic','New Models','openai-reasoning.png',84,'Claude receives improvements aimed at long-form reasoning, documents and coding tasks.',['Claude']],
        ];

        foreach ($rows as $i => [$headline,$companyName,$category,$image,$importance,$summary,$related]) {
            $company = Company::where('name',$companyName)->first();
            $slug = Str::slug($headline);
            NewsItem::updateOrCreate(['slug'=>$slug], [
                'company_id'=>$company?->id,
                'headline'=>$headline,
                'image_path'=>'storage/ai-hub/news/'.$image,
                'summary'=>$summary,
                'why_it_matters'=>'This development can affect how people compare, choose and deploy AI products.',
                'category'=>$category,
                'ai_topic'=>$category,
                'ai_tags'=>[$category,'AI Update'],
                'ai_summary'=>$summary,
                'ai_why_it_matters'=>'Useful context for evaluating current tools, models and platform capabilities.',
                'ai_confidence'=>90,
                'ai_processor'=>'seed-demo',
                'source'=>$companyName.' Updates',
                'source_url'=>'https://example.com/ai-hub-seed/'.$slug,
                'sentiment'=>'neutral',
                'importance'=>$importance,
                'verification_status'=>'verified',
                'verification_notes'=>'Seeded development content — replace with live-source pipeline content in production.',
                'tags'=>[$category,'AI'],
                'related_tools'=>$related,
                'processing_status'=>'processed',
                'duplicate_status'=>'unique',
                'status'=>'published',
                'published_at'=>now()->subHours(($i+1)*3),
                'fetched_at'=>now()->subHours(($i+1)*3+1),
                'ai_processed_at'=>now()->subHours(($i+1)*3),
                'duplicate_checked_at'=>now()->subHours(($i+1)*3),
            ]);
        }
    }
}
