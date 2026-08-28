(function () {
    'use strict';
    function ready() {
        document.querySelectorAll('[data-performance-table]').forEach(function (table) {
            var fields = Array.from(table.querySelectorAll('thead th')).map(function (th) { return th.dataset.reportField; });
            var counts = ['Clicks', 'UniqueClicks', 'FreeSignUps', 'PendingConversions', 'Conversions', 'Codes'];
            var money = ['Revenue', 'Deductions', 'EPC', 'BonusRevenue', 'ReferralRevenue', 'TOTAL'];
            table.querySelectorAll('tbody tr:not(.rl-report-empty), tfoot tr').forEach(function (row) {
                Array.from(row.cells).forEach(function (cell, index) {
                    var field = fields[index];
                    cell.dataset.reportField = field;
                    if (counts.includes(field) || money.includes(field)) {
                        var text = cell.textContent.trim();
                        var value = Number(text.replace(/[$,]/g, ''));
                        cell.classList.toggle('is-zero', text !== '' && value === 0);
                        cell.classList.toggle('is-positive', value > 0);
                        // Keep the original link/popover elements and tablesorter hooks intact.
                        if (counts.includes(field) && /^\d+$/.test(text)) {
                            var target = cell.querySelector('a') || cell;
                            if (target.childElementCount === 0) target.textContent = Number(text).toLocaleString('en-US');
                        }
                    }
                });
            });
            // Formatting happened after the legacy sorter was initialized.
            if (window.jQuery && table.config) window.jQuery(table).trigger('update');
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready); else ready();
}());
