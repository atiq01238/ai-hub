<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Feature;
use App\Models\Subcategory;
use App\Models\Tag;
use App\Models\Tool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AiHubToolSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['ChatGPT','chatgpt','OpenAI','AI Chat','AI Assistant','chatgpt.png','chatgpt.jpg','https://chatgpt.com',4.8,98,96.4,['Free','Paid'],['Text Generation','Reasoning','Web Search','Multimodal','File Analysis'],['Popular','Editor Pick','Chatbot'],'A versatile AI assistant for writing, research, coding and multimodal work.'],
            ['Claude','claude','Anthropic','AI Chat','AI Assistant','claude.png','claude.jpg','https://claude.ai',4.7,94,95.8,['Free','Paid'],['Text Generation','Reasoning','File Analysis','Code Generation'],['Popular','Chatbot'],'A focused AI assistant for reasoning, writing, analysis and coding.'],
            ['Gemini','gemini','Google','AI Chat','AI Assistant','gemini.png','gemini.jpg','https://gemini.google.com',4.6,93,94.9,['Free','Paid'],['Text Generation','Multimodal','Reasoning','Web Search'],['Popular','Chatbot'],'Google AI assistant for multimodal help, research and productivity.'],
            ['Midjourney','midjourney','Midjourney','AI Image','Image Generator','midjourney.png','midjourney.jpg','https://midjourney.com',4.7,91,93.7,['Paid'],['Image Generation','Prompting'],['Popular','Image'],'High-quality generative image creation for concept art and visual design.'],
            ['Runway','runway','Runway','AI Video','Video Generator','runway.png','runway.jpg','https://runwayml.com',4.6,89,92.8,['Free','Paid'],['Video Generation','Image Generation','Prompting'],['Popular','Video'],'AI video generation and creative production tools for visual storytellers.'],
            ['Perplexity','perplexity','Perplexity','AI Search','Research Assistant','perplexity.png','perplexity.jpg','https://perplexity.ai',4.6,90,92.4,['Free','Paid'],['Web Search','Research','File Analysis'],['Popular','Research'],'Answer engine designed for cited web research and fast information discovery.'],
            ['ElevenLabs','elevenlabs','ElevenLabs','AI Voice','Voice Generator','elevenlabs.png','elevenlabs.jpg','https://elevenlabs.io',4.7,88,92.0,['Free','Paid'],['Voice Synthesis','Multimodal'],['Popular','Voice'],'AI speech and voice generation platform for media, apps and localization.'],
            ['Adobe Firefly','adobe-firefly','Adobe','AI Image','Creative Suite','adobe-firefly.png','adobe-firefly.jpg','https://firefly.adobe.com',4.5,84,90.8,['Free','Paid'],['Image Generation','Video Generation','Prompting'],['Image','Enterprise'],'Generative creative tools integrated into Adobe workflows.'],
            ['GitHub Copilot','github-copilot','Microsoft','AI Coding','Code Assistant','github-copilot.png','github-copilot.jpg','https://github.com/features/copilot',4.6,92,93.2,['Free','Paid'],['Code Generation','Agent Workflows','Text Generation'],['Popular','Coding','Developer'],'AI coding assistant for code completion, chat and agentic development workflows.'],
            ['Microsoft Copilot','microsoft-copilot','Microsoft','AI Productivity','AI Assistant','microsoft-copilot.png','microsoft-copilot.jpg','https://copilot.microsoft.com',4.4,85,90.1,['Free','Paid'],['Text Generation','Web Search','Multimodal'],['Productivity','Enterprise'],'AI assistant connected to Microsoft productivity and web experiences.'],
            ['Sora','sora','OpenAI','AI Video','Video Generator','sora.png','sora.jpg','https://sora.com',4.5,87,91.7,['Paid'],['Video Generation','Image Generation','Prompting'],['Video','Popular'],'Creative generation tool for AI video and visual storytelling.'],
            ['Google AI Studio','google-ai-studio','Google','AI Coding','Developer Platform','google-ai-studio.png','google-ai-studio.jpg','https://aistudio.google.com',4.5,86,92.1,['Free'],['API Access','Prompting','Multimodal','Code Generation'],['Developer','Free'],'Browser-based workspace for experimenting with Google AI models and APIs.'],
            ['NotebookLM','notebooklm','Google','AI Productivity','Knowledge Assistant','notebooklm.png','notebooklm.jpg','https://notebooklm.google.com',4.6,88,91.9,['Free','Paid'],['Research','File Analysis','Text Generation'],['Research','Productivity'],'Source-grounded AI notebook for understanding documents and research material.'],
            ['Le Chat','le-chat','Mistral AI','AI Chat','AI Assistant','le-chat.png','le-chat.jpg','https://chat.mistral.ai',4.4,80,89.8,['Free','Paid'],['Text Generation','Reasoning','Web Search'],['Chatbot'],'Mistral AI assistant for chat, research, productivity and model access.'],
            ['Mistral Studio','mistral-studio','Mistral AI','AI Coding','Developer Platform','mistral-studio.png','mistral-studio.jpg','https://console.mistral.ai',4.3,76,89.2,['Paid'],['API Access','Prompting','Code Generation'],['Developer'],'Developer platform for building applications with Mistral models.'],
        ];

        $features = Feature::all()->keyBy('name');
        $tags = Tag::all()->keyBy('name');

        foreach ($rows as $i => $r) {
            [$name,$slug,$companyName,$categoryName,$subcategoryName,$logo,$cover,$website,$rating,$popularity,$benchmark,$pricing,$capabilities,$tagNames,$description] = $r;
            $company = Company::where('name',$companyName)->firstOrFail();
            $category = Category::where('name',$categoryName)->firstOrFail();
            $subcategory = Subcategory::where('name',$subcategoryName)->first();

            $tool = Tool::updateOrCreate(['slug'=>$slug], [
                'company_id'=>$company->id,
                'category_id'=>$category->id,
                'subcategory_id'=>$subcategory?->id,
                'subcategory'=>$subcategoryName,
                'name'=>$name,
                'logo_path'=>'storage/ai-hub/tools/logos/'.$logo,
                'cover_image_path'=>'storage/ai-hub/tools/covers/'.$cover,
                'website'=>$website,
                'launch_date'=>Carbon::now()->subYears(2)->subDays($i * 19)->toDateString(),
                'short_description'=>$description,
                'description'=>$description.' This seeded record is intended to exercise the complete AI Hub public UI and admin data model.',
                'pricing_models'=>$pricing,
                'tags'=>$tagNames,
                'capabilities'=>$capabilities,
                'platforms'=>['Web','API'],
                'status'=>'published',
                'rating'=>$rating,
                'popularity'=>$popularity,
                'rating_breakdown'=>['quality'=>min(100,(int)round($rating*20)),'ease_of_use'=>92,'value'=>88,'features'=>94],
                'benchmarks'=>['Quality'=>$benchmark,'Usability'=>round($benchmark-1.8,1),'Value'=>round($benchmark-3.1,1)],
                'benchmark_score'=>$benchmark,
                'seo_title'=>$name.' AI Tool Review, Features & Pricing',
                'meta_description'=>$description,
                'published_at'=>now()->subDays($i),
            ]);

            $tool->featureTerms()->sync(collect($capabilities)->map(fn($n)=>$features->get($n)?->id)->filter()->all());
            $tool->tagTerms()->sync(collect($tagNames)->map(fn($n)=>$tags->get($n)?->id)->filter()->all());
        }
    }
}
