@extends('layouts.admin')
@section('title','Settings')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/final-polish.css') }}">@endpush
@section('content')
@php($canEditSettings=auth()->user()->canAccessModule('Settings','Edit'))
<form action="{{ route('admin.system.settings.update') }}" method="POST">@csrf @method('PUT')
<div class="fp-page">
<x-page-header title="General Settings" subtitle="Control the real site-wide defaults currently backed by the Settings model." :breadcrumb="['System','Settings']">
<x-slot:actions>@if($canEditSettings)<button class="btn btn-primary"><i data-lucide="save"></i>Save Changes</button>@else<span class="fp-readonly"><i data-lucide="eye"></i>Read only</span>@endif</x-slot:actions>
</x-page-header>
@if(session('status'))<div class="alert alert-success fp-flash"><i data-lucide="check-circle-2"></i><span>{{ session('status') }}</span></div>@endif
@if($errors->any())<div class="alert alert-danger fp-flash"><i data-lucide="circle-alert"></i><span>{{ $errors->first() }}</span></div>@endif
@unless($canEditSettings)<div class="fp-boundary"><i data-lucide="lock-keyhole"></i><div><strong>Read-only configuration</strong><p>Your permission role can inspect settings but cannot modify them.</p></div></div>@endunless
<fieldset {{ $canEditSettings?'':'disabled' }} class="fp-fieldset">
<div class="fp-settings-layout">
<main class="fp-settings-main">
<section class="card fp-panel"><header class="fp-card-head"><div><span class="fp-eyebrow">Platform Identity</span><h2>General</h2><p>Core naming, locale and availability defaults.</p></div><i data-lucide="settings-2"></i></header><div class="fp-form-grid">
<label class="fp-field fp-field--full"><span>Site Name</span><input class="input" name="site_name" value="{{ old('site_name',$settings['site_name']) }}" required></label>
<label class="fp-field fp-field--full"><span>Tagline</span><input class="input" name="tagline" value="{{ old('tagline',$settings['tagline']) }}"></label>
<label class="fp-field"><span>Default Timezone</span><select class="select" name="timezone">@foreach(['Asia/Karachi'=>'Asia/Karachi (UTC+5)','UTC'=>'UTC'] as $val=>$label)<option value="{{ $val }}" @selected(old('timezone',$settings['timezone'])===$val)>{{ $label }}</option>@endforeach</select></label>
<label class="fp-field"><span>Default Language</span><select class="select" name="language">@foreach(['en'=>'English','ur'=>'Urdu'] as $val=>$label)<option value="{{ $val }}" @selected(old('language',$settings['language'])===$val)>{{ $label }}</option>@endforeach</select></label>
<label class="fp-toggle fp-field--full"><input type="checkbox" name="maintenance_mode" value="1" @checked(old('maintenance_mode',$settings['maintenance_mode'])=='1')><span><strong>Maintenance Mode</strong><small>Stored platform availability preference.</small></span></label>
</div></section>
<section class="card fp-panel"><header class="fp-card-head"><div><span class="fp-eyebrow">Editorial Defaults</span><h2>Content behavior</h2><p>Defaults used by content and community workflows.</p></div><i data-lucide="file-cog"></i></header><div class="fp-form-grid">
<label class="fp-field"><span>Default Article Status</span><select class="select" name="default_article_status"><option value="draft" @selected(old('default_article_status',$settings['default_article_status'])==='draft')>Draft</option><option value="pending_review" @selected(old('default_article_status',$settings['default_article_status'])==='pending_review')>Pending Review</option></select></label>
<label class="fp-toggle"><input type="checkbox" name="comments_enabled" value="1" @checked(old('comments_enabled',$settings['comments_enabled'])=='1')><span><strong>Comments Enabled</strong><small>Site-wide stored preference.</small></span></label>
</div></section>
</main>
<aside class="fp-settings-side">
<section class="card fp-settings-toggles"><span class="fp-eyebrow">Operational Toggles</span>
@foreach([
'auto_publish_verified_news'=>['Auto-publish verified news','badge-check'],
'require_2fa_for_admins'=>['Require 2FA for administrators','shield-check'],
'public_tool_submissions'=>['Public tool submissions','inbox'],
'show_beta_features'=>['Show beta features','flask-conical'],
] as $key=>[$label,$icon])
<label class="fp-toggle-row"><span class="fp-toggle-row__icon"><i data-lucide="{{ $icon }}"></i></span><div><strong>{{ $label }}</strong><small>{{ old($key,$settings[$key])=='1'?'Enabled':'Disabled' }}</small></div><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key,$settings[$key])=='1')></label>
@endforeach
</section>
<section class="fp-boundary fp-boundary--card"><i data-lucide="info"></i><div><strong>Only real settings are shown</strong><p>Branding, appearance, mail and integration tabs are intentionally not fabricated here because the current controller does not persist them. 2FA itself is now implemented; this page stores the administrator-enforcement preference.</p></div></section>
</aside></div>
</fieldset></div></form>
@endsection
