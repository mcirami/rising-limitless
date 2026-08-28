@php
    $profile = \LeadMax\TrackYourStats\System\Session::userData();
    $role = (int) \LeadMax\TrackYourStats\System\Session::userType();
    $roleLabel = [0 => 'Network Admin', 1 => 'Administrator', 2 => 'Manager', 3 => 'Agent'][$role] ?? 'Member';
    $displayName = trim(($profile->first_name ?? '') . ' ' . ($profile->last_name ?? '')) ?: ($profile->user_name ?? 'My account');
    $brandCompany = \LeadMax\TrackYourStats\System\Company::loadFromSession();
    $brandName = $brandCompany->getShortHand() ?: config('app.network_name', 'Rising Limitless');
    $brandLogo = $brandCompany->getImgDir() . '/logo.png';
    $hasBrandLogo = is_file(public_path($brandLogo));
    $sections = $navBar->getVisibleMenu();
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/dashboard', PHP_URL_PATH);
    $isProfileEdit = $currentPath === '/aff_update.php' && (int) request('idrep', $profile->idrep) === (int) $profile->idrep;
@endphp
<a class="rl-skip" href="#network-content">Skip to content</a>
<button type="button" class="rl-overlay" data-nav-close aria-label="Close navigation" tabindex="-1" hidden></button>
<aside class="rl-sidebar" id="network-navigation" aria-label="Network sidebar">
    <a class="rl-brand" href="/dashboard">@if($hasBrandLogo)<img class="rl-brand-logo" src="/{{ $brandLogo }}?v={{ filemtime(public_path($brandLogo)) }}" alt="">@else<span class="rl-brand-mark">{{ mb_strtoupper(mb_substr($brandName, 0, 2)) }}</span>@endif<span>{{ $brandName }}</span></a>
    <nav class="rl-navigation" aria-label="Main navigation">
        @foreach(['Management' => ['Users', 'Offers', 'Reports'], 'Settings' => ['Advertisers', 'Account']] as $group => $labels)
            @php $groupSections = array_filter($sections, fn($section) => in_array($section['label'], $labels)); @endphp
            @if(count($groupSections))
                <p class="rl-nav-label">{{ $group }}</p>
                @foreach($groupSections as $section)
                    @php
                        $selected = count(array_filter($section['items'], fn($item) => $item['active'])) > 0;
                        if (!$selected) {
                            $selected = match($section['label']) {
                                'Users' => !$isProfileEdit && (str_starts_with($currentPath, '/user/') || str_starts_with($currentPath, '/aff_')),
                                'Offers' => str_starts_with($currentPath, '/offer/') || str_starts_with($currentPath, '/offer_'),
                                'Reports' => str_starts_with($currentPath, '/report/'),
                                'Advertisers' => str_starts_with($currentPath, '/campaign_'),
                                'Account' => $isProfileEdit,
                                default => false,
                            };
                        }
                    @endphp
                    <details class="rl-nav-section {{ $selected ? 'is-active' : '' }}" @if($selected && $role !== 2) open @endif>
                        <summary><i class="{{ $section['icon'] }}" aria-hidden="true"></i><span>{{ $section['label'] }}</span><span class="rl-chevron" aria-hidden="true">⌄</span></summary>
                        <div class="rl-subnav">
                            @foreach($section['items'] as $item)
                                <a href="{{ $item['url'] }}" @if($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            @endif
        @endforeach
    </nav>
    <div class="rl-sidebar-bottom">
        <a class="rl-profile-link" href="/dashboard"><span class="rl-avatar">{{ mb_strtoupper(mb_substr($displayName, 0, 1)) }}</span><span class="rl-profile-copy"><strong>{{ $displayName }}</strong><small>{{ $roleLabel }}</small></span></a>
        <a class="rl-logout" href="/logout" aria-label="Sign out" title="Sign out"><i class="fas fa-sign-out-alt" aria-hidden="true"></i></a>
    </div>
    @if($role === 2)
        <dl class="rl-sidebar-details">
            <div><dt>Username</dt><dd>{{ $profile->user_name }}</dd></div>
            <div><dt>Email</dt><dd>{{ $profile->email }}</dd></div>
            <div><dt>Password</dt><dd><a href="/aff_update.php?idrep={{ $profile->idrep }}">Change Password</a></dd></div>
        </dl>
    @endif
</aside>
<header class="rl-topbar">
    <button type="button" class="rl-icon-button rl-menu-toggle" data-nav-open aria-controls="network-navigation" aria-expanded="false" aria-label="Open navigation"><i class="fas fa-bars" aria-hidden="true"></i></button>
    <div class="rl-breadcrumb">
        @if(str_starts_with($currentPath, '/report/'))
            <span>Reports</span><span aria-hidden="true">/</span><strong data-page-title>{{ $pageTitle ?? 'Report' }}</strong>
        @elseif($role === 2 && $currentPath === '/user/manage')
            <span>Management</span><span aria-hidden="true">/</span><strong data-page-title data-page-title-fixed>Users</strong>
        @else
            <span>{{ $brandName }}</span><span aria-hidden="true">›</span><strong data-page-title>{{ $pageTitle ?? 'Workspace' }}</strong>
        @endif
    </div>
    <div class="rl-topbar-actions">
    <button type="button" class="rl-icon-button" data-theme-toggle aria-label="Switch to dark theme" aria-pressed="false"><i class="far fa-moon" aria-hidden="true"></i></button>
    @if($role === 2)
        <a href="/notifications.php" class="rl-icon-button" aria-label="Notifications" title="Notifications"><i class="far fa-bell" aria-hidden="true"></i></a>
        <a href="/logout" class="rl-icon-button" aria-label="Log out" title="Log out"><i class="fas fa-sign-out-alt" aria-hidden="true"></i></a>
    @endif
    </div>
</header>
