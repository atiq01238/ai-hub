<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\Comparison;
use App\Models\Tool;
use Illuminate\Database\Seeder;

class AiHubComparisonSeeder extends Seeder
{
    public function run(): void
    {
        $toolComparisons = [
            ['ChatGPT vs Claude','chatgpt-vs-claude',['ChatGPT','Claude'],18240],
            ['Gemini vs ChatGPT','gemini-vs-chatgpt',['Gemini','ChatGPT'],15310],
            ['Midjourney vs Adobe Firefly','midjourney-vs-adobe-firefly',['Midjourney','Adobe Firefly'],9780],
            ['Runway vs Sora','runway-vs-sora',['Runway','Sora'],11120],
            ['Perplexity vs ChatGPT','perplexity-vs-chatgpt',['Perplexity','ChatGPT'],8740],
            ['Claude vs Gemini','claude-vs-gemini',['Claude','Gemini'],8220],
        ];

        foreach ($toolComparisons as [$title,$slug,$names,$views]) {
            $ids = Tool::whereIn('name',$names)->get()->sortBy(fn($t)=>array_search($t->name,$names,true))->pluck('id')->values()->all();
            if (count($ids) === count($names)) {
                Comparison::updateOrCreate(['slug'=>$slug], [
                    'title'=>$title,'comparable_type'=>'tool','item_ids'=>$ids,'views'=>$views,'status'=>'published',
                ]);
            }
        }

        $modelComparisons = [
            ['GPT-5.6 Sol vs Claude Opus 5','gpt-5-6-sol-vs-claude-opus-5',['GPT-5.6 Sol','Claude Opus 5'],13840],
            ['GPT-5.6 Terra vs Claude Sonnet 5','gpt-5-6-terra-vs-claude-sonnet-5',['GPT-5.6 Terra','Claude Sonnet 5'],10620],
            ['Gemini 3.6 Flash vs GPT-5.6 Luna','gemini-3-6-flash-vs-gpt-5-6-luna',['Gemini 3.6 Flash','GPT-5.6 Luna'],9340],
            ['Mistral Small 4 vs Gemini 3.1 Flash Lite','mistral-small-4-vs-gemini-3-1-flash-lite',['Mistral Small 4','Gemini 3.1 Flash Lite'],6240],
        ];

        foreach ($modelComparisons as [$title,$slug,$names,$views]) {
            $ids = AiModel::whereIn('name',$names)->get()->sortBy(fn($m)=>array_search($m->name,$names,true))->pluck('id')->values()->all();
            if (count($ids) === count($names)) {
                Comparison::updateOrCreate(['slug'=>$slug], [
                    'title'=>$title,'comparable_type'=>'model','item_ids'=>$ids,'views'=>$views,'status'=>'published',
                ]);
            }
        }
    }
}
