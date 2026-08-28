@php $networkName = config('app.network_name', 'Rising Limitless'); @endphp
<!DOCTYPE html>
<html lang="en" data-landing-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $networkName }} — Performance Marketing Network</title>
    <meta name="description" content="Connect your offers, affiliates, and performance reporting with {{ $networkName }}. Sign in to manage your network in one place.">
    <meta property="og:title" content="{{ $networkName }} — Performance Marketing Network">
    <meta property="og:description" content="Your offers, your partners, your performance. One connected network.">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $networkName }} — Performance Marketing Network">
    <meta name="twitter:description" content="Your offers, your partners, your performance. One connected network.">
    <link rel="stylesheet" href="/css/font-awesome/css/all.css">
    <link rel="stylesheet" href="/css/network-landing.css?v={{ filemtime(public_path('css/network-landing.css')) }}">
    <script src="/js/network-landing.js?v={{ filemtime(public_path('js/network-landing.js')) }}" defer></script>
</head>
<body class="rl-landing">
<a class="landing-skip" href="#main-content">Skip to content</a>
<header class="landing-header">
    <a class="landing-brand" href="/" aria-label="{{ $networkName }} home"><span class="landing-mark">RL</span>{{ $networkName }}</a>
    <nav class="landing-nav" aria-label="Main navigation">
        <a href="#platform">Platform</a><a href="#advertisers">Advertisers</a><a href="#affiliates">Affiliates</a><a href="#support">Support</a>
    </nav>
    <div class="landing-header-actions">
        <button type="button" class="landing-icon-button" data-landing-theme-toggle aria-label="Switch to light theme" aria-pressed="true"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg></button>
        <a class="landing-button landing-login-link" href="#sign-in">Log in</a>
        {{--<a class="landing-button landing-primary" href="/signup.php">Apply to Join <span aria-hidden="true">↗</span></a>--}}
    </div>
