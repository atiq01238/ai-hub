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
            ['Mistral expands its model lineup for developer workloads','Mistral AI','New Models','gemini-update.png',79,'A refreshed Mistral lineup targets coding, structured generation and efficient enterprise deployment.',['Le Chat','Mistral Studio']],
            ['Adobe adds new generative controls to creative AI workflows','Adobe','Product Launch','ai-research.png',74,'Adobe is expanding creative controls for teams using generative image and design workflows.',['Adobe Firefly']],
            ['Perplexity improves source-first research experiences','Perplexity','Product Launch','ai-research.png',77,'The research experience adds workflow improvements intended to make source discovery and synthesis faster.',['Perplexity']],
            ['Microsoft updates Copilot governance controls for organizations','Microsoft','Security','ai-security.png',81,'New administrative controls focus on governance, access policy and safer AI deployment across organizations.',['Microsoft Copilot']],
            ['AI benchmark methodology gets a reproducibility refresh','Google','Research','ai-research.png',76,'Researchers are refining evaluation practices to make model comparisons more reproducible and easier to audit.',['Gemini','ChatGPT','Claude']],
            ['OpenAI adjusts developer platform pricing for selected workloads','OpenAI','Pricing Change','openai-reasoning.png',88,'A pricing update changes the cost profile for selected API workloads and may affect production deployment decisions.',['ChatGPT','GPT-5.6 Sol']],
            ['Anthropic publishes new enterprise safety recommendations','Anthropic','Security','ai-security.png',80,'The guidance focuses on evaluation, permissions and monitoring for organizations deploying advanced AI systems.',['Claude']],
            ['Google AI Studio adds workflow improvements for multimodal builders','Google','Product Launch','gemini-update.png',73,'Developer tooling improvements make it easier to prototype and test multimodal applications.',['Google AI Studio','Gemini']],
            ['Runway research highlights progress in controllable video generation','Runway','Research','ai-research.png',83,'New research explores stronger control over motion, consistency and direction in generated video.',['Runway']],
        ];

        foreach ($rows as $i => [$headline,$companyName,$category,$image,$importance,$summary,$related]) {
            $company = Company::where('name',$companyName)->first();
            $slug = Str::slug($headline);
            NewsItem::updateOrCreate(['slug'=>$slug], [
                'company_id'=>$company?->id,
                'headline'=>$headline,
                'image_path'=>'storage/ai-hub/news/'.$image,
                'summary'=>$summary,
                'why_it_matters'=>'This development can affect how people compare, choose and deploy AI products, so it is useful to evaluate the change alongside pricing, benchmarks and product capabilities.',
                'category'=>$category,
                'ai_topic'=>$category,
                'ai_tags'=>[$category,'AI Update'],
                'ai_summary'=>$summary,
                'ai_why_it_matters'=>'Useful context for evaluating current tools, models, platform capabilities and deployment tradeoffs.',
                'ai_confidence'=>90,
                'ai_processor'=>'seed-demo',
                'source'=>$companyName.' Updates',
                'source_url'=>'https://example.com/ai-hub-seed/'.$slug,
                'sentiment'=>'neutral',
                'importance'=>$importance,
                'verification_status'=>'verified',
                'verification_notes'=>'Seeded development content for UI testing — replace with live-source pipeline content in production.',
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
