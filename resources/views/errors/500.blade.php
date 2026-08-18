@extends('errors.layout')
@section('title','Server Error — AI Hub')
@section('content')<div class="e-code">Error 500</div><h1>Something went wrong.</h1><p>AI Hub could not complete this request. The issue may be temporary. Try again, return home, or revisit the page after the service recovers.</p><div class="e-actions"><a class="e-btn primary" href="{{ url()->current() }}">Try Again</a><a class="e-btn" href="{{ url('/') }}">Go Home</a></div>@endsection
