@extends('layouts.master')
@section('content')
<div class="right_panel">
    <div class="rl-page-heading"><div><h1>Offer Routing Flows</h1><p>Route countries through an ordered list of offers. The first match wins.</p></div><a class="rl-button rl-primary" href="{{ route('offer-flows.create') }}">＋ New flow</a></div>
    @if(session('flow_saved'))<div class="rl-settings-message" role="status">{{ session('flow_saved') }}</div>@endif
    <section class="rl-card">
        <div class="rl-table-scroll"><table class="table"><thead><tr><th>Flow</th><th>Entry offer</th><th>Steps</th><th>Final fallback</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        @forelse($flows as $flow)
            <tr><td><a href="{{ route('offer-flows.edit', $flow) }}"><strong>{{ $flow->name }}</strong></a></td><td>{{ $flow->entryOffer->offer_name ?? 'Unassigned' }}</td><td>{{ count($flow->steps) }}</td><td>{{ $flow->fallbackOffer->offer_name ?? 'Offer unavailable' }}</td><td>{{ $flow->is_active ? 'Active' : 'Draft / paused' }}</td><td>
                <a class="btn btn-sm" href="{{ route('offer-flows.edit', $flow) }}">Edit</a>
                <form class="rl-inline-delete" action="{{ route('offer-flows.duplicate', $flow) }}" method="post">@csrf<button type="submit" class="btn btn-sm">Duplicate</button></form>
                <form class="rl-inline-delete" action="{{ route('offer-flows.destroy', $flow) }}" method="post" onsubmit="return confirm('Delete this flow? Any generated individual offer rules will remain active under their offers.');">@csrf @method('DELETE')<button type="submit" class="btn btn-sm btn-danger">Delete</button></form>
            </td></tr>
        @empty
            <tr><td colspan="6" class="rl-empty">No flows yet. Create a flow, add offers in priority order, then assign an entry offer.</td></tr>
        @endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
