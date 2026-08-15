<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('benchmarks', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('category')->default('General');
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(1);
            $table->decimal('max_score', 8, 2)->default(100);
            $table->boolean('higher_is_better')->default(true);
            $table->string('official_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('benchmark_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benchmark_id')->constrained()->cascadeOnDelete();
            $table->morphs('benchmarkable');
            $table->decimal('score', 10, 2);
            $table->date('tested_at')->nullable()->index();
            $table->string('source_name')->nullable();
            $table->string('source_url')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('verified')->default(false)->index();
            $table->timestamps();
            $table->index(['benchmark_id','benchmarkable_type','benchmarkable_id'], 'benchmark_result_lookup');
        });

        $now = now();
        $defaults = [
            ['MMLU Pro','Knowledge & Reasoning',1.20],
            ['HumanEval','Coding',1.15],
            ['GPQA Diamond','Reasoning',1.20],
            ['MATH','Mathematics',1.00],
            ['SWE-bench','Software Engineering',1.25],
        ];
        foreach ($defaults as [$name,$category,$weight]) {
            DB::table('benchmarks')->insert([
                'name'=>$name,'slug'=>Str::slug($name),'category'=>$category,'weight'=>$weight,
                'max_score'=>100,'higher_is_better'=>true,'is_active'=>true,'created_at'=>$now,'updated_at'=>$now,
            ]);
        }

        // Bring the current JSON scores into the new history table once, so existing
        // benchmark data is not lost. Imported rows remain unverified until reviewed.
        $importLegacy = function (string $table, string $morphClass): void {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'benchmarks')) return;
            foreach (DB::table($table)->select('id','benchmarks')->whereNotNull('benchmarks')->get() as $item) {
                $scores = json_decode($item->benchmarks, true);
                if (!is_array($scores)) continue;
                foreach ($scores as $name => $score) {
                    if (!is_string($name) || !is_numeric($score)) continue;
                    $benchmarkId = DB::table('benchmarks')->where('name',$name)->value('id');
                    if (!$benchmarkId) {
                        $benchmarkId = DB::table('benchmarks')->insertGetId([
                            'name'=>$name,'slug'=>Str::slug($name).'-'.Str::lower(Str::random(5)),
                            'category'=>'Legacy','weight'=>1,'max_score'=>100,'higher_is_better'=>true,
                            'is_active'=>true,'created_at'=>now(),'updated_at'=>now(),
                        ]);
                    }
                    DB::table('benchmark_results')->insert([
                        'benchmark_id'=>$benchmarkId,'benchmarkable_type'=>$morphClass,'benchmarkable_id'=>$item->id,
                        'score'=>(float)$score,'tested_at'=>null,'source_name'=>'Legacy import','source_url'=>null,
                        'notes'=>'Imported from the previous JSON benchmark field.','verified'=>false,
                        'created_at'=>now(),'updated_at'=>now(),
                    ]);
                }
            }
        };

        $importLegacy('ai_models', 'App\\Models\\AiModel');
        $importLegacy('tools', 'App\\Models\\Tool');
    }

    public function down(): void
    {
        Schema::dropIfExists('benchmark_results');
        Schema::dropIfExists('benchmarks');
    }
};
