@extends('layouts.master')
@section('content')
@php
    $permissions = \LeadMax\TrackYourStats\System\Session::permissions();
    $userList = collect($users);
    $total = $directorySummary['total'];
    $percentage = fn($count) => $total ? number_format($count / $total * 100, 1) : '0.0';
    $inactive = (int) request('showInactive', 0) === 1;
@endphp
<div class="right_panel rl-manager-directory" data-directory>
    <div class="rl-page-heading"><div><h1>View User Accounts</h1><p>Manage and access your network users from one place</p></div>
        @if($permissions->can('create_affiliates'))<a class="rl-button rl-primary" href="/aff_add.php"><span aria-hidden="true">＋</span> Add User</a>@endif
    </div>
    <div class="rl-metrics rl-directory-metrics" aria-label="Your team summary">
        <div class="rl-metric"><span class="rl-metric-label"><i class="fas fa-users" aria-hidden="true"></i> Total Users</span><strong>{{ number_format($total) }}</strong><small class="rl-directory-positive">↑ {{ number_format($directorySummary['new_this_month']) }} joined this month</small></div>
        <div class="rl-metric is-green"><span class="rl-metric-label"><i class="far fa-check-circle" aria-hidden="true"></i> Active</span><strong>{{ number_format($directorySummary['active']) }}</strong><small>{{ number_format($directorySummary['inactive']) }} inactive</small></div>
        <div class="rl-metric"><span class="rl-metric-label"><i class="far fa-user" aria-hidden="true"></i> Agents</span><strong>{{ number_format($directorySummary['agents']) }}</strong><small>{{ $percentage($directorySummary['agents']) }}% of your users</small></div>
        <div class="rl-metric"><span class="rl-metric-label"><i class="fas fa-user-tie" aria-hidden="true"></i> Managers</span><strong>{{ number_format($directorySummary['managers']) }}</strong><small>{{ $percentage($directorySummary['managers']) }}% of your users</small></div>
    </div>
    <section class="rl-card">
        <div class="rl-toolbar rl-directory-toolbar">
            @include('report.options.user-type')
            <a class="rl-button rl-inactive-toggle" href="{{ request()->fullUrlWithQuery(['showInactive' => $inactive ? 0 : 1]) }}">{{ $inactive ? 'Show Active' : 'Show Inactive' }}</a>
            <label class="rl-search"><i class="fas fa-search" aria-hidden="true"></i><input id="searchBox" type="search" data-directory-search placeholder="Search by username, email or ID…" aria-label="Search users"></label>
            <span class="rl-directory-count" data-directory-count role="status">Showing {{ $userList->count() }} users</span>
        </div>
        <div class="rl-table-scroll">
            <table class="table rl-directory-table" id="mainTable">
                <thead><tr>
                    <th aria-sort="ascending"><button class="rl-sort" data-directory-sort="id">User ID <span aria-hidden="true">⌄</span></button></th>
                    <th><button class="rl-sort" data-directory-sort="username">Username <span aria-hidden="true">⌄</span></button></th>
                    <th>Actions</th><th><button class="rl-sort" data-directory-sort="manager">Manager <span aria-hidden="true">⌄</span></button></th><th>Status</th>
                    <th><button class="rl-sort" data-directory-sort="joined">Joined <span aria-hidden="true">⌄</span></button></th>
                </tr></thead>
                <tbody>
                @foreach($userList as $account)
                    @php
                        $username = $account->user_name;
                        $initials = mb_strtoupper(mb_substr($username, 0, 2));
                        $color = ((int) $account->idrep) % 6;
                        $managerName = data_get($account, 'referrer.user_name') ?: '—';
                        $joinedAt = $account->directory_joined_at ?? '';
                    @endphp
                    <tr data-directory-row data-id="{{ $account->idrep }}" data-username="{{ $username }}" data-manager="{{ $managerName }}" data-joined="{{ $joinedAt }}" data-search="{{ $username . ' ' . $account->email . ' ' . $account->idrep }}">
                        <td class="rl-directory-id">#{{ $account->idrep }}</td>
                        <td><span class="rl-user-identity"><span class="rl-user-avatar rl-avatar-color-{{ $color }}" aria-hidden="true">{{ $initials }}</span><span>{{ $username }}</span></span></td>
                        <td class="rl-directory-actions">
                            @if($permissions->can('edit_affiliates'))<a class="rl-button rl-edit-user" href="/aff_update.php?idrep={{ $account->idrep }}">Edit</a>@endif
                            @if($permissions->can('create_affiliates'))<button type="button" class="rl-button rl-login-user" data-login-user="{{ $account->idrep }}">Login</button>@endif
                            @if($permissions->can('create_managers') && (int) request('role', 3) === 2)<a class="rl-button" href="/user/{{ $account->idrep }}/affiliates">View Agents</a>@endif
                        </td>
                        <td class="rl-directory-manager">{{ $managerName }}</td>
                        <td><span class="rl-badge {{ (int) $account->status === 1 ? 'is-active' : 'is-inactive' }}">{{ (int) $account->status === 1 ? 'Active' : 'Inactive' }}</span></td>
                        <td class="rl-directory-joined"><time @if($joinedAt) datetime="{{ $joinedAt }}" title="{{ $joinedAt }}" @endif>{{ $account->rep_timestamp ?: 'Not available' }}</time></td>
                    </tr>
                @endforeach
                <tr data-directory-empty hidden><td colspan="6" class="rl-empty">No users match your search.</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
@section('footer')
<script src="/js/network-directory.js?v={{ filemtime(public_path('js/network-directory.js')) }}" defer></script>
@endsection
