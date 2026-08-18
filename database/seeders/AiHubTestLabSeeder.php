<?php
namespace Database\Seeders;
use App\Models\AiModel; use App\Models\AiTest; use App\Models\AiTestResult; use Illuminate\Database\Seeder;
class AiHubTestLabSeeder extends Seeder {
 public function run(): void {
  $tests=[
   ['AI Reasoning Challenge','Solve a multi-step reasoning task and explain the final answer concisely.','Reasoning','Accuracy, reasoning quality, prompt adherence and speed','A correct, concise answer with clear reasoning.'],
   ['Production Code Debugging','Find the root cause in a failing backend function, propose the safest fix, and explain the trade-offs.','Coding','Accuracy, code quality, prompt adherence, creativity and speed','A technically correct fix with concise engineering rationale.'],
   ['Long-Context Synthesis','Synthesize a long technical brief into decisions, risks and next actions without losing key constraints.','Text','Quality, factual accuracy, instruction following and speed','A structured synthesis preserving all critical constraints.'],
   ['Creative Product Launch','Write a distinctive launch concept for a new AI productivity product for a professional audience.','Text','Quality, creativity, prompt adherence and usefulness','Original positioning with a clear message and usable campaign direction.'],
   ['Quantitative Logic Test','Solve a constrained quantitative logic problem and return the result in the requested format.','Reasoning','Accuracy, prompt adherence, quality and speed','Correct calculation and exact requested output format.'],
   ['Technical Explanation','Explain a difficult AI concept to a non-technical reader using one analogy and one practical example.','Text','Clarity, accuracy, creativity and prompt adherence','An accessible explanation without sacrificing technical correctness.'],
  ];
  $models=AiModel::whereIn('status',['active','preview'])->orderByDesc('benchmark_score')->take(4)->get();
  foreach($tests as $ti=>$t){$test=AiTest::updateOrCreate(['name'=>$t[0]],['prompt'=>$t[1],'category'=>$t[2],'criteria'=>$t[3],'expected_output'=>$t[4]]);foreach($models as $i=>$m){$base=max(68,96-$i*3-$ti%3);AiTestResult::updateOrCreate(['ai_test_id'=>$test->id,'ai_model_id'=>$m->id],['response_text'=>'Seeded evaluation response for frontend layout and Test Lab workflow validation. Replace this with the recorded model output when running production tests.','score_quality'=>$base,'score_accuracy'=>max(0,$base-1),'score_prompt_adherence'=>max(0,$base-2),'score_creativity'=>max(0,$base-($ti%2?0:3)),'score_speed'=>max(0,$base-4)]);}}
 }
}
