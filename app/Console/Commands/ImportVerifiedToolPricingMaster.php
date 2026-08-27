<?php

namespace App\Console\Commands;

use App\Models\PricingPlan;
use App\Models\PricingSource;
use App\Models\Tool;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ImportVerifiedToolPricingMaster extends Command
{
    protected $signature = 'pricing:import-tool-master
        {--dry-run : Validate exact catalog matches without writing}
        {--replace-existing : Remove existing pricing plans for matched tools before importing this verified batch}
        {--dataset=master-v1-2026-08-27 : Required verified dataset version}';

    protected $description = 'Import the AI Orbit official-source verified master tool pricing dataset.';

    public function handle(): int
    {
        if ($this->option('dataset') !== 'master-v1-2026-08-27') {
            $this->error('Unsupported dataset. Use --dataset=master-v1-2026-08-27.');
            return self::FAILURE;
        }

        $file = storage_path('app/import-templates/verified-tool-pricing-master-v1-2026-08-27.csv');
        if (! is_file($file)) {
            $this->error('Verified pricing CSV is missing: '.$file);
            return self::FAILURE;
        }

        $rows = $this->csv($file);
        if ($rows === []) {
            $this->error('Verified pricing CSV contains no valid rows.');
            return self::FAILURE;
        }

        $grouped = collect($rows)->groupBy(fn (array $row) => trim((string) $row['tool']));
        $matched = [];
        $missing = [];

        foreach ($grouped as $toolName => $toolRows) {
            $tool = Tool::whereRaw('LOWER(name) = ?', [mb_strtolower($toolName)])->first();
            if ($tool) {
                $matched[$toolName] = $tool;
            } else {
                $missing[] = $toolName;
            }
        }

        $this->info('Verified tool pricing dataset: master-v1-2026-08-27 / 100+ master catalog');
        $this->line('Tools in dataset: '.$grouped->count());
        $this->line('Pricing rows: '.count($rows));
        $this->line('Exact catalog matches: '.count($matched));

        if ($missing) {
            $this->warn('Tools not found (will skip): '.implode(', ', $missing));
        }

        foreach ($grouped as $toolName => $toolRows) {
            $state = isset($matched[$toolName]) ? 'MATCH' : 'SKIP';
            $this->line(sprintf('[%s] %-26s %2d plans', $state, $toolName, $toolRows->count()));
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry run complete. No database changes made.');
            return self::SUCCESS;
        }

        $stats = ['tools' => 0, 'plans' => 0, 'sources' => 0, 'deleted' => 0];

        DB::transaction(function () use ($grouped, $matched, &$stats) {
            foreach ($grouped as $toolName => $toolRows) {
                /** @var Tool|null $tool */
                $tool = $matched[$toolName] ?? null;
                if (! $tool) continue;

                if ($this->option('replace-existing')) {
                    $stats['deleted'] += PricingPlan::where('tool_id', $tool->id)->count();
                    PricingPlan::where('tool_id', $tool->id)->delete();
                }

                $hasFree = false;
                $hasPaid = false;

                foreach ($toolRows as $row) {
                    $monthly = $this->number($row['monthly_price'] ?? null);
                    $yearly = $this->number($row['yearly_price'] ?? null);
                    $billingType = trim((string) ($row['billing_type'] ?? 'subscription')) ?: 'subscription';
                    $verifiedAt = ! empty($row['verified_at']) ? Carbon::parse($row['verified_at']) : now();

                    if ($monthly !== null && abs($monthly) < 0.000001) $hasFree = true;
                    if (($monthly !== null && $monthly > 0) || in_array($billingType, ['usage', 'custom', 'subscription'], true) && $monthly === null) $hasPaid = true;

                    $plan = PricingPlan::updateOrCreate(
                        ['tool_id' => $tool->id, 'plan_name' => $row['plan_name']],
                        [
                            'monthly_price' => $monthly,
                            'yearly_price' => $yearly,
                            'currency' => $row['currency'] ?: 'USD',
                            'billing_type' => $billingType,
                            'billing_unit' => $row['billing_unit'] ?: null,
                            'api_price_label' => $row['api_price_label'] ?: null,
                            'credits' => $row['credits'] ?: null,
                            'limits' => $row['limits'] ?: null,
                            'last_verified_at' => $verifiedAt,
                        ]
                    );
                    $stats['plans']++;

                    PricingSource::updateOrCreate(
                        [
                            'pricing_plan_id' => $plan->id,
                            'metric' => 'monthly_price',
                            'source_url' => $row['source_url'],
                        ],
                        [
                            'source_name' => $row['source_name'] ?: 'Official pricing',
                            'source_type' => 'official',
                            'currency' => $row['currency'] ?: 'USD',
                            'unit' => $row['billing_unit'] ?: 'per month',
                            'enabled' => true,
                            'last_checked_at' => $verifiedAt,
                            'last_check_status' => 'verified',
                            'last_check_message' => 'Verified directly against the official source on '.$verifiedAt->toDateString().'.',
                            'last_detected_value' => $monthly !== null ? (string) $monthly : ($row['api_price_label'] ?: $billingType),
                        ]
                    );
                    $stats['sources']++;
                }

                $pricingModels = [];
                if ($hasFree) $pricingModels[] = 'Free';
                if ($hasPaid) $pricingModels[] = 'Paid';
                if ($pricingModels !== []) {
                    $tool->forceFill(['pricing_models' => $pricingModels])->saveQuietly();
                }

                $stats['tools']++;
            }
        });

        $this->newLine();
        $this->info("Imported verified pricing for {$stats['tools']} tools / {$stats['plans']} plans.");
        $this->info("Attached/updated {$stats['sources']} official pricing sources.");
        if ($this->option('replace-existing')) {
            $this->warn("Removed {$stats['deleted']} pre-existing pricing plan rows before replacement.");
        }

        return self::SUCCESS;
    }

    private function csv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) return [];

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            return [];
        }

        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if (count($values) !== count($header)) continue;
            $row = array_combine($header, $values);
            if (! $row || empty($row['tool']) || empty($row['plan_name']) || empty($row['source_url'])) continue;
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    private function number(?string $value): ?float
    {
        return $value === null || trim($value) === '' ? null : (float) $value;
    }
}
