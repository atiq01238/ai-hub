<?php

namespace App\Console\Commands;

use App\Models\AiModel;
use App\Models\ModelPricingHistory;
use App\Models\ModelPricingSource;
use App\Models\PricingPlan;
use App\Models\PricingSource;
use App\Models\Tool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportVerifiedPricing extends Command
{
    protected $signature = 'pricing:import-verified
        {--dry-run : Validate exact catalog matches without writing}
        {--dataset=v2-2026-08-21 : Required verified dataset version}';
    protected $description = 'Import the bundled official-source verified tool and model pricing dataset.';

    public function handle(): int
    {
        $toolFile = storage_path('app/import-templates/verified-tool-pricing-2026-08-21.csv');
        if ($this->option('dataset') !== 'v2-2026-08-21') {
            $this->error('Refusing import: unsupported/stale dataset version. Use --dataset=v2-2026-08-21.');
            return self::FAILURE;
        }
        $modelFile = storage_path('app/import-templates/verified-model-pricing-v2-2026-08-21.csv');

        if (!is_file($toolFile) || !is_file($modelFile)) {
            $this->error('Verified pricing dataset files are missing.');
            return self::FAILURE;
        }

        $tools = $this->csv($toolFile);
        $models = $this->csv($modelFile);
        $toolMissing = [];
        $modelMissing = [];

        foreach ($tools as $row) {
            if (!Tool::whereRaw('LOWER(name) = ?', [mb_strtolower($row['tool'])])->exists()) $toolMissing[] = $row['tool'];
        }
        foreach ($models as $row) {
            if (!AiModel::whereRaw('LOWER(name) = ?', [mb_strtolower($row['model'])])->exists()) $modelMissing[] = $row['model'];
        }

        $toolMissing = array_values(array_unique($toolMissing));
        $modelMissing = array_values(array_unique($modelMissing));

        $this->info('Verified dataset v2-2026-08-21: '.count($tools).' tool plans + '.count($models).' exact-name model prices.');
        $deprecated = array_values(array_filter($models, fn($r) => ($r['pricing_status'] ?? 'active') !== 'active'));
        if ($deprecated) {
            $this->warn('Non-active model pricing retained for historical labeling only: '.implode(', ', array_column($deprecated, 'model')));
        }
        if ($toolMissing) $this->warn('Tools not found (will skip): '.implode(', ', $toolMissing));
        if ($modelMissing) $this->warn('Models not found (will skip): '.implode(', ', $modelMissing));

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No database changes made.');
            return self::SUCCESS;
        }

        $stats = ['plans'=>0,'model_prices'=>0,'sources'=>0,'history'=>0];

        DB::transaction(function () use ($tools, $models, &$stats) {
            foreach ($tools as $row) {
                $tool = Tool::whereRaw('LOWER(name) = ?', [mb_strtolower($row['tool'])])->first();
                if (!$tool) continue;

                $plan = PricingPlan::updateOrCreate(
                    ['tool_id'=>$tool->id,'plan_name'=>$row['plan_name']],
                    [
                        'monthly_price'=>$this->number($row['monthly_price']),
                        'yearly_price'=>$this->number($row['yearly_price']),
                        'currency'=>$row['currency'] ?: 'USD',
                        'billing_type'=>$row['billing_type'] ?: 'subscription',
                        'billing_unit'=>$row['billing_unit'] ?: null,
                        'api_price_label'=>$row['api_price_label'] ?: null,
                        'limits'=>$row['limits'] ?: null,
                        'last_verified_at'=>now(),
                    ]
                );
                $stats['plans']++;

                PricingSource::updateOrCreate(
                    ['pricing_plan_id'=>$plan->id,'metric'=>'monthly_price','source_url'=>$row['source_url']],
                    [
                        'source_name'=>$row['source_name'] ?: 'Official pricing',
                        'source_type'=>'auto','currency'=>$row['currency'] ?: 'USD',
                        'unit'=>$row['billing_unit'] ?: 'per month','enabled'=>true,
                        'last_checked_at'=>now(),'last_check_status'=>'verified',
                        'last_check_message'=>'Initial official-source dataset verification (2026-08-21).',
                    ]
                );
                $stats['sources']++;
            }

            foreach ($models as $row) {
                $model = AiModel::whereRaw('LOWER(name) = ?', [mb_strtolower($row['model'])])->first();
                if (!$model) continue;
                if (($row['pricing_status'] ?? 'active') !== 'active') {
                    $this->warn('Skipped live update for non-active model: '.$row['model'].' ('.$row['pricing_status'].')');
                    continue;
                }

                foreach ([
                    'input_price_per_million'=>$this->number($row['input_price_per_million']),
                    'output_price_per_million'=>$this->number($row['output_price_per_million']),
                ] as $metric=>$new) {
                    $old = $model->{$metric};
                    if ($new !== null && ($old === null || abs((float)$old-$new) > 0.000001)) {
                        ModelPricingHistory::create([
                            'ai_model_id'=>$model->id,'metric'=>$metric,'old_value'=>$old,'new_value'=>$new,
                            'currency'=>$row['currency'] ?: 'USD','unit'=>$row['unit'] ?: 'per 1M tokens',
                            'source_url'=>$row['source_url'],'change_type'=>$old===null?'initial_verified':'verified_update',
                            'verified_at'=>$row['verified_at'] ?: now(),
                        ]);
                        $stats['history']++;
                    }
                    $model->forceFill([$metric=>$new])->saveQuietly();

                    ModelPricingSource::updateOrCreate(
                        ['ai_model_id'=>$model->id,'metric'=>$metric,'source_url'=>$row['source_url']],
                        [
                            'source_name'=>$row['source_name'] ?: 'Official pricing','source_type'=>'auto',
                            'currency'=>$row['currency'] ?: 'USD','unit'=>$row['unit'] ?: 'per 1M tokens',
                            'enabled'=>true,'last_checked_at'=>now(),'last_check_status'=>'verified',
                            'last_check_message'=>'Initial official-source dataset verification (2026-08-21).',
                            'last_detected_value'=>$new,
                        ]
                    );
                    $stats['sources']++;
                }
                $stats['model_prices']++;
            }
        });

        $this->newLine();
        $this->info("Imported {$stats['plans']} tool plans and {$stats['model_prices']} model price records.");
        $this->info("Attached/updated {$stats['sources']} official source monitors; {$stats['history']} model history entries recorded.");
        return self::SUCCESS;
    }

    private function csv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if (count($values) !== count($header)) continue;
            $rows[] = array_combine($header, $values);
        }
        fclose($handle);
        return $rows;
    }

    private function number(?string $value): ?float
    {
        return $value === null || trim($value) === '' ? null : (float)$value;
    }
}
