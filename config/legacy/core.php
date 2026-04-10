<?php

return [
    'security' => [
        'salt' => env('LEGACY_SECURITY_SALT', ''),
        'encryptKey' => env('LEGACY_SECURITY_ENCRYPT_KEY', ''),
        'cipherSeed' => env('LEGACY_SECURITY_CIPHER_SEED', ''),
        'level' => env('LEGACY_SECURITY_LEVEL', 'medium'),
        'useOpenSsl' => env('LEGACY_SECURITY_USE_OPENSSL', true),
    ],
];
