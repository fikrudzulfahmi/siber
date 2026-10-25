/* ==========================================================================
   SiBer — notifikasi
   - SiberToast.success/error/warning/info : toast pojok kanan atas
   - SiberConfirm(...)                    : konfirmasi SweetAlert (modal)
   - data-confirm="pesan"                 : ganti window.confirm bawaan
   - alert() bawaan browser dialihkan ke toast
   ========================================================================== */
(function () {
    'use strict';

    var ICONS = {
        success: 'check_circle',
        error: 'error',
        warning: 'warning',
        info: 'info'
    };
    var TITLES = {
        success: 'Berhasil',
        error: 'Gagal',
        warning: 'Perhatian',
        info: 'Informasi'
    };
    var MAX_TOAST = 4;

    function wrap() {
        var el = document.getElementById('siber-toast-wrap');
        if (!el) {
            el = document.createElement('div');
            el.id = 'siber-toast-wrap';
            el.className = 'siber-toast-wrap';
            el.setAttribute('role', 'status');
            el.setAttribute('aria-live', 'polite');
            document.body.appendChild(el);
        }
        return el;
    }

    function bersihkan(teks) {
        return String(teks == null ? '' : teks)
            .replace(/<\s*br\s*\/?>/gi, ' ')
            .replace(/<[^>]*>/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function show(type, message, opsi) {
        opsi = opsi || {};
        type = ICONS[type] ? type : 'info';

        var teks = bersihkan(message);
        if (!teks) return null;

        var box = wrap();
        while (box.children.length >= MAX_TOAST) {
            box.removeChild(box.firstChild);
        }

        var durasi = typeof opsi.durasi === 'number' ? opsi.durasi : (type === 'error' ? 6000 : 4000);

        var toast = document.createElement('div');
        toast.className = 'siber-toast siber-toast--' + type;

        var ico = document.createElement('span');
        ico.className = 'siber-toast__icon';
        ico.textContent = ICONS[type];

        var body = document.createElement('div');
        body.className = 'siber-toast__body';

        var judul = document.createElement('span');
        judul.className = 'siber-toast__title';
        judul.textContent = opsi.judul || TITLES[type];

        var isi = document.createElement('span');
        isi.textContent = teks;

        body.appendChild(judul);
        body.appendChild(isi);

        var tutup = document.createElement('button');
        tutup.type = 'button';
        tutup.className = 'siber-toast__close';
        tutup.setAttribute('aria-label', 'Tutup notifikasi');
        tutup.innerHTML = '&times;';

        var bar = document.createElement('span');
        bar.className = 'siber-toast__bar';
        bar.style.animationDuration = durasi + 'ms';

        toast.appendChild(ico);
        toast.appendChild(body);
        toast.appendChild(tutup);
        toast.appendChild(bar);
        box.appendChild(toast);

        requestAnimationFrame(function () { toast.classList.add('is-shown'); });

        var timer = null;
        function buang() {
            if (!toast.parentNode) return;
            clearTimeout(timer);
            toast.classList.add('is-hiding');
            toast.classList.remove('is-shown');
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 220);
        }

        function mulai() {
            clearTimeout(timer);
            timer = setTimeout(buang, durasi);
        }

        tutup.addEventListener('click', buang);
        toast.addEventListener('mouseenter', function () {
            clearTimeout(timer);
            bar.style.animationPlayState = 'paused';
        });
        toast.addEventListener('mouseleave', function () {
            bar.style.animationPlayState = 'running';
            mulai();
        });

        mulai();
        return toast;
    }

    function dialog(type, message, opsi) {
        opsi = opsi || {};
        var teks = bersihkan(message);

        if (typeof window.Swal === 'undefined') {
            if (type === 'error') {
                show('error', teks);
            } else {
                show(type, teks);
            }
            return;
        }

        var html = String(message == null ? '' : message)
            .replace(/<\s*br\s*\/?>/gi, '<br>')
            .replace(/<(?!\/?br\b)[^>]*>/gi, '')
            .replace(/&(?!(amp|lt|gt|quot|#39);)/g, '&amp;');

        window.Swal.fire({
            icon: type === 'warning' ? 'warning' : (type === 'success' ? 'success' : 'error'),
            title: opsi.judul || TITLES[type] || 'Informasi',
            html: html,
            confirmButtonText: opsi.tombol || 'Mengerti',
            confirmButtonColor: '#198754',
            customClass: { popup: 'siber-swal' }
        });
    }

    function konfirmasi(opsi) {
        opsi = typeof opsi === 'string' ? { teks: opsi } : (opsi || {});
        var teks = bersihkan(opsi.teks || 'Lanjutkan aksi ini?');

        if (typeof window.Swal === 'undefined') {
            return Promise.resolve(window.__siberNativeConfirm ? window.__siberNativeConfirm(teks) : false);
        }

        return window.Swal.fire({
            icon: opsi.icon || 'warning',
            title: opsi.judul || 'Konfirmasi',
            text: teks,
            showCancelButton: true,
            confirmButtonText: opsi.tombolYa || 'Ya, lanjutkan',
            cancelButtonText: opsi.tombolBatal || 'Batal',
            confirmButtonColor: opsi.bahaya ? '#dc3545' : '#198754',
            cancelButtonColor: '#8392ab',
            reverseButtons: true,
            customClass: { popup: 'siber-swal' }
        }).then(function (hasil) { return !!hasil.isConfirmed; });
    }

    window.SiberToast = {
        show: show,
        success: function (m, o) { return show('success', m, o); },
        error: function (m, o) { return show('error', m, o); },
        warning: function (m, o) { return show('warning', m, o); },
        info: function (m, o) { return show('info', m, o); }
    };
    window.SiberDialog = dialog;
    window.SiberConfirm = konfirmasi;

    /* ---- alert() bawaan browser -> toast ---- */
    window.__siberNativeConfirm = window.confirm.bind(window);
    window.alert = function (pesan) { show('info', pesan); };

    /* ---- data-confirm="..." pada link/tombol/form ---- */
    var sedangKonfirmasi = false;

    function bolehLanjut(el, lanjut) {
        var pesan = el.getAttribute('data-confirm');
        if (!pesan) { lanjut(); return; }
        konfirmasi({
            teks: pesan,
            judul: el.getAttribute('data-confirm-judul') || 'Konfirmasi',
            tombolYa: el.getAttribute('data-confirm-ya') || 'Ya, lanjutkan',
            bahaya: el.hasAttribute('data-confirm-bahaya') || /hapus|keluar|restore|reset/i.test(pesan)
        }).then(function (ya) { if (ya) lanjut(); });
    }

    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-confirm]');
        if (!el || el.tagName === 'FORM') return;
        if (sedangKonfirmasi) return;

        e.preventDefault();
        sedangKonfirmasi = true;
        bolehLanjut(el, function () {
            if (el.tagName === 'A') {
                if (el.target === '_blank') {
                    window.open(el.href, '_blank');
                } else if (el.form) {
                    el.form.submit();
                } else {
                    window.location.href = el.href;
                }
            } else if (el.form) {
                el.form.submit();
            } else {
                el.click();
            }
        });
        setTimeout(function () { sedangKonfirmasi = false; }, 400);
    }, true);

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.hasAttribute('data-confirm')) return;
        if (form.dataset.siberConfirmed === '1') {
            delete form.dataset.siberConfirmed;
            return;
        }
        e.preventDefault();
        bolehLanjut(form, function () {
            form.dataset.siberConfirmed = '1';
            form.submit();
        });
    }, true);

    /* ---- validasi HTML5 -> SweetAlert (bukan bubble bawaan) ---- */
    document.addEventListener('invalid', function (e) {
        var el = e.target;
        if (!el || !el.validationMessage) return;
        if (el.closest('.select2-hidden-accessible')) return;
        if (window.__siberValidasiAktif) return;
        window.__siberValidasiAktif = true;
        setTimeout(function () { window.__siberValidasiAktif = false; }, 600);
        dialog('error', el.validationMessage, { judul: 'Data belum lengkap' });
    }, true);

    /* ---- flash dari PHP ---- */
    function tampilkanFlash() {
        var data = window.__SIBER_FLASH;
        if (!data) return;

        (data.toast || []).forEach(function (item) {
            show(item.tipe, item.pesan);
        });

        (data.dialog || []).forEach(function (item, i) {
            setTimeout(function () { dialog(item.tipe, item.pesan); }, i * 120);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tampilkanFlash);
    } else {
        tampilkanFlash();
    }
})();
