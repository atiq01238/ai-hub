@extends('layouts.admin')
@section('title', 'Settings')

@section('content')

<x-page-header title="Settings" :breadcrumb="['System', 'Settings']">
    <x-slot:actions><button class="btn btn-primary btn-sm"><i data-lucide="check"></i> Save Changes</button></x-slot:actions>
</x-page-header>

<div class="tabs">
    @foreach(['General','Branding','Appearance','Notifications','Email','SEO','Analytics','Integrations','Security','Content','Social Media','System'] as $i => $t)
        <div class="tab {{ $i===0 ? 'is-active' : '' }}">{{ $t }}</div>
    @endforeach
</div>

<div class="grid-12">
    <div class="col-8">
        <div class="card card-pad form-section" style="margin-bottom:16px;">
            <div class="form-section__title">General</div>
            <div class="form-grid">
                <div class="form-field col-span-2"><label>Site Name</label><input class="input" value="AI Hub"></div>
                <div class="form-field col-span-2"><label>Tagline</label><input class="input" value="AI Research &amp; Intelligence Platform"></div>
                <div class="form-field"><label>Default Timezone</label><select class="select"><option>Asia/Karachi (UTC+5)</option><option>UTC</option></select></div>
                <div class="form-field"><label>Default Language</label><select class="select"><option>English</option><option>Urdu</option></select></div>
                <div class="form-field"><label>Maintenance Mode</label>
                    <div class="flex items-center gap-8"><div class="switch"><i></i></div><span class="cell-sub">Site is publicly accessible</span></div>
                </div>
            </div>
        </div>

        <div class="card card-pad form-section">
            <div class="form-section__title">Content Defaults</div>
            <div class="form-grid">
                <div class="form-field"><label>Default Article Status</label><select class="select"><option>Draft</option><option>Pending Review</option></select></div>
                <div class="form-field"><label>Comments</label>
                    <div class="flex items-center gap-8"><div class="switch is-on"><i></i></div><span class="cell-sub">Enabled site-wide</span></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-4 card card-pad">
        <div class="form-section__title">Quick Toggles</div>
        @foreach(['Auto-publish verified news'=>true,'Require 2FA for all admins'=>true,'Public tool submissions'=>true,'Show beta features'=>false] as $label => $on)
        <div class="flex items-center justify-between" style="padding:10px 0; border-bottom:1px solid var(--border-soft);">
            <span style="font-size:12.5px;">{{ $label }}</span>
            <div class="switch {{ $on ? 'is-on' : '' }}"><i></i></div>
        </div>
        @endforeach
    </div>
</div>
@endsection
