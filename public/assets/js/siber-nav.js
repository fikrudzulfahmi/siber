/* SIBER — bottom sheet "Lainnya" pada navigasi mobile. */
(function () {
    'use strict';

    var sheet = document.getElementById('siber-sheet');
    var backdrop = document.getElementById('siber-sheet-backdrop');
    var toggle = document.getElementById('siber-sheet-toggle');
    var closeBtn = document.getElementById('siber-sheet-close');

    if (!sheet || !toggle) {
        return;
    }

    var closing = false;

    function openSheet() {
        closing = false;
        sheet.hidden = false;
        if (backdrop) {
            backdrop.hidden = false;
        }
        // paksa reflow supaya transisi jalan
        void sheet.offsetHeight;
        sheet.classList.add('is-open');
        if (backdrop) {
            backdrop.classList.add('is-open');
        }
        document.body.classList.add('siber-sheet-open');
        toggle.setAttribute('aria-expanded', 'true');
    }

    function closeSheet() {
        if (closing || sheet.hidden) {
            return;
        }
        closing = true;
        sheet.classList.remove('is-open');
        if (backdrop) {
            backdrop.classList.remove('is-open');
        }
        document.body.classList.remove('siber-sheet-open');
        toggle.setAttribute('aria-expanded', 'false');
        window.setTimeout(function () {
            sheet.hidden = true;
            if (backdrop) {
                backdrop.hidden = true;
            }
            closing = false;
        }, 280);
    }

    toggle.addEventListener('click', function () {
        if (sheet.classList.contains('is-open')) {
            closeSheet();
        } else {
            openSheet();
        }
    });

    closeBtn && closeBtn.addEventListener('click', closeSheet);
    backdrop && backdrop.addEventListener('click', closeSheet);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeSheet();
        }
    });

    // Menu yang dituju langsung menutup sheet (hindari sheet menggantung
    // ketika halaman berikutnya dimuat dari cache).
    sheet.addEventListener('click', function (e) {
        if (e.target.closest('a')) {
            closeSheet();
        }
    });

    // Kalau layar dibesarkan ke desktop, bersihkan state sheet.
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 1200) {
            closeSheet();
        }
    });
})();
