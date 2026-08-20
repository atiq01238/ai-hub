@extends('layouts.admin')
@section('title','Engagement Intelligence')
@push('styles')<link rel="stylesheet" href="{{ asset('css/pages/intelligence-upgrade.css') }}">@endpush
@section('content')
<x-page-header title="Engagement Intelligence" subtitle="Real user actions, discovery demand and community activity." :breadcrumb="['Analytics','Engagement Intelligence']">
<x-slot:actions><form method="GET"><select class="select" name="days" onchange="this.form.submit()">@foreach([7,30,90,365] as $d)<option value="{{ $d }}" @selected($days===$d)>Last {{ $d }} days</option>@endforeach</select></form></x-slot:actions>
</x-page-header>
<div class="intel-metrics">@foreach(['new_users'=>'New users','searches'=>'Searches','saves'=>'Saves','follows'=>'Follows','reviews'=>'Reviews','comments'=>'Comments','comparisons'=>'Comparisons','zero_searches'=>'Zero-result searches'] as $key=>$label)<article><span>{{ $label }}</span><strong>{{ number_format($metrics[$key]) }}</strong></article>@endforeach</div>
<div class="intel-grid"><section class="card intel-card"><div class="intel-head"><div><span>DISCOVERY DEMAND</span><h2>Top searches</h2></div></div><table class="table"><thead><tr><th>Query</th><th>Searches</th><th>Zero result</th></tr></thead><tbody>@forelse($topSearches as $row)<tr><td><b>{{ $row->query }}</b></td><td>{{ $row->total }}</td><td>{{ $row->zero_count }}</td></tr>@empty<tr><td colspan="3">No search events yet.</td></tr>@endforelse</tbody></table></section>
<section class="card intel-card"><div class="intel-head"><div><span>RETENTION SIGNAL</span><h2>Most-followed targets</h2></div></div><table class="table"><thead><tr><th>Type</th><th>ID</th><th>Follows</th></tr></thead><tbody>@forelse($topFollowed as $row)<tr><td>{{ ucfirst($row->target_type) }}</td><td>#{{ $row->target_id }}</td><td>{{ $row->total }}</td></tr>@empty<tr><td colspan="3">No follows yet.</td></tr>@endforelse</tbody></table></section></div>
@endsection