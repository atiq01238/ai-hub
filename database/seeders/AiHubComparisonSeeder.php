<?php

namespace Database\Seeders;

use App\Models\Comparison;
use App\Models\Tool;
use Illuminate\Database\Seeder;

class AiHubComparisonSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['ChatGPT vs Claude','chatgpt-vs-claude',['ChatGPT','Claude'],18240],
            ['Gemini vs ChatGPT','gemini-vs-chatgpt',['Gemini','ChatGPT'],15310],
            ['Midjourney vs Adobe Firefly','midjourney-vs-adobe-firefly',['Midjourney','Adobe Firefly'],9780],
            ['Runway vs Sora','runway-vs-sora',['Runway','Sora'],11120],
        ] as [$title,$slug,$names,$views]) {
            $ids = Tool::whereIn('name',$names)->get()->sortBy(fn($t)=>array_search($t->name,$names,true))->pluck('id')->values()->all();
            if (count($ids) === 2) {
                Comparison::updateOrCreate(['slug'=>$slug], [
                    'title'=>$title,
                    'comparable_type'=>'tool',
                    'item_ids'=>$ids,
                    'views'=>$views,
                    'status'=>'published',
                ]);
            }
        }
    }
}
