@extends('layouts.admin')
@section('title', isset($item) ? 'Edit News Item' : 'Add News Item')

@section('content')

@php
    $item ??= null;
    $old = fn ($key, $default = null) => old($key, $item->{$key} ?? $default);
@endphp

<style>
    /* AI HUB — ADVANCED NEWS EDITOR UI
       Presentation-only styles. Existing form fields, routes,
       Blade variables and submission logic are preserved. */

    .news-editor {
        --ne-border: var(--border-soft, rgba(148,163,184,.14));
        --ne-text: var(--text, #eef2ff);
        --ne-muted: var(--muted, #8d98ad);
        --ne-blue: #6d8cff;
        --ne-cyan: #22d3ee;
        --ne-green: #32d583;
        --ne-orange: #f5a524;
        color: var(--ne-text);
    }

    .news-editor__hero {
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 20px;
        padding: 21px 22px;
        border: 1px solid var(--ne-border);
        border-radius: 17px;
        background:
            radial-gradient(circle at 92% 15%, rgba(109,140,255,.17), transparent 28%),
            radial-gradient(circle at 68% 100%, rgba(34,211,238,.07), transparent 27%),
            linear-gradient(145deg, rgba(255,255,255,.045), rgba(255,255,255,.012));
        box-shadow: 0 16px 42px rgba(0,0,0,.10);
    }

    .news-editor__hero:after {
        content: "";
        position: absolute;
        right: -70px;
        top: -95px;
        width: 190px;
        height: 190px;
        border: 1px solid rgba(109,140,255,.12);
        border-radius: 50%;
        box-shadow: 0 0 0 30px rgba(109,140,255,.025), 0 0 0 60px rgba(109,140,255,.012);
        pointer-events: none;
    }

    .news-editor__heading {
        position: relative;
        z-index: 1;
    }

    .news-editor__eyebrow {
        display: flex;
        align-items: center;
        gap: 7px;
        margin-bottom: 6px;
        color: var(--ne-cyan);
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .13em;
        text-transform: uppercase;
    }

    .news-editor__dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--ne-green);
        box-shadow: 0 0 0 4px rgba(50,213,131,.09);
    }

    .news-editor__title {
        margin: 0;
        font-size: clamp(22px, 2.7vw, 29px);
        line-height: 1.15;
        letter-spacing: -.035em;
        font-weight: 800;
    }

    .news-editor__description {
        margin: 7px 0 0;
        color: var(--ne-muted);
        font-size: 11.5px;
        line-height: 1.55;
    }

    .news-editor__actions {
        position: relative;
        z-index: 2;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .news-editor__action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 37px;
        padding: 0 13px;
        border-radius: 9px;
        font-size: 11px;
        font-weight: 750;
        cursor: pointer;
        transition: .2s ease;
    }

    .news-editor__action svg {
        width: 14px;
        height: 14px;
    }

    .news-editor__action.draft {
        color: var(--ne-text);
        background: rgba(255,255,255,.035);
        border: 1px solid var(--ne-border);
    }

    .news-editor__action.draft:hover {
        background: rgba(255,255,255,.07);
        border-color: rgba(109,140,255,.28);
    }

    .news-editor__action.publish {
        color: #fff;
        background: linear-gradient(135deg, #6d8cff, #536ff0);
        border: 1px solid rgba(255,255,255,.12);
        box-shadow: 0 8px 22px rgba(83,111,240,.20);
    }

    .news-editor__action.publish:hover {
        transform: translateY(-1px);
        box-shadow: 0 11px 27px rgba(83,111,240,.30);
    }

    .news-editor__error {
        display: flex;
        gap: 11px;
        margin-bottom: 16px;
        padding: 13px 15px;
        border: 1px solid rgba(249,112,104,.25);
        border-radius: 12px;
        color: #ffaaa4;
        background: rgba(249,112,104,.055);
        font-size: 11px;
    }

    .news-editor__error svg {
        width: 17px;
        height: 17px;
        flex: 0 0 17px;
    }

    .news-editor__error ul {
        margin: 0;
        padding-left: 17px;
    }

    .news-editor__layout {
        display: grid;
        grid-template-columns: minmax(0, 1.7fr) minmax(270px, .72fr);
        gap: 18px;
        align-items: start;
    }

    .news-editor__main {
        min-width: 0;
    }

    .news-editor__side {
        min-width: 0;
        position: sticky;
        top: 18px;
    }

    .news-editor__card {
        overflow: hidden;
        margin-bottom: 18px;
        border: 1px solid var(--ne-border);
        border-radius: 15px;
        background: linear-gradient(145deg, rgba(255,255,255,.035), rgba(255,255,255,.012));
        box-shadow: 0 12px 34px rgba(0,0,0,.07);
    }

    .news-editor__card:last-child {
        margin-bottom: 0;
    }

    .news-editor__card-head {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--ne-border);
    }

    .news-editor__card-mark {
        width: 3px;
        height: 17px;
        flex: 0 0 3px;
        border-radius: 99px;
        background: linear-gradient(180deg, var(--ne-blue), var(--ne-cyan));
    }

    .news-editor__card-title {
        margin: 0;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: -.01em;
    }

    .news-editor__card-subtitle {
        margin: 2px 0 0;
        color: var(--ne-muted);
        font-size: 9.5px;
    }

    .news-editor__card-body {
        padding: 17px;
    }

    .news-editor .form-grid {
        gap: 14px;
    }

    .news-editor .form-field label {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 6px;
        color: var(--ne-muted);
        font-size: 10px;
        font-weight: 750;
        letter-spacing: .01em;
    }

    .news-editor .form-field label:after {
        content: "";
        width: 3px;
        height: 3px;
        border-radius: 50%;
        background: rgba(109,140,255,.55);
    }

    .news-editor .input,
    .news-editor .select {
        width: 100%;
        min-height: 39px;
        border-radius: 9px;
        border-color: var(--ne-border);
        background: rgba(255,255,255,.022);
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .news-editor textarea.input {
        min-height: 92px;
        resize: vertical;
        line-height: 1.55;
    }

    .news-editor .form-field:nth-child(3) textarea.input {
        min-height: 65px;
    }

    .news-editor .input:focus,
    .news-editor .select:focus {
        border-color: rgba(109,140,255,.55);
        background: rgba(109,140,255,.025);
        box-shadow: 0 0 0 3px rgba(109,140,255,.08);
        outline: none;
    }

    /* Fix native select dropdown readability.
       The closed field stays in the existing dark theme,
       while opened options use a readable background/text. */
    .news-editor select.select {
        color-scheme: dark;
        color: var(--ne-text);
    }

    .news-editor select.select option,
    .news-editor select.select optgroup {
        color: #182033;
        background: #ffffff;
        font-weight: 500;
    }

    .news-editor select.select option:checked,
    .news-editor select.select option:hover {
        color: #ffffff;
        background: #536ff0;
    }

    .news-editor select.select option[disabled] {
        color: #7b8497;
        background: #ffffff;
    }

    /* Better visibility for browsers that expose the native
       dropdown with the select's own color scheme. */
    .news-editor select.select:focus {
        color-scheme: dark;
    }

    .news-editor__headline-field .input {
        min-height: 48px;
        font-size: 14px;
        font-weight: 650;
    }

    .news-editor__source-card {
        position: relative;
    }

    .news-editor__source-status {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-left: auto;
        padding: 4px 7px;
        border: 1px solid rgba(50,213,131,.16);
        border-radius: 6px;
        color: #6fe0a1;
        background: rgba(50,213,131,.055);
        font-size: 8px;
        font-weight: 800;
        letter-spacing: .05em;
        text-transform: uppercase;
    }

    .news-editor__source-status span {
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: currentColor;
    }

    .news-editor__source-fields {
        display: grid;
        gap: 13px;
    }

    .news-editor__helper {
        margin-top: 5px;
        color: var(--ne-muted);
        font-size: 8.5px;
    }

    .news-editor__classification-note {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-top: 14px;
        padding: 10px 11px;
        border: 1px solid rgba(109,140,255,.12);
        border-radius: 9px;
        color: var(--ne-muted);
        background: rgba(109,140,255,.035);
        font-size: 9.5px;
        line-height: 1.5;
    }

    .news-editor__classification-note svg {
        width: 14px;
        height: 14px;
        flex: 0 0 14px;
        color: #91a5ff;
    }

    .news-editor__quick-status {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 7px;
        margin-top: 14px;
    }

    .news-editor__status-item {
        padding: 9px 8px;
        border: 1px solid var(--ne-border);
        border-radius: 8px;
        background: rgba(255,255,255,.018);
    }

    .news-editor__status-label {
        margin-bottom: 3px;
        color: var(--ne-muted);
        font-size: 8px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .06em;
    }

    .news-editor__status-value {
        font-size: 10px;
        font-weight: 750;
    }

    .news-editor__status-value.green { color: #6fe0a1; }
    .news-editor__status-value.blue { color: #9fb1ff; }
    .news-editor__status-value.orange { color: #ffc86b; }

    @media (max-width: 1050px) {
        .news-editor__layout {
            grid-template-columns: minmax(0, 1fr);
        }

        .news-editor__side {
            position: static;
        }
    }

    @media (max-width: 700px) {
        .news-editor__hero {
            align-items: flex-start;
            flex-direction: column;
            padding: 17px;
        }

        .news-editor__actions {
            width: 100%;
        }

        .news-editor__action {
            flex: 1;
        }

        .news-editor__card-body {
            padding: 14px;
        }

        .news-editor__quick-status {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 500px) {
        .news-editor__actions {
            flex-direction: column;
        }

        .news-editor__action {
            width: 100%;
        }
    }
</style>

<div class="news-editor">

    <form action="{{ $item ? route('admin.news.update', $item->id) : route('admin.news.store') }}" method="POST">
        @csrf
        @if ($item) @method('PUT') @endif

        {{-- =================================================
             EDITOR HEADER
             ================================================= --}}
        <section class="news-editor__hero">
            <div class="news-editor__heading">
                <div class="news-editor__eyebrow">
                    <span class="news-editor__dot"></span>
                    AI Intelligence · News Feed
                </div>

                <h1 class="news-editor__title">
                    {{ $item ? 'Edit News Item' : 'Add News Item' }}
                </h1>

                <p class="news-editor__description">
                    {{ $item ? 'Update the story details, classification and source information.' : 'Create and publish a structured AI news story for your intelligence feed.' }}
                </p>
            </div>

            <div class="news-editor__actions">
                <button type="submit" name="status" value="draft" class="news-editor__action draft">
                    <i data-lucide="save"></i>
                    Save Draft
                </button>

                <button type="submit" name="status" value="published" class="news-editor__action publish">
                    <i data-lucide="check"></i>
                    Publish
                </button>
            </div>
        </section>

        {{-- =================================================
             VALIDATION
             ================================================= --}}
        @if ($errors->any())
            <div class="news-editor__error">
                <i data-lucide="triangle-alert"></i>

                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- =================================================
             MAIN EDITOR
             ================================================= --}}
        <div class="news-editor__layout">

            <main class="news-editor__main">

                {{-- STORY --}}
                <section class="news-editor__card">
                    <div class="news-editor__card-head">
                        <span class="news-editor__card-mark"></span>
                        <div>
                            <h2 class="news-editor__card-title">Story</h2>
                            <p class="news-editor__card-subtitle">Build the core news story and editorial context.</p>
                        </div>
                    </div>

                    <div class="news-editor__card-body">
                        <div class="form-grid">

                            <div class="form-field col-span-2 news-editor__headline-field">
                                <label>Headline</label>
                                <input
                                    class="input"
                                    name="headline"
                                    value="{{ $old('headline') }}"
                                    placeholder="e.g. OpenAI announces GPT-5.2 Turbo"
                                    required
                                >
                            </div>

                            <div class="form-field col-span-2">
                                <label>Summary</label>
                                <textarea
                                    class="input"
                                    name="summary"
                                    rows="4"
                                    placeholder="What happened..."
                                >{{ $old('summary') }}</textarea>
                                <div class="news-editor__helper">Keep the summary concise and focused on the key development.</div>
                            </div>

                            <div class="form-field col-span-2">
                                <label>Why It Matters</label>
                                <textarea
                                    class="input"
                                    name="why_it_matters"
                                    rows="2"
                                    placeholder="Why this is significant..."
                                >{{ $old('why_it_matters') }}</textarea>
                            </div>

                        </div>
                    </div>
                </section>

                {{-- CLASSIFICATION --}}
                <section class="news-editor__card">
                    <div class="news-editor__card-head">
                        <span class="news-editor__card-mark"></span>
                        <div>
                            <h2 class="news-editor__card-title">Classification</h2>
                            <p class="news-editor__card-subtitle">Organize the story for discovery, filtering and trust.</p>
                        </div>
                    </div>

                    <div class="news-editor__card-body">
                        <div class="form-grid">

                            <div class="form-field">
                                <label>Category</label>
                                <select class="select" name="category">
                                    <option value="">Select category...</option>
                                    @foreach (['Breaking News','New Model','Product Launch','Product Update','New Feature','Pricing Change','AI Review','Benchmark','Research','Funding','Acquisition','Security','Policy','Regulation'] as $cat)
                                        <option value="{{ $cat }}" @selected($old('category') === $cat)>{{ $cat }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field">
                                <label>Company</label>
                                <select class="select" name="company_id">
                                    <option value="">Select company...</option>
                                    @foreach ($companies as $company)
                                        <option value="{{ $company->id }}" @selected($old('company_id') == $company->id)>{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field">
                                <label>Sentiment</label>
                                <select class="select" name="sentiment">
                                    @foreach (['positive'=>'Positive','neutral'=>'Neutral','negative'=>'Negative'] as $val => $label)
                                        <option value="{{ $val }}" @selected($old('sentiment','neutral') === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field">
                                <label>Importance (0–100)</label>
                                <input
                                    class="input"
                                    type="number"
                                    min="0"
                                    max="100"
                                    name="importance"
                                    value="{{ $old('importance', 50) }}"
                                >
                            </div>

                            <div class="form-field">
                                <label>Verification Status</label>
                                <select class="select" name="verification_status">
                                    @foreach (['unverified'=>'Unverified','needs_verification'=>'Needs Verification','verified'=>'Verified'] as $val => $label)
                                        <option value="{{ $val }}" @selected($old('verification_status','unverified') === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-field col-span-2">
                                <label>Related Tools</label>
                                <input
                                    class="input"
                                    name="related_tools_input"
                                    value="{{ $old('related_tools_input', $item && $item->related_tools ? implode(', ', $item->related_tools) : '') }}"
                                    placeholder="ChatGPT, Claude..."
                                >
                                <div class="news-editor__helper">Separate multiple tools with commas.</div>
                            </div>

                            <div class="form-field col-span-2">
                                <label>Tags</label>
                                <input
                                    class="input"
                                    name="tags_input"
                                    value="{{ $old('tags_input', $item && $item->tags ? implode(', ', $item->tags) : '') }}"
                                    placeholder="agents, llm, enterprise..."
                                >
                                <div class="news-editor__helper">Use concise topics to improve categorization and search.</div>
                            </div>

                        </div>

                        <div class="news-editor__classification-note">
                            <i data-lucide="info"></i>
                            <span>
                                Classification fields help the News Feed organize stories and communicate their relevance and verification state.
                            </span>
                        </div>
                    </div>
                </section>

            </main>

            {{-- =================================================
                 SOURCE SIDEBAR
                 ================================================= --}}
            <aside class="news-editor__side">

                <section class="news-editor__card news-editor__source-card">
                    <div class="news-editor__card-head">
                        <span class="news-editor__card-mark"></span>

                        <div>
                            <h2 class="news-editor__card-title">Source</h2>
                            <p class="news-editor__card-subtitle">Where this story originated.</p>
                        </div>

                        <span class="news-editor__source-status">
                            <span></span>
                            Source
                        </span>
                    </div>

                    <div class="news-editor__card-body">
                        <div class="news-editor__source-fields">

                            <div class="form-field">
                                <label>Source Name</label>
                                <input
                                    class="input"
                                    name="source"
                                    value="{{ $old('source') }}"
                                    placeholder="e.g. TechCrunch"
                                >
                            </div>

                            <div class="form-field">
                                <label>Source URL</label>
                                <input
                                    class="input"
                                    name="source_url"
                                    value="{{ $old('source_url') }}"
                                    placeholder="https://"
                                >
                            </div>

                        </div>
                    </div>
                </section>

                {{-- EDITOR STATUS --}}
                <section class="news-editor__card">
                    <div class="news-editor__card-head">
                        <span class="news-editor__card-mark"></span>

                        <div>
                            <h2 class="news-editor__card-title">Editor Status</h2>
                            <p class="news-editor__card-subtitle">Quick publishing overview.</p>
                        </div>
                    </div>

                    <div class="news-editor__card-body">
                        <div class="news-editor__quick-status">

                            <div class="news-editor__status-item">
                                <div class="news-editor__status-label">Mode</div>
                                <div class="news-editor__status-value blue">
                                    {{ $item ? 'Editing' : 'Creating' }}
                                </div>
                            </div>

                            <div class="news-editor__status-item">
                                <div class="news-editor__status-label">Verify</div>
                                <div class="news-editor__status-value orange">
                                    {{ ucfirst(str_replace('_', ' ', $old('verification_status', 'unverified'))) }}
                                </div>
                            </div>

                            <div class="news-editor__status-item">
                                <div class="news-editor__status-label">Action</div>
                                <div class="news-editor__status-value green">Ready</div>
                            </div>

                        </div>
                    </div>
                </section>

            </aside>

        </div>

    </form>

</div>

@endsection