<?php
/**
 * Upload.php — validasi & penyimpanan berkas unggahan.
 *
 * Aturan: ekstensi diwhitelist, tipe MIME dicocokkan, nama berkas diacak,
 * folder tujuan diberi .htaccess yang melarang eksekusi skrip.
 */

final class Upload
{
    public const IMAGE = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    public const DOC   = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
    public const MAX_BYTES = 8388608; // 8 MB

    private const MIME = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'gif'  => ['image/gif'],
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/CDFV2', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls'  => ['application/vnd.ms-excel', 'application/CDFV2', 'application/x-ole-storage'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'ppt'  => ['application/vnd.ms-powerpoint', 'application/CDFV2', 'application/x-ole-storage'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
    ];

    /**
     * Simpan berkas unggahan dengan nama acak.
     *
     * @param array  $file    elemen dari $_FILES
     * @param string $subdir  subfolder di dalam public/uploads (mis. 'ekstra')
     * @param array  $allowed daftar ekstensi yang diizinkan
     * @param string $prefix  awalan nama berkas agar mudah dikenali
     * @return string|null nama berkas tersimpan, atau null bila ditolak
     */
    public static function store(array $file, string $subdir, array $allowed, string $prefix = ''): ?string
    {
        if (!isset($file['error'], $file['tmp_name'], $file['name']) || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            return null;
        }
        if ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > self::MAX_BYTES) {
            error_log('[SIBER upload] ukuran berkas ditolak: ' . (int) ($file['size'] ?? 0));
            return null;
        }

        $name = str_replace("\0", '', (string) $file['name']);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, array_map('strtolower', $allowed), true)) {
            error_log('[SIBER upload] ekstensi ditolak: ' . $ext);
            return null;
        }

        // Cocokkan MIME sebenarnya dengan ekstensi.
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file($file['tmp_name']);
            if (!empty(self::MIME[$ext]) && !in_array($mime, self::MIME[$ext], true)) {
                error_log('[SIBER upload] MIME tidak cocok (' . $mime . ' vs ' . $ext . ')');
                return null;
            }
        }

        $subdir = preg_replace('/[^a-zA-Z0-9_\-]/', '', $subdir);
        if ($subdir === '') {
            return null;
        }
        $dir = SIBER_ROOT . '/public/uploads/' . $subdir;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            error_log('[SIBER upload] folder tidak dapat dibuat: ' . $dir);
            return null;
        }
        self::protectDir($dir);

        $filename = ($prefix !== '' ? $prefix . '_' : '') . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $target = $dir . '/' . $filename;

        if (!@move_uploaded_file($file['tmp_name'], $target)) {
            error_log('[SIBER upload] gagal memindahkan berkas ke ' . $target);
            return null;
        }
        @chmod($target, 0644);

        return $filename;
    }

    /** Pastikan folder unggahan tidak dapat mengeksekusi skrip. */
    public static function protectDir(string $dir): void
    {
        $htaccess = rtrim($dir, '/') . '/.htaccess';
        if (is_file($htaccess)) {
            return;
        }
        $rules = "php_flag engine off\n"
            . "RemoveHandler .php .phtml .php3 .php4 .php5 .php7 .php8 .phar .pht .shtml .cgi .pl\n"
            . "AddType text/plain .php .phtml .php3 .php4 .php5 .php7 .php8 .phar .pht .shtml .cgi .pl\n"
            . "<IfModule mod_php.c>\n  php_flag engine off\n</IfModule>\n"
            . "<IfModule mod_rewrite.c>\n  RewriteEngine On\n  RewriteRule \\.(php|phtml|phar|pht|cgi|pl|sh)$ - [F,L,NC]\n</IfModule>\n"
            . "Options -Indexes\n";
        @file_put_contents($htaccess, $rules);
    }
}
