@extends('layouts.master')
@section('content')
<div class="right_panel rl-announcement-create">
    <nav class="rl-inline-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('announcements.index') }}">Announcements</a><span aria-hidden="true">›</span><span>Edit Announcement</span></nav>
    <div class="rl-page-heading"><div><h1>Edit Announcement</h1><p>Update the message shown to network members.</p></div></div>
    <section class="rl-card rl-announcement-form-card">
        @include('announcements.partials.form', ['action' => route('announcements.update', $announcement), 'method' => 'PUT', 'submitLabel' => 'Save Changes'])
    </section>
</div>
@endsection
@section('footer')
<script>document.getElementById('announcement-attachment').addEventListener('change',function(){document.querySelector('[data-file-name]').textContent=this.files.length?this.files[0].name:'Replace attached file';});</script>
@endsection
