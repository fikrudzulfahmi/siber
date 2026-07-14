<?php
// File: app/helpers/CookieSessionHandler.php

class CookieSessionHandler implements SessionHandlerInterface
{
    private $cookieName;
    private $secretKey; // Kunci untuk enkripsi

    public function __construct($cookieName = 'app_session', $secretKey = 'kunci_rahasia_anda_yang_sangat_kuat')
    {
        $this->cookieName = $cookieName;
        $this->secretKey = $secretKey;
    }

    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string
    {
        if (!isset($_COOKIE[$this->cookieName])) {
            return '';
        }
        return $this->decrypt($_COOKIE[$this->cookieName]);
    }

    public function write($id, $data): bool
    {
        $encryptedData = $this->encrypt($data);
        $options = [
            'expires' => time() + (3600 * 24), // Session berlaku 24 jam
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']), // Hanya kirim via HTTPS jika ada
            'httponly' => true,
            'samesite' => 'Lax'
        ];
        return setcookie($this->cookieName, $encryptedData, $options);
    }

    public function destroy($id): bool
    {
        if (isset($_COOKIE[$this->cookieName])) {
            unset($_COOKIE[$this->cookieName]);
            return setcookie($this->cookieName, '', time() - 3600, '/');
        }
        return true;
    }

    public function gc($maxlifetime): int
    {
        return 0;
    }

    private function encrypt($data)
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $this->secretKey, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    private function decrypt($data)
    {
        list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted_data, 'aes-256-cbc', $this->secretKey, 0, $iv);
    }
}
