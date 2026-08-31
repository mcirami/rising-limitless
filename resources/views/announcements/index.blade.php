@extends('layouts.master')
@section('content')
@php $announcementList = collect($announcements); @endphp
<div class="right_panel">
    <div class="rl-page-heading"><div><h1>Announcements</h1><p>Manage messages displayed to managers and agents.</p></div><a class="rl-button rl-primary" href="{{ route('announcements.create') }}"><span aria-hidden="true">＋</span> Create Announcement</a></div>
    @if(session('announcement_saved'))<div class="rl-settings-message" role="status">{{ session('announcement_saved') }}</div>@endif
    @if($errors->any())<div class="rl-form-errors" role="alert">{{ $errors->first() }}</div>@endif
    <section class="rl-card" data-announcement-table>
        <div class="rl-toolbar"><label class="rl-search"><i class="fas fa-search" aria-hidden="true"></i><input type="search" data-announcement-search placeholder="Search announcements…" aria-label="Search announcements"></label></div>
        <div class="rl-table-scroll">
            <table class="table">
                <thead><tr><th>Title</th><th>Type</th><th>Text</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($announcementList as $announcement)
                    <tr data-announcement-row data-search="{{ $announcement->title.' '.$announcement->typeLabel().' '.$announcement->body }}">
                        <td><strong>{{ $announcement->title }}</strong>@if($announcement->is_pinned)<span class="rl-announcement-pin"><i class="fas fa-thumbtack" aria-hidden="true"></i> Pinned</span>@endif</td>
                        <td><span class="rl-announcement-type {{ 'is-'.str_replace('_', '-', $announcement->type) }}">{{ $announcement->typeLabel() }}</span></td>
                        <td class="rl-announcement-text">{{ $announcement->body }}</td>
                        <td class="action_column"><a class="btn btn-sm" href="{{ route('announcements.edit', $announcement) }}">Edit</a><form class="rl-inline-delete" action="{{ route('announcements.destroy', $announcement) }}" method="post" onsubmit="return confirm('Delete this announcement? This cannot be undone.');">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" type="submit">Delete</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="rl-empty">No announcements have been posted yet.</td></tr>
                @endforelse
                    <tr data-announcement-empty hidden><td colspan="4" class="rl-empty">No announcements match your search.</td></tr>
                </tbody>
            </table>
        </div>
        <div class="rl-table-footer"><span data-announcement-count role="status">{{ $announcementList->count() }} announcements</span></div>
    </section>
</div>
@endsection
@section('footer')
<script>
(function(){var input=document.querySelector('[data-announcement-search]');if(!input)return;var rows=Array.from(document.querySelectorAll('[data-announcement-row]')),empty=document.querySelector('[data-announcement-empty]'),count=document.querySelector('[data-announcement-count]');input.addEventListener('input',function(){var q=this.value.trim().toLowerCase(),shown=0;rows.forEach(function(row){var visible=!q||(row.dataset.search||'').toLowerCase().includes(q);row.hidden=!visible;if(visible)shown++;});empty.hidden=shown!==0;count.textContent=shown+' announcement'+(shown===1?'':'s');});})();
</script>
@endsection