</header>
<main id="main-content">
    <section class="landing-hero" aria-labelledby="hero-heading">
        <div class="landing-hero-copy">
            <span class="landing-eyebrow"><span class="landing-dot"></span>Performance Marketing Network</span>
            <h1 id="hero-heading">Every conversion<br>tracked. Every<br><em>partner connected.</em></h1>
            <p class="landing-intro">{{ $networkName }} brings your offers, affiliates, and campaign performance together — with clear reporting, conversion tracking, and the tools to grow your network.</p>
            <div class="landing-stats" aria-label="Platform capabilities">
                <div><strong>CPA / CPC</strong><span>Offer types</span></div><div><strong>5</strong><span>Sub-ID variables</span></div><div><strong>S2S</strong><span>Postbacks</span></div><div><strong>GEO</strong><span>Traffic insights</span></div>
            </div>
            <div class="landing-highlights"><span><i class="fas fa-chart-line" aria-hidden="true"></i> Campaign reporting</span><span><i class="fas fa-users" aria-hidden="true"></i> Partner management</span><span><i class="fas fa-globe" aria-hidden="true"></i> Geo rules</span></div>
        </div>
        <section class="landing-signin" id="sign-in" aria-labelledby="signin-heading">
            <h2 id="signin-heading">Welcome back</h2><p>Sign in to your {{ $networkName }} account</p>
           {{-- <div class="landing-account-note"><i class="fas fa-shield-alt" aria-hidden="true"></i> One sign-in for your network account</div>--}}
            <form method="post" action="/login">
                @csrf
                <label for="landing-username">Username or email</label>
                <input id="landing-username" name="txt_uname_email" type="text" autocomplete="username" placeholder="your_username" required>
                <label for="landing-password">Password</label>
                <div class="landing-password"><input id="landing-password" name="txt_password" type="password" autocomplete="current-password" placeholder="Your password" required><button type="button" data-password-toggle aria-label="Show password" aria-controls="landing-password" aria-pressed="false"><i class="far fa-eye" aria-hidden="true"></i></button></div>
                <div class="landing-form-meta"><span><i class="fas fa-lock" aria-hidden="true"></i> Access based on your account</span><a href="/aff_help.php">Forgot password?</a></div>
                <button type="submit" name="button" value="login" class="landing-button landing-primary landing-submit">Sign in <span aria-hidden="true">→</span></button>
            </form>
        </section>
    </section>
    <div class="landing-network-strip" aria-label="Network tools"><span class="landing-strip-label"><span class="landing-dot"></span>Your Network</span><span>Offers <strong>CPA &amp; CPC</strong></span><span>Tracking <strong>Sub-IDs</strong></span><span>Reporting <strong>By country</strong></span><span>Access <strong>By role</strong></span><span>Integrations <strong>Postbacks</strong></span></div>
    <div class="landing-trust"><span><i class="fas fa-user-shield" aria-hidden="true"></i> Role-based access</span><span><i class="fas fa-globe" aria-hidden="true"></i> Geo targeting rules</span><span><i class="fas fa-chart-bar" aria-hidden="true"></i> Detailed reporting</span><span><i class="fas fa-link" aria-hidden="true"></i> Conversion postbacks</span><span><i class="fas fa-filter" aria-hidden="true"></i> IP blacklist controls</span></div>
    <section class="landing-platform landing-section" id="platform" aria-labelledby="platform-heading">
        <div class="landing-section-heading"><span class="landing-kicker">Why {{ $networkName }}</span><h2 id="platform-heading">Built for performance<br>at every step.</h2><p>From your first tracking link to your next campaign, keep your partners and performance in one connected workspace.</p></div>
        <div class="landing-feature-grid">
            @foreach([
                ['fa-chart-line', 'Campaign Reporting', 'Explore clicks, conversions, and revenue. Filter by date, offer, affiliate, or country to understand what is working.', 'A clearer view of performance'],
                ['fa-globe', 'Geo Targeting', 'Manage country and device rules for your offers and review the locations your traffic comes from.', 'Traffic that fits your offers'],
                ['fa-wallet', 'Payout Visibility', 'Review offer payouts and conversion totals with reporting that reflects your account permissions.', 'Know your numbers'],
                ['fa-shield-alt', 'Traffic Controls', 'Use IP blacklists, sub-ID controls, and offer access rules to help manage the traffic entering your network.', 'More control over your traffic'],
                ['fa-users', 'Partner Management', 'Organize agents and managers, assign offers, and give every account access to the tools it needs.', 'Your team, connected'],
                ['fa-code', 'Tracking & Postbacks', 'Connect conversion events to your offers with tracking links, five sub-ID variables, and server-to-server postbacks.', 'Built for your workflow']
            ] as [$icon, $title, $description, $note])
                <article class="landing-feature"><span class="landing-feature-icon"><i class="fas {{ $icon }}" aria-hidden="true"></i></span><h3>{{ $title }}</h3><p>{{ $description }}</p><span class="landing-feature-note">{{ $note }} <span aria-hidden="true">↗</span></span></article>
            @endforeach
        </div>
    </section>
    {{--<section class="landing-audiences landing-section" aria-label="Who the network is for">
        <article id="advertisers"><span class="landing-kicker">For Advertisers</span><h2>Your offers.<br>A connected network.</h2><p>Bring your campaigns to a network built around offer management, controlled access, and clear performance reporting.</p><a class="landing-button" href="/signup.php">Join the network <span aria-hidden="true">↗</span></a></article>
        <article id="affiliates"><span class="landing-kicker">For Affiliates</span><h2>Your traffic.<br>A clearer picture.</h2><p>Find the offers available to you, create tracking links, and follow your conversions from one place.</p><a class="landing-button landing-primary" href="/signup.php">Apply as a partner <span aria-hidden="true">↗</span></a></article>
    </section>--}}
    <section class="landing-support landing-section" id="support"><div><span class="landing-kicker">Already a partner?</span><h2>Let's keep you moving.</h2><p>Sign in to access your network. Need help getting back in? Reset your password or contact your network manager.</p></div><div><a class="landing-button landing-primary" href="#sign-in">Sign in <span aria-hidden="true">→</span></a><a class="landing-support-link" href="/aff_help.php">Reset your password</a></div></section>
</main>
<footer class="landing-footer">
    <a class="landing-brand" href="/">
        <span class="landing-mark">RL</span>{{ $networkName }}
    </a>
    <span>© {{ date('Y') }} {{ $networkName }}. All rights reserved.</span>
    {{--<nav aria-label="Footer navigation">
        <a href="#platform">Platform</a><a href="/signup.php">Apply to join</a>
        <a href="#support">Support</a></nav>--}}
</footer>
</body>
</html>
