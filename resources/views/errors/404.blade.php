@extends('errors.layout')
@section('title','Page Not Found — AI Hub')
@section('content')<div class="e-code">Error 404</div><h1>This page is off the map.</h1><p>The URL may be outdated, mistyped, or the resource may no longer be public. Use search or return to the AI Hub homepage.</p><div class="e-actions"><a class="e-btn primary" href="{{ url('/') }}">Back to AI Hub</a><a class="e-btn" href="{{ url('/search') }}">Search AI Hub</a></div>@endsection
