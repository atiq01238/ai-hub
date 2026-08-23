@extends('frontend.layouts.app')
@section('title','Email Unsubscribed — AI Hub')
@section('content')
<section style="min-height:60vh;display:grid;place-items:center;padding:60px 20px;background:#070b18;color:#eef2ff"><div style="max-width:620px;text-align:center;padding:32px;border:1px solid #273457;border-radius:20px;background:#0d1428"><h1>You’re unsubscribed</h1><p style="color:#93a0bd;line-height:1.7">AI Hub intelligence emails have been turned off for {{ $user->email }}. Essential security emails such as password resets are not affected.</p><a href="{{ route('home') }}" style="display:inline-block;margin-top:14px;padding:11px 16px;border-radius:10px;background:#6e55dc;color:white">Return to AI Hub</a></div></section>
@endsection
