@extends('frontend.layouts.app')
@section('title', 'Contact & Feedback — AI Orbit')
@section('meta_description', 'Contact AI Orbit for feedback, data corrections, partnerships, press, technical issues or general questions.')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/institutional.css') }}">@endpush
@section('content')
<section class="inst-hero inst-hero-compact"><div class="inst-wrap"><span class="inst-eyebrow"><i data-lucide="messages-square"></i> Contact & feedback</span><h1>Help us make AI Orbit <span>more useful.</span></h1><p>Send product feedback, flag data quality issues, discuss partnerships or reach out with a general question.</p></div></section>
<div class="inst-wrap inst-contact-layout">
    <section class="inst-contact-form panel">
        <div class="inst-contact-head"><span class="inst-mini-title">Send a message</span><h2>What can we help with?</h2><p>Messages are stored for review. Please avoid including passwords, API keys or other sensitive credentials.</p></div>
        @if(session('status'))<div class="inst-alert success"><i data-lucide="circle-check"></i>{{ session('status') }}</div>@endif
        @if($errors->any())<div class="inst-alert error"><i data-lucide="circle-alert"></i><div><strong>Please check the form.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
        <form action="{{ route('contact.store') }}" method="POST" class="inst-form">@csrf
            <div class="inst-hp" aria-hidden="true"><label>Company name<input name="company_name" tabindex="-1" autocomplete="off"></label></div>
            <div class="inst-form-grid">
                <label><span>Name <b>*</b></span><input name="name" maxlength="120" required value="{{ old('name', auth()->user()?->name) }}" placeholder="Your name"></label>
                <label><span>Email <b>*</b></span><input type="email" name="email" maxlength="190" required value="{{ old('email', auth()->user()?->email) }}" @readonly(auth()->check()) placeholder="you@example.com"></label>
                <label><span>Topic <b>*</b></span><select name="topic" required><option value="general" @selected(old('topic')==='general')>General question</option><option value="feedback" @selected(old('topic')==='feedback')>Product feedback</option><option value="data_correction" @selected(old('topic')==='data_correction')>Data quality / correction</option><option value="partnership" @selected(old('topic')==='partnership')>Partnership</option><option value="press" @selected(old('topic')==='press')>Press / media</option><option value="technical" @selected(old('topic')==='technical')>Technical issue</option></select></label>
                <label><span>Subject <b>*</b></span><input name="subject" maxlength="180" required value="{{ old('subject') }}" placeholder="Short summary"></label>
                <label class="full"><span>Message <b>*</b></span><textarea name="message" rows="8" minlength="20" maxlength="5000" required placeholder="Give us enough context to understand the issue or request...">{{ old('message') }}</textarea><small>20–5,000 characters.</small></label>
            </div>
            <div class="inst-form-footer"><span><i data-lucide="shield-check"></i> Protected by rate limiting and duplicate-message checks.</span><button class="inst-btn primary" type="submit">Send message <i data-lucide="send"></i></button></div>
        </form>
    </section>
    <aside class="inst-contact-side">
        <article class="inst-side-card"><i data-lucide="database-zap"></i><h3>Suggest a tool or correction</h3><p>Use the structured contribution form when you want a new tool, model or company added to the directory.</p><a href="{{ route('submissions.create') }}">Open contribution form <i data-lucide="arrow-right"></i></a></article>
        <article class="inst-side-card"><i data-lucide="microscope"></i><h3>Question our methodology</h3><p>See how ratings, benchmarks, Test Lab results, pricing and news signals are handled before reporting a methodology concern.</p><a href="{{ route('methodology') }}">Read methodology <i data-lucide="arrow-right"></i></a></article>
        <article class="inst-side-card"><i data-lucide="life-buoy"></i><h3>Useful context</h3><ul><li>Include the affected page URL when reporting data.</li><li>For pricing issues, mention the plan and billing period.</li><li>For benchmark issues, include the benchmark/test name.</li></ul></article>
    </aside>
</div>
@endsection
