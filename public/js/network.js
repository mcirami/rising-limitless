(function () {
    'use strict';
    var root = document.documentElement;
    // Preference only; authentication/session storage is untouched.
    try { root.dataset.theme = localStorage.getItem('rl-theme') === 'light' ? 'light' : 'dark'; } catch (_) { root.dataset.theme = 'dark'; }
    function ready() {
        var toggle = document.querySelector('[data-theme-toggle]');
        function syncTheme() {
            var dark = root.dataset.theme === 'dark';
            if (!toggle) return;
            toggle.setAttribute('aria-pressed', String(dark));
            toggle.setAttribute('aria-label', 'Switch to ' + (dark ? 'light' : 'dark') + ' theme');
            toggle.innerHTML = dark
                ? '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>'
                : '<i class="far fa-moon" aria-hidden="true"></i>';
        }
        syncTheme();
        if (toggle) toggle.addEventListener('click', function () {
            root.dataset.theme = root.dataset.theme === 'dark' ? 'light' : 'dark';
            try { localStorage.setItem('rl-theme', root.dataset.theme); } catch (_) {}
            syncTheme();
        });
        var open = document.querySelector('[data-nav-open]');
        var overlay = document.querySelector('.rl-overlay');
        var sidebar = document.querySelector('.rl-sidebar');
        var mobile = window.matchMedia('(max-width: 760px)');
        function setNavigation(show) {
            document.body.classList.toggle('rl-nav-open', show);
            if (overlay) overlay.hidden = !show;
            if (open) open.setAttribute('aria-expanded', String(show));
            if (sidebar) sidebar.inert = mobile.matches && !show;
            if (show && sidebar) sidebar.querySelector('a').focus();
            else if (open && mobile.matches) open.focus();
        }
        if (sidebar) sidebar.inert = mobile.matches;
        if (open) open.addEventListener('click', function () { setNavigation(true); });
        document.querySelectorAll('[data-nav-close]').forEach(function (button) { button.addEventListener('click', function () { setNavigation(false); }); });
        document.addEventListener('keydown', function (event) {
            if (!document.body.classList.contains('rl-nav-open')) return;
            if (event.key === 'Escape') setNavigation(false);
            if (event.key === 'Tab' && sidebar) {
                var items = Array.from(sidebar.querySelectorAll('a, summary, button')).filter(function (item) { return item.getClientRects().length; });
                var first = items[0], last = items[items.length - 1];
                if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            }
        });
        mobile.addEventListener('change', function () { setNavigation(false); });
        var heading = document.querySelector('.rl-page-heading h1, .heading_holder .lft, h1');
        var breadcrumb = document.querySelector('[data-page-title]');
        if (heading && breadcrumb) {
            if (!breadcrumb.hasAttribute('data-page-title-fixed')) breadcrumb.textContent = heading.textContent.trim();
            document.title = heading.textContent.trim() + ' · ' + (document.querySelector('.rl-brand').textContent.trim().replace(/^RL\s*/, ''));
        }
        // Shared feedback and clipboard behavior for account links and offer links.
        var flash = document.createElement('div');
        flash.className = 'rl-flash'; flash.setAttribute('role', 'status');
        document.body.appendChild(flash);
        var flashTimer;
        window.networkNotice = function (message) {
            flash.textContent = message; clearTimeout(flashTimer);
            flashTimer = setTimeout(function () { flash.textContent = ''; }, 3500);
        };
        document.addEventListener('click', async function (event) {
            var button = event.target.closest('[data-copy-text]');
            if (!button) return;
            try {
                if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(button.dataset.copyText);
                else {
                    var input = document.createElement('textarea');
                    input.value = button.dataset.copyText; input.style.position = 'fixed'; input.style.opacity = '0';
                    document.body.appendChild(input); input.select();
                    var copied = document.execCommand('copy'); input.remove(); button.focus();
                    if (!copied) throw new Error('Clipboard unavailable');
                }
                window.networkNotice('Link copied to clipboard');
            } catch (_) { window.networkNotice('Unable to copy. Please select and copy the link manually.'); }
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
}());
