@extends('layouts.master')
@section('content')
<div class="right_panel rl-announcement-create">
    <nav class="rl-inline-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('announcements.index') }}">Announcements</a><span aria-hidden="true">›</span><span>New Announcement</span></nav>
    <div class="rl-page-heading"><div><h1>New Announcement</h1><p>Broadcast a message to all network members.</p></div></div>
    <section class="rl-card rl-announcement-form-card">
        @include('announcements.partials.form', ['action' => route('announcements.store'), 'method' => 'POST', 'submitLabel' => 'Post Announcement'])
    </section>
</div>
@endsection
@section('footer')
<script>document.getElementById('announcement-attachment').addEventListener('change',function(){document.querySelector('[data-file-name]').textContent=this.files.length?this.files[0].name:'Click to attach a file';});</script>
@endsection
