<?php

namespace App\Console\Commands;

use App\Models\PricingHistory;
use App\Models\PricingPlan;
use App\Models\Tool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairVerifiedToolPricing extends Command
{
    protected $signature = 'pricing:repair-verified-tool
        {tool : Exact tool name from the catalog}
        {--dry-run : Show what would change without writing}';

    protected $description = 'Restore one tool pricing plans from the bundled verified pricing dataset.';

    public function handle(): int
    {
        $file = storage_path('app/import-templates/verified-tool-pricing-2026-08-21.csv');
        if (! is_file($file)) {
            $this->error('Verified tool pricing dataset is missing.');
            return self::FAILURE;
        }

        $toolName = trim((string) $this->argument('tool'));
        $tool = Tool::whereRaw('LOWER(name) = ?', [mb_strtolower($toolName)])->first();
        if (! $tool) {
            $this->error("Tool not found: {$toolName}");
            return self::FAILURE;
        }

        $rows = array_values(array_filter($this->csv($file), fn (array $row) =>
            mb_strtolower(trim((string) ($row['tool'] ?? ''))) === mb_strtolower($tool->name)
        ));

        if ($rows === []) {
            $this->error("No verified pricing rows found for {$tool->name}.");
            return self::FAILURE;
        }

        $this->info("Verified repair preview for {$tool->name}: ".count($rows).' plans.');
        $changes = [];

        foreach ($rows as $row) {
            $plan = PricingPlan::where('tool_id', $tool->id)->where('plan_name', $row['plan_name'])->first();
            $oldMonthly = $plan?->monthly_price;
            $newMonthly = $this->number($row['monthly_price'] ?? null);
            $oldYearly = $plan?->yearly_price;
            $newYearly = $this->number($row['yearly_price'] ?? null);

            $this->line(sprintf(
                '%-14s monthly: %-10s -> %-10s yearly: %-10s -> %-10s',
                $row['plan_name'],
                $oldMonthly === null ? 'null' : (string) $oldMonthly,
                $newMonthly === null ? 'null' : (string) $newMonthly,
                $oldYearly === null ? 'null' : (string) $oldYearly,
                $newYearly === null ? 'null' : (string) $newYearly,
            ));

            $changes[] = compact('row', 'plan', 'oldMonthly', 'newMonthly', 'oldYearly', 'newYearly');
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run complete. No database changes made.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($tool, $changes) {
            foreach ($changes as $item) {
                $row = $item['row'];
                $plan = $item['plan'] ?: new PricingPlan(['tool_id' => $tool->id, 'plan_name' => $row['plan_name']]);

                foreach ([
                    'monthly_price' => [$item['oldMonthly'], $item['newMonthly']],
                    'yearly_price' => [$item['oldYearly'], $item['newYearly']],
                ] as $metric => [$old, $new]) {
                    if ($this->same($old, $new)) {
                        continue;
                    }

                    PricingHistory::create([
                        'tool_id' => $tool->id,
                        'plan_name' => $row['plan_name'],
                        'metric' => $metric,
                        'old_value' => $old,
                        'new_value' => $new,
                        'old_price' => $old,
                        'new_price' => $new,
                        'change_type' => $this->historyType($old, $new),
                        'source_url' => $row['source_url'] ?? null,
                    ]);
                }

                $plan->forceFill([
                    'tool_id' => $tool->id,
                    'plan_name' => $row['plan_name'],
                    'monthly_price' => $item['newMonthly'],
                    'yearly_price' => $item['newYearly'],
                    'currency' => $row['currency'] ?: 'USD',
                    'billing_type' => $row['billing_type'] ?: 'subscription',
                    'billing_unit' => $row['billing_unit'] ?: null,
                    'api_price_label' => $row['api_price_label'] ?: null,
                    'limits' => $row['limits'] ?: null,
                    'last_verified_at' => now(),
                ])->saveQuietly();
            }
        });

        $this->info("Restored {$tool->name} pricing from the bundled verified dataset.");
        return self::SUCCESS;
    }

    private function csv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if (count($values) === count($header)) {
                $rows[] = array_combine($header, $values);
            }
        }
        fclose($handle);
        return $rows;
    }

    private function number(?string $value): ?float
    {
        return $value === null || trim($value) === '' ? null : (float) $value;
    }

    private function same(mixed $a, mixed $b): bool
    {
        if ($a === null && $b === null) return true;
        if ($a === null || $b === null) return false;
        return abs((float) $a - (float) $b) < 0.00001;
    }

    private function historyType(mixed $old, mixed $new): string
    {
        if ($old === null && $new !== null) return 'new_plan';
        if ($old !== null && $new === null) return 'removed_plan';
        return (float) $new >= (float) $old ? 'increase' : 'decrease';
    }
}
