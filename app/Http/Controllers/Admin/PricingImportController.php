<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use App\Models\PricingSource;
use App\Models\Tool;
use App\Services\Imports\ImportPreviewStore;
use App\Services\Imports\SpreadsheetReader;
use App\Services\Tools\ToolCommercialProfileService;
use App\Services\Tools\ToolSourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Throwable;

class PricingImportController extends Controller
{
    public function template()
    {
        $path = storage_path('app/import-templates/ai-hub-pricing-import.csv');
        abort_unless(File::exists($path), 404);
        return response()->download($path, 'ai-hub-pricing-import.csv');
    }

    public function preview(Request $request, SpreadsheetReader $reader, ImportPreviewStore $store)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240']]);
        try {
            $rows = $reader->read($request->file('file'));
        } catch (Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $tools = Tool::get(['id','name'])->keyBy(fn($t)=>mb_strtolower(trim($t->name)));
        $preview = [];
        $seen = [];

        foreach ($rows as $r) {
            $n = $this->normalize($r);
            $errors = [];
            $tool = $tools->get(mb_strtolower($n['tool']));

            if ($n['tool'] === '' || ! $tool) $errors[] = 'Tool not found in database: '.$n['tool'];
            if ($n['plan_name'] === '') $errors[] = 'Plan name is required.';
            foreach (['monthly_price','yearly_price'] as $field) {
                if ($n[$field] !== null && $n[$field] < 0) $errors[] = 'Price cannot be negative.';
            }
            if ($n['source_url'] !== '' && ! filter_var($n['source_url'], FILTER_VALIDATE_URL)) $errors[] = 'Source URL is invalid.';
            if (! in_array($n['metric'], ['monthly_price','yearly_price','api_price_label'], true)) $errors[] = 'Metric is invalid.';
            if (! in_array($n['billing_type'], ['subscription','per_seat','usage','one_time','custom','included'], true)) $errors[] = 'Billing type is invalid.';

            $key = mb_strtolower($n['tool'].'|'.$n['plan_name']);
            if (isset($seen[$key])) $errors[] = 'Duplicate pricing plan inside file.';
            $seen[$key] = true;

            $existing = $tool
                ? PricingPlan::where('tool_id',$tool->id)->where('plan_name',$n['plan_name'])->first()
                : null;

            $preview[] = $n + [
                'tool_id' => $tool?->id,
                'tool_match' => $tool?->name,
                'existing_id' => $existing?->id,
                'errors' => array_values(array_unique($errors)),
                'state' => $errors ? 'invalid' : ($existing ? 'existing' : 'ready'),
            ];
        }

        $token = $store->put('pricing', $request->user()->id, $preview);
        $stats = $this->stats($preview);
        return view('data-import.pricing-preview', compact('preview','stats','token'));
    }

