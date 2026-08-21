<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetectedPriceChange;
use App\Models\PricingHistory;
use App\Models\PricingPlan;
use App\Models\PricingSource;
use App\Models\Tool;
use App\Services\Pricing\PricingDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PricingController extends Controller
{
    public function index(Request $request)
    {
        return $this->plansIndex($request);
    }

    public function api(Request $request)
    {
        return $this->plansIndex($request, onlyApi: true);
    }

    public function create()
    {
        $tools = Tool::orderBy('name')->get();
        return view('pricing.form', compact('tools'));
    }

    public function store(Request $request)
    {
        $plan = PricingPlan::create($this->fromRequest($request));

        return redirect()->route('admin.pricing.sources', $plan->id)
            ->with('status', 'Pricing plan created. Add an official source to enable automatic detection.');
    }

    public function edit(int $id)
    {
        $plan = PricingPlan::findOrFail($id);
        $tools = Tool::orderBy('name')->get();
        return view('pricing.form', compact('plan', 'tools'));
    }

    public function update(Request $request, int $id)
    {
        $plan = PricingPlan::findOrFail($id);
        $plan->update($this->fromRequest($request));

        return redirect()->route('admin.pricing.index')->with('status', 'Pricing plan updated.');
    }

    public function destroy(int $id)
    {
        PricingPlan::findOrFail($id)->delete();
        return redirect()->route('admin.pricing.index')->with('status', 'Pricing plan removed.');
    }

    public function sources(int $id)
    {
        $plan = PricingPlan::with(['tool', 'sources'])->findOrFail($id);
        return view('pricing.sources', compact('plan'));
    }

    public function storeSource(Request $request, int $id)
    {
        $plan = PricingPlan::findOrFail($id);

        $data = $request->validate([
            'metric' => ['required', 'in:monthly_price,yearly_price,api_price_label'],
            'source_name' => ['nullable', 'string', 'max:120'],
            'source_url' => ['required', 'url', 'max:2000'],
            'source_type' => ['required', 'in:auto,regex,json_path'],
            'extraction_rule' => ['nullable', 'string', 'max:4000'],
            'currency' => ['nullable', 'string', 'max:10'],
            'unit' => ['nullable', 'string', 'max:120'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        if (in_array($data['source_type'], ['regex', 'json_path'], true) && empty($data['extraction_rule'])) {
            return back()->withErrors(['extraction_rule' => 'Extraction rule is required for Regex and JSON Path sources.'])->withInput();
        }

        $plan->sources()->create($data + [
            'currency' => $data['currency'] ?: 'USD',
            'enabled' => $request->boolean('enabled', true),
        ]);

        return back()->with('status', 'Automatic pricing source added.');
    }

    public function destroySource(int $id, int $sourceId)
    {
        $plan = PricingPlan::findOrFail($id);
        $source = $plan->sources()->findOrFail($sourceId);
        $source->delete();

        return back()->with('status', 'Pricing source removed.');
    }

    public function checkSource(int $id, int $sourceId, PricingDetectionService $service)
    {
        $plan = PricingPlan::findOrFail($id);
        $source = $plan->sources()->findOrFail($sourceId);
        $stats = $service->scan($source->id, null);

        $message = $stats['failed'] > 0
            ? 'Source check failed. See the source status for details.'
            : ($stats['changes'] > 0 ? 'Change detected and sent to review.' : 'Source checked successfully; no change detected.');

        return back()->with($stats['failed'] > 0 ? 'error' : 'status', $message);
    }

    public function changes(Request $request)
    {
        $status = $request->query('status', 'pending');
        $allowed = ['pending', 'approved', 'rejected', 'all'];
        if (! in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $changes = DetectedPriceChange::with(['tool', 'plan', 'source', 'reviewer'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('detected_at')
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'pending' => DetectedPriceChange::where('status', 'pending')->count(),
            'approved' => DetectedPriceChange::where('status', 'approved')->count(),
            'rejected' => DetectedPriceChange::where('status', 'rejected')->count(),
        ];

        return view('pricing.changes', compact('changes', 'counts', 'status'));
    }

    public function runDetection(PricingDetectionService $service)
    {
        $stats = $service->scan();

        return back()->with(
            'status',
            "Pricing scan complete: {$stats['checked']} checked, {$stats['changes']} change(s), {$stats['unchanged']} unchanged, {$stats['failed']} failed."
        );
    }

    public function approveChange(Request $request, int $id)
    {
        $change = DetectedPriceChange::with(['plan', 'tool'])->findOrFail($id);
        abort_unless($change->status === 'pending', 422, 'This change has already been reviewed.');

        $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);

        DB::transaction(function () use ($change, $request) {
            $plan = $change->plan;
            $old = $plan->{$change->metric};
            $new = $this->castDetectedValue($change->metric, $change->detected_value);

            $plan->forceFill([$change->metric => $new])->saveQuietly();

            $numeric = in_array($change->metric, ['monthly_price', 'yearly_price'], true);
            PricingHistory::create([
                'tool_id' => $plan->tool_id,
                'plan_name' => $plan->plan_name,
                'metric' => $change->metric,
                'old_value' => $old,
                'new_value' => $new,
                'old_price' => $numeric && $old !== null ? $old : null,
                'new_price' => $numeric && $new !== null ? $new : null,
                'change_type' => $this->historyType($old, $new, $numeric),
                'source_url' => $change->source_url,
                'detected_change_id' => $change->id,
            ]);

            $change->update([
                'status' => 'approved',
                'review_note' => $request->input('review_note'),
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
        });

        return back()->with('status', 'Detected price approved and published to the pricing plan.');
    }

    public function rejectChange(Request $request, int $id)
    {
        $change = DetectedPriceChange::findOrFail($id);
        abort_unless($change->status === 'pending', 422, 'This change has already been reviewed.');

        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:1000']]);
        $change->update([
            'status' => 'rejected',
            'review_note' => $data['review_note'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        return back()->with('status', 'Detected price rejected. Live pricing was not changed.');
    }

    public function history(Request $request)
    {
        $toolId = $request->query('tool_id');
        $planName = $request->query('plan_name');
        $metric = $request->query('metric', 'monthly_price');
        $tools = Tool::orderBy('name')->get();
        $selectedTool = $toolId ? Tool::find($toolId) : $tools->first();

        if (! $toolId && $selectedTool) {
            $toolId = $selectedTool->id;
        }

        $planNames = $selectedTool
            ? PricingPlan::where('tool_id', $selectedTool->id)->orderBy('plan_name')->pluck('plan_name')
            : collect();
        $selectedPlanName = $planName ?: $planNames->first();

        $changes = PricingHistory::with('tool')
            ->when($toolId, fn ($q) => $q->where('tool_id', $toolId))
            ->when($selectedPlanName, fn ($q) => $q->where('plan_name', $selectedPlanName))
            ->when($metric, fn ($q) => $q->where('metric', $metric))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $timeline = ($selectedTool && $selectedPlanName)
            ? PricingHistory::where('tool_id', $selectedTool->id)
                ->where('plan_name', $selectedPlanName)
                ->where('metric', $metric)
                ->whereNotNull('new_price')
                ->oldest()
                ->get(['new_price', 'created_at'])
            : collect();

        return view('pricing.history', compact(
            'changes', 'tools', 'selectedTool', 'timeline', 'planNames', 'selectedPlanName', 'metric'
        ));
    }

    private function plansIndex(Request $request, bool $onlyApi = false)
    {
        $query = PricingPlan::with(['tool', 'sources'])
            ->withCount(['detectedChanges as pending_changes_count' => fn ($q) => $q->where('status', 'pending')]);

        if ($onlyApi) {
            $query->whereNotNull('api_price_label');
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('plan_name', 'like', "%{$search}%")
                    ->orWhereHas('tool', fn ($tool) => $tool->where('name', 'like', "%{$search}%"));
            });
        }

        $plans = $query->latest('updated_at')->paginate(20)->withQueryString();
        return view('pricing.index', compact('plans', 'onlyApi'));
    }

    private function fromRequest(Request $request): array
    {
        return $request->validate([
            'tool_id' => ['required', 'exists:tools,id'],
            'plan_name' => ['required', 'string', 'max:100'],
            'currency' => ['required', 'string', 'max:10'],
            'billing_type' => ['required', 'in:subscription,per_seat,usage,one_time,custom'],
            'billing_unit' => ['nullable', 'string', 'max:80'],
            'monthly_price' => ['nullable', 'numeric', 'min:0'],
            'yearly_price' => ['nullable', 'numeric', 'min:0'],
            'api_price_label' => ['nullable', 'string', 'max:120'],
            'credits' => ['nullable', 'string', 'max:100'],
            'limits' => ['nullable', 'string', 'max:100'],
            'last_verified_at' => ['nullable', 'date'],
        ]);
    }

    private function castDetectedValue(string $metric, ?string $value): mixed
    {
        return in_array($metric, ['monthly_price', 'yearly_price'], true)
            ? ($value === null ? null : (float) $value)
            : $value;
    }

    private function historyType(mixed $old, mixed $new, bool $numeric): string
    {
        if ($old === null && $new !== null) return 'new_plan';
        if ($old !== null && ($new === null || $new === '')) return 'removed_plan';
        if ($numeric) return (float) $new >= (float) $old ? 'increase' : 'decrease';
        return 'increase';
    }
}
