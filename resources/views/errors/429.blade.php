@extends('errors.layout')
@section('title','Too Many Requests — AI Orbit')
@section('content')<div class="e-code">Error 429</div><h1>Too many requests.</h1><p>AI Orbit has temporarily limited this action to protect the service. Wait briefly and try again.</p><div class="e-actions"><a class="e-btn primary" href="{{ url('/') }}">Go Home</a></div>@endsection
