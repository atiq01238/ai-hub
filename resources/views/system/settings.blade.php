@extends('layouts.admin')
@section('title', 'Settings')

@section('content')
@php($canEditSettings = auth()->user()->canAccessModule('Settings', 'Edit'))

<form action="{{ route('admin.system.settings.update') }}" method="POST">
@csrf
@method('PUT')

<x-page-header title="Settings" :breadcrumb="['System', 'Settings']">
    <x-slot:actions>@if($canEditSettings)<button type="submit" class="btn btn-primary btn-sm"><i data-lucide="check"></i> Save Changes</button>@else<span class="badge badge-neutral"><i data-lucide="eye" style="width:13px;"></i> Read only</span>@endif</x-slot:actions>
</x-page-header>

@if (session('status'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('status') }}</div>
@endif
@unless($canEditSettings)
    <div class="alert alert-info" style="margin-bottom:16px;">Your role has Settings → View access but not Settings → Edit. Configuration is read-only.</div>
@endunless

<fieldset {{ $canEditSettings ? '' : 'disabled' }} style="border:0;padding:0;margin:0;min-width:0;">

<div class="tabs">
    <div class="tab is-active">General</div>
</div>
<p class="text-sub" style="font-size:12px; margin:-8px 0 16px;">
    The other tabs (Branding, Appearance, Email, Integrations, etc.) from the original design
    weren't hooked up to anything real yet — they were empty placeholders. Only General is wired
    up for now.
</p>

<div class="grid-12">
    <div class="col-8">
        <div class="card card-pad form-section" style="margin-bottom:16px;">
            <div class="form-section__title">General</div>
            <div class="form-grid">
                <div class="form-field col-span-2"><label>Site Name</label><input class="input" name="site_name" value="{{ old('site_name', $settings['site_name']) }}"></div>
                <div class="form-field col-span-2"><label>Tagline</label><input class="input" name="tagline" value="{{ old('tagline', $settings['tagline']) }}"></div>
                <div class="form-field">
                    <label>Default Timezone</label>
                    <select class="select" name="timezone">
                        @foreach (['Asia/Karachi' => 'Asia/Karachi (UTC+5)', 'UTC' => 'UTC'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('timezone', $settings['timezone']) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label>Default Language</label>
                    <select class="select" name="language">
                        @foreach (['en' => 'English', 'ur' => 'Urdu'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('language', $settings['language']) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label>Maintenance Mode</label>
                    <label class="flex items-center gap-8" style="cursor:pointer;">
                        <input type="checkbox" name="maintenance_mode" value="1" class="switch-checkbox" {{ old('maintenance_mode', $settings['maintenance_mode']) == '1' ? 'checked' : '' }}>
                        <span class="switch"><i></i></span>
                        <span class="cell-sub">{{ $settings['maintenance_mode'] == '1' ? 'Site is in maintenance mode' : 'Site is publicly accessible' }}</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="card card-pad form-section">
            <div class="form-section__title">Content Defaults</div>
            <div class="form-grid">
                <div class="form-field">
                    <label>Default Article Status</label>
                    <select class="select" name="default_article_status">
                        @foreach (['draft' => 'Draft', 'pending_review' => 'Pending Review'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('default_article_status', $settings['default_article_status']) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label>Comments</label>
                    <label class="flex items-center gap-8" style="cursor:pointer;">
                        <input type="checkbox" name="comments_enabled" value="1" class="switch-checkbox" {{ old('comments_enabled', $settings['comments_enabled']) == '1' ? 'checked' : '' }}>
                        <span class="switch"><i></i></span>
                        <span class="cell-sub">{{ $settings['comments_enabled'] == '1' ? 'Enabled site-wide' : 'Disabled' }}</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="col-4 card card-pad">
        <div class="form-section__title">Quick Toggles</div>
        @foreach ([
            'auto_publish_verified_news' => 'Auto-publish verified news',
            'require_2fa_for_admins'     => 'Require 2FA for all admins',
            'public_tool_submissions'    => 'Public tool submissions',
            'show_beta_features'         => 'Show beta features',
        ] as $key => $label)
        <div class="flex items-center justify-between" style="padding:10px 0; border-bottom:1px solid var(--border-soft);">
            <span style="font-size:12.5px;">{{ $label }}</span>
            <label style="cursor:pointer;">
                <input type="checkbox" name="{{ $key }}" value="1" class="switch-checkbox" {{ old($key, $settings[$key]) == '1' ? 'checked' : '' }}>
                <span class="switch"><i></i></span>
            </label>
        </div>
        @endforeach
        @if ($settings['require_2fa_for_admins'] == '1')
        <p class="text-sub" style="font-size:11.5px; margin-top:10px;">Note: this toggle just records the preference — actually enforcing 2FA requires the 2FA feature itself to be built (separate task).</p>
        @endif
    </div>
</div>
</fieldset>
</form>
@endsection
