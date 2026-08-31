@extends('layouts.master')
@section('content')
@php
    $displayName = $profile->user_name ?: trim(($profile->first_name ?? '').' '.($profile->last_name ?? ''));
    $typeClasses = ['new_offer'=>'is-new-offer','bonus'=>'is-bonus','info'=>'is-info','payments'=>'is-payments','other'=>'is-other'];
    $totalUnique = $traffic->sum('unique_clicks');
    $totalSales = $traffic->sum('total_sales');
@endphp
<div class="right_panel rl-dashboard">
    <div class="rl-dashboard-welcome"><div><h1>Welcome back, <span>{{ $displayName }}</span> <span aria-hidden="true">👋</span></h1><p>Here’s what’s happening with your network today.</p></div><time datetime="{{ $snapshotDate->toDateString() }}">{{ $snapshotDate->format('l, F j, Y') }}</time></div>
    @if(session('announcement_saved'))<div class="rl-settings-message" role="status">{{ session('announcement_saved') }}</div>@endif
    <section class="rl-card rl-announcements-panel">
        <header class="rl-dashboard-card-header"><h2><i class="far fa-bell" aria-hidden="true"></i> Announcements</h2><span>{{ number_format($announcementCount) }} {{ \Illuminate\Support\Str::plural('post', $announcementCount) }}</span></header>
        <div class="rl-announcement-feed">
        @forelse($announcements as $announcement)
            <article class="rl-announcement {{ $announcement->is_pinned ? 'is-pinned' : '' }}">
                <div class="rl-announcement-meta"><span class="rl-announcement-type {{ $typeClasses[$announcement->type] ?? 'is-info' }}">{{ $announcement->typeLabel() }}</span>@if($announcement->is_pinned)<span class="rl-pin"><i class="fas fa-thumbtack" aria-hidden="true"></i> Pinned</span>@endif<time datetime="{{ $announcement->created_at->toIso8601String() }}">{{ $announcement->created_at->format('M j, Y') }}</time></div>
                <h3>{{ $announcement->title }}</h3>
                <p>{!! nl2br(e($announcement->body)) !!}</p>
                @if($announcement->hasAttachment())<div class="rl-announcement-attachment"><span><i class="fas fa-paperclip" aria-hidden="true"></i> {{ $announcement->attachment_name }}</span><a class="rl-button" href="/announcements/{{ $announcement->id }}/attachment"><i class="fas fa-external-link-alt" aria-hidden="true"></i> Open Attached</a></div>@endif
            </article>
        @empty
            <div class="rl-dashboard-empty"><i class="far fa-bell" aria-hidden="true"></i><strong>No announcements yet</strong><p>New network updates will appear here.</p></div>
        @endforelse
        </div>
    </section>
    <section class="rl-card rl-traffic-panel">
        <header class="rl-dashboard-card-header"><h2><i class="fas fa-wave-square" aria-hidden="true"></i> Today’s Traffic Snapshot</h2><span>{{ number_format($traffic->count()) }} {{ \Illuminate\Support\Str::plural('offer', $traffic->count()) }}</span></header>
        <div class="rl-traffic-toolbar"><span><i class="fas fa-circle" aria-hidden="true"></i> {{ $snapshotDate->format('M j, Y') }}</span><label class="rl-search"><i class="fas fa-search" aria-hidden="true"></i><input type="search" data-traffic-search placeholder="Search offers…" aria-label="Search traffic offers"></label></div>
        <div class="rl-table-scroll"><table class="table rl-traffic-table"><thead><tr><th>Offer Name</th><th>Total Unique Clicks <span aria-hidden="true">↓</span></th><th>Total Sales</th></tr></thead><tbody>
        @forelse($traffic as $row)<tr data-traffic-row data-search="{{ strtolower($row->offer_name.' '.$row->offer_id) }}"><td><strong>{{ $row->offer_name }}</strong><span>#{{ $row->offer_id }}</span></td><td>{{ number_format($row->unique_clicks) }}</td><td class="{{ $row->total_sales > 0 ? 'is-positive' : '' }}">{{ number_format($row->total_sales) }}</td></tr>
        @empty<tr data-traffic-empty><td colspan="3" class="rl-dashboard-empty">No traffic has been recorded today.</td></tr>@endforelse
        <tr data-traffic-no-results hidden><td colspan="3" class="rl-dashboard-empty">No offers match your search.</td></tr>
        </tbody>@if($traffic->isNotEmpty())<tfoot><tr><td>Totals — {{ $traffic->count() }} {{ \Illuminate\Support\Str::plural('offer', $traffic->count()) }}</td><td>{{ number_format($totalUnique) }}</td><td>{{ number_format($totalSales) }}</td></tr></tfoot>@endif</table></div>
    </section>
</div>
@endsection
@section('footer')
<script>(function(){var search=document.querySelector('[data-traffic-search]');if(!search)return;var rows=Array.from(document.querySelectorAll('[data-traffic-row]')),empty=document.querySelector('[data-traffic-no-results]');search.addEventListener('input',function(){var q=this.value.trim().toLowerCase(),shown=0;rows.forEach(function(row){row.hidden=!row.dataset.search.includes(q);if(!row.hidden)shown++;});empty.hidden=shown>0||!q;});}());</script>
@endsection
