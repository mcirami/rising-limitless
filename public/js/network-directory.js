(function () {
    'use strict';
    var directory = document.querySelector('[data-directory]');
    if (!directory) return;
    var rows = Array.from(directory.querySelectorAll('[data-directory-row]'));
    var tbody = directory.querySelector('tbody');
    var search = directory.querySelector('[data-directory-search]');
    var count = directory.querySelector('[data-directory-count]');
    var empty = directory.querySelector('[data-directory-empty]');
    var sort = 'id', direction = 1;
    function render() {
        var query = search.value.trim().toLowerCase();
        var found = 0;
        rows.sort(function (a, b) {
            var comparison = sort === 'id' ? Number(a.dataset.id) - Number(b.dataset.id) : a.dataset[sort].localeCompare(b.dataset[sort], undefined, {numeric: true, sensitivity: 'base'});
            return comparison * direction;
        });
        rows.forEach(function (row) {
            row.hidden = !row.dataset.search.toLowerCase().includes(query);
            if (!row.hidden) found++;
            tbody.appendChild(row);
        });
        tbody.appendChild(empty);
        empty.hidden = found > 0;
        count.textContent = 'Showing ' + found + (found === 1 ? ' user' : ' users');
    }
    search.addEventListener('input', render);
    directory.querySelectorAll('[data-directory-sort]').forEach(function (button) {
        button.addEventListener('click', function () {
            direction = sort === button.dataset.directorySort ? -direction : 1;
            sort = button.dataset.directorySort;
            directory.querySelectorAll('th').forEach(function (th) { th.removeAttribute('aria-sort'); });
            button.closest('th').setAttribute('aria-sort', direction === 1 ? 'ascending' : 'descending');
            render();
        });
    });
    directory.querySelectorAll('[data-login-user]').forEach(function (button) {
        button.addEventListener('click', function () { adminLogin(Number(button.dataset.loginUser)); });
    });
    render();
}());
