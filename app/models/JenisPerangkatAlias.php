<?php
// app/models/JenisPerangkatAlias.php

/**
 * Peta label jenis perangkat LAMA → nama jenis di master saat ini, beserta pembangun
 * ekspresi SQL-nya.
 *
 * perangkat_mengajar menyimpan NAMA jenis (bukan id), dan labelnya bergeser antar tahun
 * ajaran (admin mengganti nama jenis). Akibatnya unggahan berlabel lama — mis. guru
 * mengunggah 'Pengumpulan Soal STS' padahal master kini hanya punya 'Soal STS' — TIDAK
 * PERNAH cocok kalau dicocokkan lewat nama mentah, sehingga tampil sebagai "belum upload"
 * padahal file-nya ada.
 *
 * DIPAKAI BERSAMA oleh Rekat (rekap + detail) dan Verifikasi, supaya aturannya hanya ada di
 * satu tempat. Dulu Verifikasi mencocokkan nama persis, jadi unggahan berlabel lama terbaca
 * "Belum upload" di Verifikasi sementara sudah terhitung di Rekap.
 *
 * Hanya label yang memang sudah HILANG dari master yang dipetakan. ('Analisis Ulangan
 * Harian/Proyek' sengaja tidak dipetakan: padanannya ambigu antara 'Analisis Penilaian'
 * dan 'Analisis Butir Soal'.)
 */
class JenisPerangkatAlias
{
    const PETA = [
        'Pengumpulan Soal STS'           => 'Soal STS',
        'Kaldik (APE, AHE)'              => 'Kaldik, APE, AHE',
        'Modul Ajar (RPP)'               => 'RPP / RPM',
        'Capaian Pembelajaran (CP)'      => 'CP, TP, ATP',
        'Tujuan Pembelajaran (TP)'       => 'CP, TP, ATP',
        'Alur Tujuan Pembelajaran (ATP)' => 'CP, TP, ATP',
    ];

    /**
     * Ekspresi SQL yang menormalkan label jenis tersimpan ke nama master saat ini,
     * sehingga satu aturan saja yang dipakai semua query pencocokan.
     */
    public static function ekspresi(PDO $db, $kolom = 'p.jenis_perangkat')
    {
        $kapan = '';
        foreach (self::PETA as $lama => $baru) {
            $kapan .= ' WHEN ' . $db->quote($lama) . ' THEN ' . $db->quote($baru);
        }
        return "CASE {$kolom}{$kapan} ELSE {$kolom} END";
    }
}
