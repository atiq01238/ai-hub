@extends('errors.layout')
@section('title','Access Denied — AI Hub')
@section('content')<div class="e-code">Error 403</div><h1>Access is restricted.</h1><p>You do not currently have permission to view this resource. If you believe this is unexpected, sign in with the correct account or return to a public area.</p><div class="e-actions"><a class="e-btn primary" href="{{ url('/') }}">Go Home</a><a class="e-btn" href="{{ url('/auth/login') }}">Sign In</a></div>@endsection
