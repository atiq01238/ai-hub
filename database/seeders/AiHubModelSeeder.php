<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\Company;
use App\Models\Tool;
use Illuminate\Database\Seeder;

class AiHubModelSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['GPT-5.6 Sol','gpt-5-6-sol','OpenAI','ChatGPT','gpt-5-6-sol.png','5.6 Sol','256K',96.8],
            ['GPT-5.6 Terra','gpt-5-6-terra','OpenAI','ChatGPT','gpt-5-6-terra.png','5.6 Terra','256K',95.9],
            ['GPT-5.6 Luna','gpt-5-6-luna','OpenAI','ChatGPT','gpt-5-6-luna.png','5.6 Luna','256K',94.7],
            ['Claude Opus 5','claude-opus-5','Anthropic','Claude','claude-opus-5.png','Opus 5','200K',96.2],
            ['Claude Sonnet 5','claude-sonnet-5','Anthropic','Claude','claude-sonnet-5.png','Sonnet 5','200K',95.1],
            ['Claude Fable 5','claude-fable-5','Anthropic','Claude','claude-fable-5.png','Fable 5','200K',92.8],
            ['Gemini 3.6 Flash','gemini-3-6-flash','Google','Gemini','gemini-3-6-flash.png','3.6 Flash','1M',94.8],
            ['Gemini 3.5 Flash','gemini-3-5-flash','Google','Gemini','gemini-3-5-flash.png','3.5 Flash','1M',93.9],
            ['Gemini 3.5 Flash Lite','gemini-3-5-flash-lite','Google','Gemini','gemini-3-5-flash-lite.png','3.5 Flash Lite','1M',91.8],
            ['Gemini 3.1 Flash Lite','gemini-3-1-flash-lite','Google','Gemini','gemini-3-1-flash-lite.png','3.1 Flash Lite','1M',89.7],
            ['Mistral Medium 3.5','mistral-medium-3-5','Mistral AI','Le Chat','mistral-medium-3-5.png','3.5','128K',91.6],
            ['Mistral Small 4','mistral-small-4','Mistral AI','Le Chat','mistral-small-4.png','4','128K',90.4],
            ['Ministral 3 14B','ministral-3-14b','Mistral AI','Mistral Studio','ministral-3-14b.png','3 14B','128K',87.9],
        ];

        foreach ($rows as $i => [$name,$slug,$companyName,$toolName,$image,$version,$context,$score]) {
            $company = Company::where('name',$companyName)->firstOrFail();
            $tool = Tool::where('name',$toolName)->first();
            AiModel::updateOrCreate(['slug'=>$slug], [
                'company_id'=>$company->id,
                'tool_id'=>$tool?->id,
                'name'=>$name,
                'logo_path'=>'storage/ai-hub/models/'.$image,
                'cover_image_path'=>$tool?->cover_image_path,
                'version'=>$version,
                'release_date'=>now()->subDays(20 + $i * 11)->toDateString(),
                'context_window'=>$context,
                'input_price_per_million'=>round(0.25 + $i * 0.17,2),
                'output_price_per_million'=>round(1.00 + $i * 0.36,2),
                'capabilities'=>['Text Generation','Reasoning','Code Generation','Multimodal'],
                'capability_notes'=>'Seeded model record for validating model cards, media, comparisons and benchmark displays.',
                'benchmark_score'=>$score,
                'benchmarks'=>['Reasoning'=>$score,'Coding'=>round($score-1.9,1),'Instruction Following'=>round($score-1.2,1)],
                'status'=>'active',
            ]);
        }
    }
}
