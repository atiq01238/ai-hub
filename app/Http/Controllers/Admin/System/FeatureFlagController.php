<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\FeatureFlag;
use Illuminate\Http\Request;

class FeatureFlagController extends Controller
{
    public function index()
    {
        $flags = FeatureFlag::latest()->get();

        return view('system.feature-flags', compact('flags'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:100'],
            'description'         => ['nullable', 'string', 'max:255'],
            'environment'         => ['required', 'in:production,staging'],
            'rollout_percentage'  => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        FeatureFlag::create($data);

        return redirect()->route('admin.system.feature-flags')->with('status', 'Flag created.');
    }

    public function toggle(int $id)
    {
        $flag = FeatureFlag::findOrFail($id);
        $flag->is_enabled = ! $flag->is_enabled;
        $flag->save();

        return redirect()->back()->with('status', $flag->name . ' turned ' . ($flag->is_enabled ? 'ON' : 'OFF') . '.');
    }

    public function destroy(int $id)
    {
        FeatureFlag::findOrFail($id)->delete();

        return redirect()->route('admin.system.feature-flags')->with('status', 'Flag removed.');
    }
}
