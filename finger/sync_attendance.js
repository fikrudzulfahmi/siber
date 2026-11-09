/**
 * Service sinkronisasi presensi — versi hemat beban.
 *
 * Perubahan utama dibanding versi lama:
 *  1. Satu request per siklus (pull.php melakukan tarik + insert + kirim ke hosting),
 *     bukan dua request terpisah seperti sebelumnya.
 *  2. Interval mengikuti jam sibuk, bukan 30 detik 24 jam:
 *       - jam sibuk (06:00-09:00, 13:00-15:00) : 30 detik
 *       - jam kerja lain (09:00-13:00, 15:00-18:00) : 60 detik
 *       - di luar itu : 300 detik
 *  3. Data baru langsung didorong ke hosting di request yang sama (tanpa jeda 2 detik,
 *     tanpa heartbeat terpisah).
 *  4. Tidak ada tumpang tindih: siklus berikutnya menunggu siklus sebelumnya selesai.
 */
const fs = require('fs');
const path = require('path');

const BASE = process.env.FINGER_BASE || 'http://localhost/finger';
const LOG_FILE = path.join(__dirname, 'sync.log');
const TIMEOUT_MS = 45000;

// [jamMulai, jamSelesai, intervalDetik]
const JADWAL = [
    [6, 9, 30], // jam sibuk pagi (datang)
    [12, 15, 30], // jam pulang — data menunjukkan scan mulai ~12:30, bukan 13:00
    [9, 12, 60], // jam kerja biasa
    [15, 18, 60], // jam kerja biasa sore
];
const INTERVAL_LUAR_JAM = 300; // 5 menit di luar jam kerja

function intervalSekarang(d = new Date()) {
    const jam = d.getHours() + d.getMinutes() / 60;
    for (const [mulai, selesai, detik] of JADWAL) {
        if (jam >= mulai && jam < selesai) return detik;
    }
    return INTERVAL_LUAR_JAM;
}

function log(baris) {
    const teks = `[${new Date().toLocaleString('id-ID')}] ${baris}`;
    console.log(teks);
    try {
        fs.appendFileSync(LOG_FILE, teks + '\n');
    } catch {
        /* logging tidak boleh menghentikan layanan */
    }
}

async function ambil(url) {
    const ctrl = new AbortController();
    const timer = setTimeout(() => ctrl.abort(), TIMEOUT_MS);
    try {
        const res = await fetch(url, { signal: ctrl.signal });
        const teks = await res.text();
        return { status: res.status, teks };
    } finally {
        clearTimeout(timer);
    }
}

function ringkas(j) {
    if (!j || typeof j !== 'object') return 'balasan tidak dikenali';
    if (j.busy) return 'dilewati (siklus sebelumnya masih jalan)';
    if (j.ok === false && j.error) return `GAGAL: ${j.error}`;

    const mesin = Array.isArray(j.machines)
        ? j.machines
              .map((m) => {
                  const sn = String(m.sn || '').slice(-6);
                  if (m.error) return `${sn}=ERROR`;
                  if (!m.result) return `${sn}=kosong`;
                  return `${sn}=${m.fetched}`;
              })
              .join(' ')
        : '-';

    return `${j.inserted || 0} baru | ${mesin} | hosting: ${j.hosting?.status || '-'}`;
}

let sedangJalan = false;

async function siklus() {
    if (sedangJalan) {
        log('siklus sebelumnya belum selesai — lewati');
        return;
    }
    sedangJalan = true;
    const mulai = Date.now();
    try {
        const r = await ambil(`${BASE}/pull.php`);
        let j = null;
        try {
            j = JSON.parse(r.teks);
        } catch {
            log(`ERROR: balasan bukan JSON (HTTP ${r.status}): ${r.teks.slice(0, 150)}`);
            return;
        }
        const ms = Date.now() - mulai;
        log(`Tarik (${ms}ms): ${ringkas(j)}`);
    } catch (err) {
        const pesan = err.name === 'AbortError' ? `timeout ${TIMEOUT_MS / 1000}s` : err.message;
        log(`ERROR tarik: ${pesan}`);
    } finally {
        sedangJalan = false;
    }
}

async function loop() {
    await siklus();
    const detik = intervalSekarang();
    setTimeout(loop, detik * 1000);
}

log('=== SERVICE SINKRONISASI FINGERPRINT AKTIF ===');
log(
    `jadwal: sibuk 06-09 & 13-15 = 30s | 09-13 & 15-18 = 60s | lainnya = ${INTERVAL_LUAR_JAM}s | log: ${LOG_FILE}`
);
loop();
