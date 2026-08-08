@extends('layouts.admin')
@section('title', 'SEO Management')

@section('content')

<x-page-header title="SEO Management" subtitle="Titles, meta, and indexing across all content types" :breadcrumb="['System', 'SEO']" />

<div class="tabs">
    <div class="tab is-active">Global SEO</div>
    <div class="tab">Tool SEO</div>
    <div class="tab">Model SEO</div>
    <div class="tab">Company SEO</div>
    <div class="tab">Article SEO</div>
    <div class="tab">News SEO</div>
</div>

<div class="grid-12">
    <div class="col-7 card card-pad">
        <div class="section-title">Edit SEO — ChatGPT</div>
        <div class="form-field" style="margin-bottom:12px;"><label>SEO Title</label><input class="input" value="ChatGPT Review, Pricing &amp; Alternatives (2026) | AI Hub"></div>
        <div class="form-field" style="margin-bottom:12px;"><label>Meta Description</label><textarea class="input" rows="3">Everything you need to know about ChatGPT — features, pricing plans, ratings, and how it compares to Claude and Gemini.</textarea></div>
        <div class="grid-2">
            <div class="form-field"><label>Slug</label><input class="input" value="/tools/chatgpt"></div>
            <div class="form-field"><label>Canonical URL</label><input class="input" value="https://aihub.io/tools/chatgpt"></div>
        </div>
        <div class="grid-2" style="margin-top:12px;">
            <div class="form-field"><label>Index Status</label><select class="select"><option>Indexed</option><option>Noindex</option></select></div>
            <div class="form-field"><label>Schema Type</label><select class="select"><option>SoftwareApplication</option><option>Product</option></select></div>
        </div>
        <button class="btn btn-primary btn-sm" style="margin-top:16px;"><i data-lucide="check"></i> Save SEO Settings</button>
    </div>
    <div class="col-5 card card-pad">
        <div class="section-title">Google Search Preview</div>
        <div style="background:#fff; border-radius:10px; padding:14px 16px; color:#202124;">
            <div style="font-size:12px; color:#1a0dab; margin-bottom:2px;">aihub.io › tools › chatgpt</div>
            <div style="font-size:16px; color:#1a0dab; margin-bottom:4px; font-family: arial, sans-serif;">ChatGPT Review, Pricing &amp; Alternatives (2026) | AI Hub</div>
            <div style="font-size:12.5px; color:#4d5156; line-height:1.5;">Everything you need to know about ChatGPT — features, pricing plans, ratings, and how it compares to Claude and Gemini.</div>
        </div>
        <div class="divider"></div>
        <div class="section-title">Open Graph Preview</div>
        <div class="card" style="overflow:hidden;">
            <div class="thumb lg" style="width:100%; height:110px; border-radius:0;"><i data-lucide="image"></i></div>
            <div style="padding:10px 12px;">
                <div class="cell-sub">AIHUB.IO</div>
                <b style="font-size:13px;">ChatGPT Review, Pricing &amp; Alternatives</b>
            </div>
        </div>
    </div>
</div>
@endsection
