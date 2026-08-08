@extends('layouts.admin')
@section('title', 'Pricing Management')

@section('content')

<x-page-header title="Pricing Management" subtitle="Plans, API pricing, and credits across all tools" :breadcrumb="['Pricing', 'Pricing Plans']">
    <x-slot:actions><button class="btn btn-primary btn-sm"><i data-lucide="plus"></i> Add Pricing Plan</button></x-slot:actions>
</x-page-header>

<div class="tabs">
    <div class="tab is-active">Pricing Plans</div>
    <div class="tab">API Pricing</div>
    <a href="{{ url('/pricing/history') }}" class="tab">Price History</a>
    <a href="{{ url('/pricing/changes') }}" class="tab">Price Changes</a>
</div>

<div class="card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Tool</th><th>Plan</th><th>Monthly</th><th>Yearly</th><th>API</th><th>Credits</th><th>Limits</th><th>Last Updated</th></tr></thead>
        <tbody>
            <tr><td><b>ChatGPT</b></td><td class="text-sub">Plus</td><td class="mono">$22</td><td class="mono">$220</td><td class="mono">$3/1M</td><td class="text-sub">Unlimited*</td><td class="cell-sub">40 msgs/3hr</td><td class="cell-sub">Aug 5</td></tr>
            <tr><td><b>Claude</b></td><td class="text-sub">Pro</td><td class="mono">$20</td><td class="mono">$204</td><td class="mono">$5/1M</td><td class="text-sub">Unlimited*</td><td class="cell-sub">45 msgs/5hr</td><td class="cell-sub">Aug 4</td></tr>
            <tr><td><b>Midjourney</b></td><td class="text-sub">Pro</td><td class="mono">$48</td><td class="mono">$480</td><td class="mono">—</td><td class="text-sub">30 hr/mo GPU</td><td class="cell-sub">Unlimited relax</td><td class="cell-sub">Aug 3</td></tr>
            <tr><td><b>Runway</b></td><td class="text-sub">Standard</td><td class="mono">$12</td><td class="mono">$144</td><td class="mono">$0.05/sec</td><td class="text-sub">625 credits</td><td class="cell-sub">—</td><td class="cell-sub">Aug 2</td></tr>
        </tbody>
    </table>
    </div>
</div>
@endsection