    public function commit(
        Request $request,
        ImportPreviewStore $store,
        ToolCommercialProfileService $commercialProfile,
        ToolSourceService $sourceService,
    ) {
        $data = $request->validate([
            'token' => ['required','string','size:40'],
            'existing_action' => ['required', Rule::in(['skip','update'])],
        ]);

        $payload = $store->get($data['token'], $request->user()->id, 'pricing');
        $created = $updated = $skipped = $invalid = 0;
        $affectedToolIds = [];

        DB::transaction(function () use ($payload, $data, $request, $commercialProfile, $sourceService, &$created, &$updated, &$skipped, &$invalid, &$affectedToolIds) {
            foreach ($payload['rows'] ?? [] as $row) {
                if (($row['state'] ?? '') === 'invalid' || ! empty($row['errors'])) {
                    $invalid++;
                    continue;
                }

                $values = [
                    'tool_id' => $row['tool_id'],
                    'plan_name' => $row['plan_name'],
                    'monthly_price' => $row['monthly_price'],
                    'yearly_price' => $row['yearly_price'],
                    'currency' => $row['currency'] ?: 'USD',
                    'billing_type' => $row['billing_type'] ?: 'subscription',
                    'billing_unit' => $row['billing_unit'] ?: null,
                    'api_price_label' => $row['api_price_label'] ?: null,
                    'credits' => $row['credits'] ?: null,
                    'limits' => $row['limits'] ?: null,
                    'last_verified_at' => now(),
                ];

                $plan = PricingPlan::where('tool_id',$row['tool_id'])->where('plan_name',$row['plan_name'])->first();
                if ($plan) {
                    if ($data['existing_action'] === 'skip') {
                        $skipped++;
                        continue;
                    }
                    $plan->update($values);
                    $updated++;
                } else {
                    $plan = PricingPlan::create($values);
                    $created++;
                }

                $affectedToolIds[(int) $row['tool_id']] = true;

                if (! empty($row['source_url'])) {
                    PricingSource::updateOrCreate(
                        [
                            'pricing_plan_id' => $plan->id,
                            'metric' => $row['metric'],
                            'source_url' => $row['source_url'],
                        ],
                        [
                            'source_name' => $row['source_name'] ?: 'Official pricing',
                            'source_type' => 'auto',
                            'currency' => $row['currency'] ?: 'USD',
                            'unit' => $row['unit'] ?: null,
                            'enabled' => true,
                            'last_checked_at' => now(),
                            'last_check_status' => 'verified_import',
                        ]
                    );

                    $tool = Tool::find($row['tool_id']);
                    if ($tool) {
                        $sourceService->upsert(
                            tool: $tool,
                            url: $row['source_url'],
                            sourceType: 'official_pricing',
                            verificationStatus: 'verified',
                            sourceName: $row['source_name'] ?: $tool->name.' official pricing',
                            verifiedAt: now(),
                            verifiedBy: $request->user()?->id,
                            factType: 'pricing',
                            factKey: 'commercial_profile',
                        );
                    }
                }
            }

            foreach (array_keys($affectedToolIds) as $toolId) {
                if ($tool = Tool::find($toolId)) $commercialProfile->refresh($tool);
            }
        });

        $store->forget($data['token']);
        return redirect()->route('admin.pricing.index')->with('status', "Pricing import complete: {$created} created, {$updated} updated, {$skipped} existing skipped, {$invalid} invalid skipped. Derived Free/Paid/Usage/Enterprise tool classification refreshed for ".count($affectedToolIds).' affected tools.');
    }

    private function normalize(array $row): array
    {
        $number = fn ($value) => trim((string) $value) === '' ? null : (float) $value;

        return [
            'row_number' => (int) ($row['row_number'] ?? 0),
            'tool' => trim((string) ($row['tool'] ?? '')),
            'plan_name' => trim((string) ($row['plan_name'] ?? '')),
            'monthly_price' => $number($row['monthly_price'] ?? ''),
            'yearly_price' => $number($row['yearly_price'] ?? ''),
            'api_price_label' => trim((string) ($row['api_price_label'] ?? '')),
            'credits' => trim((string) ($row['credits'] ?? '')),
            'limits' => trim((string) ($row['limits'] ?? '')),
            'metric' => mb_strtolower(trim((string) ($row['metric'] ?? 'monthly_price'))) ?: 'monthly_price',
            'source_name' => trim((string) ($row['source_name'] ?? '')),
            'source_url' => trim((string) ($row['source_url'] ?? '')),
            'currency' => strtoupper(trim((string) ($row['currency'] ?? 'USD'))) ?: 'USD',
            'billing_type' => mb_strtolower(trim((string) ($row['billing_type'] ?? 'subscription'))) ?: 'subscription',
            'billing_unit' => trim((string) ($row['billing_unit'] ?? '')),
            'unit' => trim((string) ($row['unit'] ?? '')),
        ];
    }

    private function stats(array $rows): array
    {
        return [
            'total' => count($rows),
            'ready' => collect($rows)->where('state','ready')->count(),
            'existing' => collect($rows)->where('state','existing')->count(),
            'invalid' => collect($rows)->where('state','invalid')->count(),
        ];
    }
}
