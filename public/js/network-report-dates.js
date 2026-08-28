(function () {
    'use strict';
    var from = document.getElementById('d_from');
    var to = document.getElementById('d_to');
    if (!from || !to) return;
    window.searchReportDates = function () {
        for (var input of [from, to]) {
            input.setCustomValidity('');
            var valid = /^\d{4}-\d{2}-\d{2}$/.test(input.value) && window.moment(input.value, 'YYYY-MM-DD', true).isValid();
            if (!valid) input.setCustomValidity('Enter a valid date in YYYY-MM-DD format.');
            if (!input.reportValidity()) return;
        }
        if (from.value > to.value) {
            to.setCustomValidity('The end date must be on or after the start date.');
            to.reportValidity();
            return;
        }
        var url = new URL(window.location.href);
        url.searchParams.set('d_from', from.value);
        url.searchParams.set('d_to', to.value);
        url.searchParams.set('dateSelect', document.getElementById('preDefined').value);
        // Pagination belongs to the previous range; all other filters and admin context survive.
        url.searchParams.delete('page');
        window.location.assign(url.toString());
    };
    [from, to].forEach(function (input) {
        input.addEventListener('input', function () { input.setCustomValidity(''); window.setCustom(); });
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') { event.preventDefault(); window.searchReportDates(); }
        });
    });
}());
