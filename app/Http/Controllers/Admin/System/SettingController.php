<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    // The full list of settings this page manages, with sensible defaults.
    // Add a new field here whenever you want the page to control something new.
    private array $defaults = [
        'site_name'                   => 'AI Hub',
        'tagline'                     => 'AI Research & Intelligence Platform',
        'timezone'                    => 'Asia/Karachi',
        'language'                    => 'en',
        'maintenance_mode'            => '0',
        'default_article_status'      => 'draft',
        'comments_enabled'            => '1',
        'auto_publish_verified_news'  => '1',
        'require_2fa_for_admins'      => '1',
        'public_tool_submissions'     => '1',
        'show_beta_features'          => '0',
    ];

    public function index()
    {
        $settings = Setting::getMany(array_keys($this->defaults), $this->defaults);

        return view('system.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'site_name'               => ['required', 'string', 'max:100'],
            'tagline'                 => ['nullable', 'string', 'max:255'],
            'timezone'                => ['required', 'string', 'max:50'],
            'language'                => ['required', 'string', 'max:10'],
            'default_article_status'  => ['required', 'in:draft,pending_review'],
        ]);

        // Checkboxes only appear in the request when checked — so anything
        // NOT present in the request means "turned off" (value '0').
        $toggles = [
            'maintenance_mode', 'comments_enabled', 'auto_publish_verified_news',
            'require_2fa_for_admins', 'public_tool_submissions', 'show_beta_features',
        ];
        foreach ($toggles as $toggle) {
            $data[$toggle] = $request->boolean($toggle) ? '1' : '0';
        }

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('admin.system.settings')->with('status', 'Settings saved.');
    }
}
