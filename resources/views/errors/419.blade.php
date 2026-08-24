@extends('errors.layout')
@section('title','Session Expired — AI Orbit')
@section('content')<div class="e-code">Error 419</div><h1>Your session expired.</h1><p>For security, the form token is no longer valid. Refresh the page and submit again.</p><div class="e-actions"><a class="e-btn primary" href="{{ url()->previous() }}">Go Back</a><a class="e-btn" href="{{ url('/') }}">Go Home</a></div>@endsection
