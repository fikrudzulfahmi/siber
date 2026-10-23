/**
 * Menyisipkan token CSRF ke semua form POST dan permintaan AJAX.
 * Dimuat sekali di layout; tidak mengubah kode form yang sudah ada.
 */
(function () {
    'use strict';

    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.getAttribute('content') : '';
    if (!token) {
        return;
    }

    if (window.jQuery) {
        window.jQuery.ajaxSetup({ headers: { 'X-CSRF-Token': token } });
    }

    function injectInto(form) {
        if (!form || form.tagName !== 'FORM') {
            return;
        }
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'post') {
            return;
        }
        if (form.querySelector('input[name="csrf_token"]')) {
            return;
        }
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = token;
        form.appendChild(input);
    }

    function injectAll(root) {
        if (!root || !root.querySelectorAll) {
            return;
        }
        Array.prototype.forEach.call(root.querySelectorAll('form'), injectInto);
    }

    document.addEventListener('DOMContentLoaded', function () {
        injectAll(document);
    });

    document.addEventListener('submit', function (event) {
        injectInto(event.target);
    }, true);

    if (window.MutationObserver) {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                Array.prototype.forEach.call(mutation.addedNodes || [], function (node) {
                    if (node.nodeType !== 1) {
                        return;
                    }
                    if (node.tagName === 'FORM') {
                        injectInto(node);
                    } else {
                        injectAll(node);
                    }
                });
            });
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }

    if (window.fetch) {
        var originalFetch = window.fetch;
        window.fetch = function (input, init) {
            init = init || {};
            try {
                var headers = new Headers(init.headers || (input && input.headers) || {});
                if (!headers.has('X-CSRF-Token')) {
                    headers.set('X-CSRF-Token', token);
                }
                init.headers = headers;
            } catch (err) {
                /* Header tidak bisa dibentuk: biarkan permintaan apa adanya. */
            }
            return originalFetch.call(window, input, init);
        };
    }

    if (window.XMLHttpRequest && window.XMLHttpRequest.prototype) {
        var originalOpen = window.XMLHttpRequest.prototype.open;
        var originalSend = window.XMLHttpRequest.prototype.send;
        window.XMLHttpRequest.prototype.open = function (method) {
            this.__csrfMethod = (method || 'GET').toUpperCase();
            return originalOpen.apply(this, arguments);
        };
        window.XMLHttpRequest.prototype.send = function () {
            if (this.__csrfMethod && this.__csrfMethod !== 'GET') {
                try {
                    this.setRequestHeader('X-CSRF-Token', token);
                } catch (err) {
                    /* abaikan */
                }
            }
            return originalSend.apply(this, arguments);
        };
    }

    window.siberInjectCsrf = injectAll;
})();
