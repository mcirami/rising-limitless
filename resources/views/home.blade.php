@extends('layouts.master')
@section('content')
@php
    $profile = \LeadMax\TrackYourStats\System\Session::userData();
    $fullName = trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')) ?: $profile->user_name;
    $roleLabel = [0 => 'Network Admin', 1 => 'Administrator', 2 => 'Manager', 3 => 'Agent'][(int) $userType] ?? 'Member';
    $editUrl = $webroot . 'aff_update.php?idrep=' . $userId;
@endphp
<div class="right_panel">
    <div class="rl-page-heading"><div><h1>My Account</h1><p>Manage your profile, credentials, and network links</p></div></div>
    <div class="rl-account">
        <section class="rl-card rl-profile-hero" aria-label="Account overview">
            <span class="rl-avatar rl-avatar-large">{{ mb_strtoupper(mb_substr($fullName, 0, 1)) }}</span>
            <div><h2>{{ $fullName }}</h2><div class="rl-profile-meta"><span>{{ $email }}</span><span class="rl-badge">{{ $roleLabel }}</span></div></div>
            <div class="rl-profile-id"><strong>#{{ $userId }}</strong><small>Account ID</small></div>
        </section>
        <div class="rl-account-layout">
            <section class="rl-card rl-account-details">
                <header class="rl-card-header"><h2><i class="far fa-user" aria-hidden="true"></i>Profile Details</h2><a href="{{ $editUrl }}"><i class="far fa-edit" aria-hidden="true"></i> Edit</a></header>
                <dl class="rl-profile-details">
                    @foreach(['Full Name' => $fullName, 'Username' => $profile->user_name, 'Email' => $email, 'Phone' => $profile->cell_phone ?? '', 'Skype ID' => $profile->skype ?? ''] as $label => $value)
                        <div><dt>{{ $label }}</dt><dd>@if(trim((string) $value) !== ''){{ $value }}@else<span class="rl-empty-value">Not set</span>@endif</dd></div>
                    @endforeach
                </dl>
            </section>
            <div class="rl-account-support">
                <div class="rl-account-grid">
                    <section class="rl-card">
                        <header class="rl-card-header"><h2><i class="fas fa-lock" aria-hidden="true"></i>Security</h2></header>
                        <div class="rl-card-body"><div class="rl-security-row"><div><p>Password</p><strong aria-label="Password is hidden">••••••••••</strong></div><a class="rl-button" href="{{ $editUrl }}">Change</a></div><p class="rl-note">Keep your account secure with a strong, unique password.</p></div>
                    </section>
                    <section class="rl-card">
                        <header class="rl-card-header"><h2><i class="fas fa-id-card" aria-hidden="true"></i>Network Access</h2></header>
                        <div class="rl-card-body"><span class="rl-badge">{{ $roleLabel }}</span><p class="rl-note">Your navigation and available actions reflect your assigned account permissions.</p><a class="rl-button" style="margin-top:14px" href="/logout">Sign out</a></div>
                    </section>
                </div>
                @if($canViewPostback)
                    <section class="rl-card"><header class="rl-card-header"><h2><i class="fas fa-link" aria-hidden="true"></i>Postback URL</h2></header><div class="rl-card-body"><div class="rl-link-box"><code>{{ $postBackURL }}</code><button class="rl-button" type="button" data-copy-text="{{ $postBackURL }}">Copy link</button></div><p class="rl-note">Use your network postback URL to report conversions.</p></div></section>
                @endif
                @if((int) $userType === 2)
                    <section class="rl-card"><header class="rl-card-header"><h2><i class="fas fa-user-plus" aria-hidden="true"></i>Your Signup Link</h2></header><div class="rl-card-body"><div class="rl-link-box"><code>{{ $domain . $userId }}</code><button class="rl-button" type="button" data-copy-text="{{ $domain . $userId }}">Copy link</button></div></div></section>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
