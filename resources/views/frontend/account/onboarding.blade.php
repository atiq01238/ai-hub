@extends('frontend.layouts.app')
@section('title','Personalize My AI Orbit')
@push('styles')<link rel="stylesheet" href="{{ asset('css/frontend/intelligence.css') }}">@endpush
@section('content')
<section class="onboard-shell"><div class="onboard-card">
<div class="onboard-progress"><span></span><span></span><span></span></div>
<span class="onboard-kicker"><i data-lucide="sparkles"></i> PERSONALIZE YOUR AI ORBIT</span>
<h1>What should AI Orbit find for you?</h1><p>These choices tune recommendations. You can change them later.</p>
@if($errors->any())<div class="onboard-error">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('account.onboarding.store') }}">@csrf
<h2>1. Your interests</h2><div class="choice-grid">@foreach(['Coding','Writing','Research','Image','Video','Audio','Productivity','Marketing','Data','Automation'] as $v)<label><input type="checkbox" name="interests[]" value="{{ $v }}" @checked(in_array($v,old('interests',$preference?->interests??[])))><span><i data-lucide="check"></i>{{ $v }}</span></label>@endforeach</div>
<h2>2. How do you use AI?</h2><div class="choice-grid compact">@foreach(['Personal','Freelance','Startup','Business','Education','Development','Content'] as $v)<label><input type="checkbox" name="use_cases[]" value="{{ $v }}" @checked(in_array($v,old('use_cases',$preference?->use_cases??[])))><span><i data-lucide="check"></i>{{ $v }}</span></label>@endforeach</div>
<h2>3. Experience level</h2><div class="choice-grid three">@foreach(['beginner'=>'Beginner','intermediate'=>'Intermediate','advanced'=>'Advanced'] as $k=>$v)<label><input type="radio" name="experience_level" value="{{ $k }}" @checked(old('experience_level',$preference?->experience_level)===$k)><span>{{ $v }}</span></label>@endforeach</div>
<button class="onboard-submit">Build my personalized AI Orbit <i data-lucide="arrow-right"></i></button>
</form></div></section>
@endsection