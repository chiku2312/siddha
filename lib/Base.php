<?php
require_once 'config.php';

class SecureEncryption {
    protected function encrypt($data) {
        $encryption_key = ENCRYPTION_KEY;
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $hashed_key = hash('sha512', $encryption_key, true);
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $hashed_key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    protected function decrypt($data) {
        $encryption_key = ENCRYPTION_KEY;
        $hashed_key = hash('sha512', $encryption_key, true);
        $data = base64_decode($data);
        $iv_length = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        return openssl_decrypt($encrypted, 'aes-256-cbc', $hashed_key, 0, $iv);
    }
}
