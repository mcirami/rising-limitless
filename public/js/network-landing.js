(function () {
    'use strict';
    var root = document.documentElement;
    var themeButton = document.querySelector('[data-landing-theme-toggle]');
    function setTheme(theme) {
        root.dataset.landingTheme = theme;
        var dark = theme === 'dark';
        themeButton.setAttribute('aria-pressed', String(dark));
        themeButton.setAttribute('aria-label', 'Switch to ' + (dark ? 'light' : 'dark') + ' theme');
        themeButton.innerHTML = dark
            ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>'
            : '<i class="far fa-moon" aria-hidden="true"></i>';
    }
    try { setTheme(localStorage.getItem('rl-landing-theme') === 'light' ? 'light' : 'dark'); } catch (_) { setTheme('dark'); }
    themeButton.addEventListener('click', function () {
        var theme = root.dataset.landingTheme === 'dark' ? 'light' : 'dark';
        setTheme(theme);
        try { localStorage.setItem('rl-landing-theme', theme); } catch (_) {}
    });
    var passwordButton = document.querySelector('[data-password-toggle]');
    var password = document.getElementById('landing-password');
    passwordButton.addEventListener('click', function () {
        var show = password.type === 'password';
        password.type = show ? 'text' : 'password';
        passwordButton.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        passwordButton.setAttribute('aria-pressed', String(show));
        passwordButton.innerHTML = '<i class="far ' + (show ? 'fa-eye-slash' : 'fa-eye') + '" aria-hidden="true"></i>';
    });
}());
