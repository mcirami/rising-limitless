(function () {
    'use strict';
    var table = document.querySelector('[data-offer-table]');
    if (!table) return;
    var rows = Array.from(table.querySelectorAll('[data-offer-row]'));
    var body = table.querySelector('tbody');
    var search = table.querySelector('[data-offer-search]');
    var type = table.querySelector('[data-offer-type]');
    var size = table.querySelector('[data-offer-page-size]');
    var pagination = table.querySelector('[data-offer-pagination]');
    var page = 1, sort = 'name', direction = 1;
    function render() {
        var query = search.value.trim().toLowerCase();
        var filtered = rows.filter(function (row) {
            return row.dataset.search.toLowerCase().includes(query) && (type.value === '' || row.dataset.type === type.value);
        });
        filtered.sort(function (a, b) {
            var result = sort === 'payout' ? Number(a.dataset.payout) - Number(b.dataset.payout) : a.dataset[sort].localeCompare(b.dataset[sort], undefined, {numeric: true});
            return result * direction;
        });
        var perPage = Number(size.value), pages = Math.max(1, Math.ceil(filtered.length / perPage));
        page = Math.min(page, pages);
        rows.forEach(function (row) { row.hidden = true; });
        filtered.slice((page - 1) * perPage, page * perPage).forEach(function (row) { row.hidden = false; body.appendChild(row); });
        var empty = table.querySelector('[data-offer-empty]');
        empty.hidden = filtered.length > 0;
        body.appendChild(empty);
        table.querySelector('[data-offer-count]').textContent = filtered.length ? 'Showing ' + ((page - 1) * perPage + 1) + '–' + Math.min(page * perPage, filtered.length) + ' of ' + filtered.length + ' offers' : 'No offers found';
        pagination.replaceChildren();
        function button(label, target, disabled, current, accessibleName) {
            var item = document.createElement('button');
            item.type = 'button'; item.className = 'rl-button'; item.textContent = label; item.disabled = disabled;
            item.setAttribute('aria-label', accessibleName || 'Page ' + label);
            if (current) item.setAttribute('aria-current', 'page');
            item.addEventListener('click', function () { page = target; render(); });
            pagination.appendChild(item);
        }
        button('‹', page - 1, page === 1, false, 'Previous page');
        var visible = new Set([1, pages, page - 1, page, page + 1]);
        var previous = 0;
        Array.from(visible).filter(function (n) { return n >= 1 && n <= pages; }).sort(function (a, b) { return a - b; }).forEach(function (n) {
            if (previous && n - previous > 1) { var ellipsis = document.createElement('span'); ellipsis.textContent = '…'; pagination.appendChild(ellipsis); }
            button(String(n), n, false, n === page); previous = n;
        });
        button('›', page + 1, page === pages, false, 'Next page');
    }
    search.addEventListener('input', function () { page = 1; render(); });
    [type, size].forEach(function (select) { select.addEventListener('change', function () { page = 1; render(); }); });
    table.querySelectorAll('[data-offer-sort]').forEach(function (button) {
        button.addEventListener('click', function () {
            direction = sort === button.dataset.offerSort ? -direction : 1;
            sort = button.dataset.offerSort;
            table.querySelectorAll('th').forEach(function (th) { th.removeAttribute('aria-sort'); });
            button.closest('th').setAttribute('aria-sort', direction === 1 ? 'ascending' : 'descending');
            page = 1; render();
        });
    });
    var domain = document.querySelector('[data-offer-domain]');
    if (domain) domain.addEventListener('change', function () {
        var url = new URL(window.location.href); url.searchParams.set('url', domain.value); window.location.assign(url.href);
    });
    document.addEventListener('click', function (event) {
        var geoToggle = event.target.closest('[data-geo-toggle]');
        if (geoToggle) {
            var expanded = geoToggle.getAttribute('aria-expanded') === 'true';
            geoToggle.closest('.rl-country-details').querySelectorAll('[data-geo-extra]').forEach(function (badge) {
                badge.hidden = expanded;
            });
            geoToggle.setAttribute('aria-expanded', String(!expanded));
            geoToggle.textContent = expanded ? geoToggle.dataset.collapsedLabel : 'Show fewer';
        }
        var deletion = event.target.closest('[data-delete-offer]');
        if (deletion && !window.confirm('Are you sure you want to delete this offer? This cannot be undone.')) event.preventDefault();
    });
    document.querySelectorAll('[data-request-offer]').forEach(function (button) {
        button.addEventListener('click', async function () {
            button.disabled = true;
            try {
                var response = await fetch(button.dataset.requestOffer, {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}});
                if (!response.ok || response.redirected) throw new Error('Request failed');
                button.textContent = 'Requested';
                if (window.networkNotice) window.networkNotice('Offer requested successfully');
            } catch (_) {
                button.disabled = false;
                if (window.networkNotice) window.networkNotice('Unable to request offer. Please try again.');
            }
        });
    });
    render();
}());
