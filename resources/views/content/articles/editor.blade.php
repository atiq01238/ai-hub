@extends('layouts.admin')
@section('title', 'Article Editor')

@section('content')

<x-page-header title="Article Editor" :breadcrumb="['Content', 'News Articles', 'Editor']">
    <x-slot:actions>
        <button class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#previewModal"><i data-lucide="eye"></i> Preview</button>
        <button class="btn btn-secondary btn-sm"><i data-lucide="save"></i> Save Draft</button>
        <button class="btn btn-secondary btn-sm"><i data-lucide="calendar-clock"></i> Schedule</button>
        <button class="btn btn-primary btn-sm"><i data-lucide="check"></i> Publish</button>
    </x-slot:actions>
</x-page-header>

<div class="grid-12">
<div class="col-8">
    <div class="card card-pad" style="margin-bottom:16px;">
        <div class="form-field" style="margin-bottom:14px;"><label>Title</label><input class="input" style="font-size:16px; padding:12px 14px;" placeholder="Article headline..." value="GPT-5.2 Turbo Explained: What Changes for Developers"></div>
        <div class="form-field" style="margin-bottom:14px;"><label>Slug</label><input class="input" value="gpt-5-2-turbo-explained"></div>
        <div class="form-field" style="margin-bottom:14px;"><label>Featured Image</label><div class="input" style="color:var(--text-lo); display:flex; align-items:center; gap:8px;"><i data-lucide="image" style="width:15px;height:15px;"></i> Upload featured image</div></div>
        <div class="form-field">
            <label>Content</label>
            <div class="card" style="background:var(--surface-2);">
                <div class="flex gap-8" style="padding:8px 10px; border-bottom:1px solid var(--border-soft);">
                    @foreach(['bold','italic','link','list','heading-2','quote','image'] as $ic)
                        <button class="icon-btn" style="width:28px;height:28px;"><i data-lucide="{{ $ic }}" style="width:14px;height:14px;"></i></button>
                    @endforeach
                </div>
                <textarea class="input" rows="12" style="border:none; border-radius:0; background:transparent;" placeholder="Write your article...">OpenAI's GPT-5.2 Turbo introduces native multi-agent orchestration, letting developers delegate complex, multi-step tasks directly to the model...</textarea>
            </div>
        </div>
    </div>

    <div class="card card-pad">
        <div class="form-field"><label>AI Summary</label><textarea class="input" rows="3" placeholder="AI-generated summary...">GPT-5.2 Turbo adds agent orchestration and a 2M token context window, aimed at enterprise automation use cases.</textarea></div>
    </div>
</div>

<div class="col-4">
    <div class="card card-pad" style="margin-bottom:16px;">
        <div class="form-section__title">Organize</div>
        <div class="form-field" style="margin-bottom:12px;"><label>Category</label><select class="select"><option>New Model</option><option>Pricing Change</option><option>Research</option></select></div>
        <div class="form-field" style="margin-bottom:12px;"><label>Tags</label><input class="input" placeholder="agents, gpt-5, openai"></div>
        <div class="form-field" style="margin-bottom:12px;"><label>Related Tools</label><input class="input" placeholder="ChatGPT"></div>
        <div class="form-field" style="margin-bottom:12px;"><label>Related Models</label><input class="input" placeholder="GPT-5.2 Turbo"></div>
        <div class="form-field"><label>Related Companies</label><input class="input" placeholder="OpenAI"></div>
    </div>

    <div class="card card-pad" style="margin-bottom:16px;">
        <div class="form-section__title">SEO</div>
        <div class="form-field" style="margin-bottom:12px;"><label>SEO Title</label><input class="input"></div>
        <div class="form-field"><label>Meta Description</label><textarea class="input" rows="3"></textarea></div>
    </div>

    <div class="card card-pad">
        <div class="form-section__title">Publish Settings</div>
        <div class="form-field" style="margin-bottom:12px;"><label>Author</label><select class="select"><option>Sarah Ahmed</option><option>Imran Khan</option></select></div>
        <div class="form-field"><label>Publish Date</label><input class="input" type="datetime-local"></div>
    </div>
</div>
</div>

@endsection
