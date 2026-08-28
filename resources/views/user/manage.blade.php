@extends('layouts.master')
@section('content')
@php
    $permissions = \LeadMax\TrackYourStats\System\Session::permissions();
    $userList = collect($users);
@endphp
<div class="right_panel">
    <div class="rl-page-heading"><div><h1>Users</h1><p>Manage your network's accounts, access, and team members</p></div>
        @if($permissions->can('create_affiliates'))<a class="rl-button rl-primary" href="/aff_add.php"><span aria-hidden="true">＋</span> Create New User</a>@endif
    </div>
    <section class="rl-card">
        <div class="rl-toolbar">
            <label class="rl-search"><i class="fas fa-search" aria-hidden="true"></i><input id="searchBox" type="search" placeholder="Search users, email, ID…" aria-label="Search users"></label>
            <div class="rl-toolbar-end">@include('report.options.user-type') @include('report.options.active')</div>
        </div>
        <div class="rl-table-scroll">
            <table class="table table_01 manage_user_table" id="mainTable">
                <thead><tr><th>User</th><th>Actions</th><th>Manager</th><th>Created</th></tr></thead>
                <tbody id="users_container">
                    @foreach($userList as $account)
                        <tr data-user-row data-search="{{ $account->user_name . ' ' . $account->email . ' ' . $account->idrep }}">
                            <td><strong>{{ $account->user_name }}</strong><span class="rl-offer-id">#{{ $account->idrep }} · {{ $account->email }}</span></td>
                            <td class="actions">
                                @if($permissions->can('edit_affiliates'))<a class="btn btn-sm" href="/aff_update.php?idrep={{ $account->idrep }}">Edit</a>@endif
                                @if($permissions->can('create_affiliates'))<button type="button" class="btn btn-sm" data-login-user="{{ $account->idrep }}">Login</button>@endif
                                @if($permissions->can('create_managers') && (int) request('role', 3) === 2)<a class="btn btn-sm" href="/user/{{ $account->idrep }}/affiliates">View Agents</a>@endif
                            </td>
                            <td>{{ data_get($account, 'referrer.user_name') ?: '—' }}</td><td class="rl-date">{{ $account->rep_timestamp }}</td>
                        </tr>
                    @endforeach
                    <tr data-user-empty hidden><td colspan="4" class="rl-empty">No users match your search.</td></tr>
                </tbody>
            </table>
        </div>
        <footer class="rl-table-footer"><span data-user-count role="status">{{ $userList->count() }} users</span></footer>
    </section>
</div>
@endsection
@section('footer')
<script>
$(function () {
    var rows = Array.from(document.querySelectorAll('[data-user-row]'));
    function filterUsers() {
        var query = document.getElementById('searchBox').value.trim().toLowerCase();
        var count = 0;
        rows.forEach(function (row) { row.hidden = !row.dataset.search.toLowerCase().includes(query); if (!row.hidden) count++; });
        document.querySelector('[data-user-empty]').hidden = count > 0;
        document.querySelector('[data-user-count]').textContent = count + (count === 1 ? ' user' : ' users');
    }
    document.getElementById('searchBox').addEventListener('input', filterUsers);
    document.querySelectorAll('[data-login-user]').forEach(function (button) {
        button.addEventListener('click', function () { adminLogin(Number(button.dataset.loginUser)); });
    });
    $('#mainTable').tablesorter({sortList: [[0, 0]], headers: {1: {sorter: false}}});
    filterUsers();
});
</script>
@endsection
