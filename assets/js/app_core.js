/* Shared lightweight helpers for legacy ShebaKit pages. */
(function (global) {
    'use strict';
    const core = {
        escapeHtml(value) {
            const node = document.createElement('span');
            node.textContent = value == null ? '' : String(value);
            return node.innerHTML;
        },
        formatTaka(value) {
            const amount = Number(value);
            return '৳' + (Number.isFinite(amount) ? amount : 0).toLocaleString('en-BD', { maximumFractionDigits: 2 });
        }
    };
    global.ShebaKitCore = Object.freeze(core);
})(window);
