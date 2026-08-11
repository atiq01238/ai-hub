<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\NotificationRule;

class NotificationRuleController extends Controller
{
    public function index()
    {
        $rules = NotificationRule::orderBy('label')->get();

        return view('system.notification-rules', compact('rules'));
    }

    public function toggle(int $id)
    {
        $rule = NotificationRule::findOrFail($id);
        $rule->enabled = ! $rule->enabled;
        $rule->save();

        return redirect()->back()->with('status', $rule->label . ' turned ' . ($rule->enabled ? 'ON' : 'OFF') . '.');
    }
}
