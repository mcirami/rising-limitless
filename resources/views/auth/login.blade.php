<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('layouts.partials.network-theme-init')
    <title>Sign in · {{ config('app.network_name', 'Rising Limitless') }}</title>
    <link rel="stylesheet" href="{{ $webroot }}css/font-awesome/css/all.css">
    <link rel="stylesheet" href="{{ $webroot }}css/network.css?v={{ filemtime(public_path('css/network.css')) }}">
    <script src="{{ $webroot }}js/network.js?v={{ filemtime(public_path('js/network.js')) }}" defer></script>
</head>
<body class="rl-auth">
<main class="rl-login">
    <a class="rl-brand" href="/"><span class="rl-brand-mark">RL</span>{{ config('app.network_name', 'Rising Limitless') }}</a>
    <section class="rl-login-card">
        <h1>Welcome back</h1>
        <p>Sign in to your network account.</p>
        <form method="post">
            @csrf
            @if(request()->has('redirectUri'))<input type="hidden" name="redirectUri" value="{{ request('redirectUri') }}">@endif
            @if(isset($error))<div role="alert" class="rl-note" style="color:#c84242;margin:0 0 18px">{{ strip_tags($error) }}</div>@endif
            <label for="login-username">Username or email</label>
            <input id="login-username" type="text" name="txt_uname_email" autocomplete="username" placeholder="Enter your username" value="{{ $user->autoFillEmail }}" required autofocus>
            <label for="login-password">Password</label>
            <input id="login-password" type="password" name="txt_password" autocomplete="current-password" placeholder="Enter your password" required>
            <a href="/aff_help.php">Forgot your password?</a>
            <button class="rl-button rl-primary" type="submit" name="button" value="login">Sign in <span aria-hidden="true">→</span></button>
        </form>
    </section>
    <footer class="rl-login-footer"><span>{{ config('app.network_name', 'Rising Limitless') }} · Partner network</span><button type="button" class="rl-icon-button" data-theme-toggle aria-label="Switch to dark theme" aria-pressed="false"><i class="far fa-moon" aria-hidden="true"></i></button></footer>
</main>
</body>
</html>
