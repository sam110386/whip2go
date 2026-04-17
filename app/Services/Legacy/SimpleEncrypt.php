<?php

namespace App\Services\Legacy;

class SimpleEncrypt
{
    private string $cipher = 'AES-256-CBC';
    private string $key;

    public function __construct(string $password = 'dia')
    {
        $this->key = hash('md5', $password, true);
    }

    public function encrypt(string $data): string
    {
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);

        return rtrim(strtr(base64_encode($iv . $encrypted), '+/', '-_'), '=');
    }

    public function decrypt(string $data): string
    {
        $data = str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT);
        $data = base64_decode($data);

        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        return openssl_decrypt($encrypted, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
    }

    public function getKey(): string
    {
        return base64_encode($this->key);
    }
}
