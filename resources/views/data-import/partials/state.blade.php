@if(($row['state'] ?? null) === 'ready')
    <span class="import-state is-ready">
        <i data-lucide="circle-check"></i>
        Ready
    </span>
@elseif(($row['state'] ?? null) === 'existing')
    <span class="import-state is-existing">
        <i data-lucide="copy-check"></i>
        Existing
    </span>
@else
    <span class="import-state is-invalid">
        <i data-lucide="circle-x"></i>
        Invalid
    </span>

    @foreach(($row['errors'] ?? []) as $error)
        <small class="import-error">{{ $error }}</small>
    @endforeach
@endif
