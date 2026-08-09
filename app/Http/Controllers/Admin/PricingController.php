<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PricingHistory;
use App\Models\PricingPlan;
use App\Models\Tool;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index(Request $request)
    {
        return $this->plansIndex($request);
    }

    // "API Pricing" tab = same list, only plans that actually have API pricing set.
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
        PricingPlan::create($this->fromRequest($request));

        return redirect()->route('admin.pricing.index')->with('status', 'Pricing plan created.');
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

    public function history(Request $request)
    {
        $toolId = $request->query('tool_id');
        $tools = Tool::orderBy('name')->get();
        $selectedTool = $toolId ? Tool::find($toolId) : $tools->first();

        $changes = PricingHistory::with('tool')
            ->when($toolId, fn ($q) => $q->where('tool_id', $toolId))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Build the timeline chart data for the selected tool only.
        $timeline = $selectedTool
            ? PricingHistory::where('tool_id', $selectedTool->id)
                ->oldest()
                ->get(['new_price', 'created_at'])
            : collect();

        return view('pricing.history', compact('changes', 'tools', 'selectedTool', 'timeline'));
    }

    private function plansIndex(Request $request, bool $onlyApi = false)
    {
        $query = PricingPlan::with('tool');

        if ($onlyApi) {
            $query->whereNotNull('api_price_label');
        }

        if ($search = $request->query('search')) {
            $query->whereHas('tool', fn ($q) => $q->where('name', 'like', "%{$search}%"));
        }

        $plans = $query->latest('updated_at')->paginate(20)->withQueryString();

        return view('pricing.index', compact('plans', 'onlyApi'));
    }

    private function fromRequest(Request $request): array
    {
        return $request->validate([
            'tool_id'          => ['required', 'exists:tools,id'],
            'plan_name'        => ['required', 'string', 'max:100'],
            'monthly_price'    => ['nullable', 'numeric', 'min:0'],
            'yearly_price'     => ['nullable', 'numeric', 'min:0'],
            'api_price_label'  => ['nullable', 'string', 'max:50'],
            'credits'          => ['nullable', 'string', 'max:100'],
            'limits'           => ['nullable', 'string', 'max:100'],
        ]);
    }
}
