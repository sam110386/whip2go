<?php

namespace App\Helpers\Legacy;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use function in_array;
use function is_string;
use function sprintf;
use function strlen;
use function chr;
use function ord;

class Security
{
    public static $hashType = null;

    public static $hashCost = '10';

    public static function inactiveMins()
    {
        return match (config('legacy.core.security.level', 'medium')) {
            'high' => 10,
            'medium' => 100,
            'low' => 200,
            default => 300,
        };
    }

    public static function generateAuthKey()
    {
        return self::hash((string) Str::uuid());
    }

    public static function validateAuthKey($authKey)
    {
        return true;
    }

    public static function hash($string, $type = null, $salt = false)
    {
        if (empty($type)) {
            $type = self::$hashType;
        }
        $type = strtolower($type);

        if ($type === 'blowfish') {
            return self::_crypt($string, $salt);
        }

        if ($salt) {
            if (!is_string($salt)) {
                $salt = config('legacy.core.security.salt', '');
            }
            $string = "{$salt}{$string}";
        }

        if (!$type || $type === 'sha1') {
            if (function_exists('sha1')) {
                return sha1($string);
            }
            $type = 'sha256';
        }

        if ($type === 'sha256' && function_exists('mhash') && defined('MHASH_SHA256')) {
            return bin2hex(mhash(MHASH_SHA256, $string));
        }

        if (function_exists('hash')) {
            return hash($type ?? 'sha256', $string);
        }

        return md5($string);
    }

    public static function setHash($hash)
    {
        self::$hashType = $hash;
    }

    public static function setCost($cost)
    {
        if ($cost < 4 || $cost > 31) {
            Log::error(sprintf('Invalid value, cost must be between %s and %s', 4, 31));
            return null;
        }

        self::$hashCost = (string) $cost;
    }

    public static function randomBytes($length)
    {
        if (function_exists('random_bytes')) {
            return random_bytes($length);
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            return openssl_random_pseudo_bytes($length);
        }

        Log::error('You do not have a safe source of random data available. Install either the openssl extension, the mcrypt extension, or paragonie/random_compat. Falling back to an insecure random source.');

        $bytes = '';
        $byteLength = 0;
        while ($byteLength < $length) {
            $bytes .= self::hash((string) Str::uuid() . uniqid(mt_rand(), true), 'sha512', true);
            $byteLength = strlen($bytes);
        }

        return substr($bytes, 0, $length);
    }

    public static function cipher($text, $key)
    {
        if (empty($key)) {
            Log::error('You cannot use an empty key for Security::cipher()');
            return '';
        }

        srand((int) (float) config('legacy.core.security.cipherSeed'));
        $out = '';
        $keyLength = strlen($key);

        for ($i = 0, $textLength = strlen($text); $i < $textLength; $i++) {
            $j = ord(substr($key, $i % $keyLength, 1));
            while ($j--) {
                rand(0, 255);
            }
            $mask = rand(0, 255);
            $out .= chr(ord(substr($text, $i, 1)) ^ $mask);
        }

        srand();
        return $out;
    }

    public static function rijndael($text, $key, $operation)
    {
        if (empty($key)) {
            Log::error('You cannot use an empty key for Security::rijndael()');
            return '';
        }

        if (empty($operation) || !in_array($operation, ['encrypt', 'decrypt'])) {
            Log::error('You must specify the operation for Security::rijndael(), either encrypt or decrypt');
            return '';
        }

        if (strlen($key) < 32) {
            Log::error('You must use a key larger than 32 bytes for Security::rijndael()');
            return '';
        }

        $cryptKey = substr($key, 0, 32);

        if (function_exists('openssl_encrypt')) {
            $method = 'AES-256-CBC';
            $ivSize = openssl_cipher_iv_length($method);
            if ($operation === 'encrypt') {
                $iv = openssl_random_pseudo_bytes($ivSize);
                return "{$iv}$$ " . openssl_encrypt($text, $method, $cryptKey, OPENSSL_RAW_DATA, $iv);
            }
            if (substr($text, $ivSize, 2) !== '$$') {
                $iv = substr($key, strlen($key) - 32, 32);
                return rtrim(openssl_decrypt($text, $method, $cryptKey, OPENSSL_RAW_DATA, $iv), "\0");
            }
            $iv = substr($text, 0, $ivSize);
            $text = substr($text, $ivSize + 2);
            return rtrim(openssl_decrypt($text, $method, $cryptKey, OPENSSL_RAW_DATA, $iv), "\0");
        }

        // NOTE: Function 'mcrypt_encrypt' has been removed and is available up to PHP 7.2
        // if (function_exists('mcrypt_encrypt')) {
        //     $algorithm = MCRYPT_RIJNDAEL_256;
        //     $mode = MCRYPT_MODE_CBC;
        //     $ivSize = mcrypt_get_iv_size($algorithm, $mode);
        //     if ($operation === 'encrypt') {
        //         $iv = mcrypt_create_iv($ivSize, MCRYPT_RAND);
        //         return $iv . '$$' . mcrypt_encrypt($algorithm, $cryptKey, $text, $mode, $iv);
        //     }
        //     if (substr($text, $ivSize, 2) !== '$$') {
        //         $iv = substr($key, strlen($key) - 32, 32);
        //         return rtrim(mcrypt_decrypt($algorithm, $cryptKey, $text, $mode, $iv), "\0");
        //     }
        //     $iv = substr($text, 0, $ivSize);
        //     $text = substr($text, $ivSize + 2);
        //     return rtrim(mcrypt_decrypt($algorithm, $cryptKey, $text, $mode, $iv), "\0");
        // }

        return '';
    }

    protected static function _salt($length = 22)
    {
        $salt = str_replace(
            ['+', '='],
            '.',
            base64_encode(sha1(uniqid(config('legacy.core.security.salt', ''), true), true))
        );
        return substr($salt, 0, $length);
    }
    protected static function _crypt($password, $salt = false)
    {
        if ($salt === false || $salt === null || $salt === '') {
            $salt = self::_salt(22);
            $salt = vsprintf('$2a$%02d$%s', array(self::$hashCost, $salt));
        }

        $invalidCipher = (
            strpos($salt, '$2y$') !== 0 &&
            strpos($salt, '$2x$') !== 0 &&
            strpos($salt, '$2a$') !== 0
        );

        if ($salt === true || $invalidCipher || strlen($salt) < 29) {
            Log::error(
                sprintf('Invalid salt: %s for %s Please visit http://www.php.net/crypt and read the appropriate section for building %s salts.', $salt, 'blowfish', 'blowfish'),
                ['salt_length' => strlen($salt)]
            );
            return '';
        }

        return crypt($password, $salt);
    }

    public static function encrypt($plain, $key = null, $hmacSalt = null)
    {
        if (empty($plain)) {
            return "";
        }
        if ($key === null) {
            $key = config('legacy.core.security.encryptKey', '');
        }
        self::_checkKey($key, 'encrypt()');

        if ($hmacSalt === null) {
            $hmacSalt = config('legacy.core.security.salt', '');
        }

        $key = substr(hash('sha256', "{$key}{$hmacSalt}"), 0, 32);

        if (config('legacy.core.security.useOpenSsl', true) && function_exists('openssl_encrypt')) {
            $method = 'AES-256-CBC';
            $ivSize = openssl_cipher_iv_length($method);
            $iv = openssl_random_pseudo_bytes($ivSize);
            $padLength = (int) ceil((strlen($plain) ?: 1) / $ivSize) * $ivSize;
            $ciphertext = openssl_encrypt(str_pad($plain, $padLength, "\0"), $method, $key, true, $iv);
            $ciphertext = $iv . substr($ciphertext, 0, -$ivSize);

            // NOTE: Function 'mcrypt_encrypt' has been removed and is available up to PHP 7.2PHP(PHP6405)
            // } else if (function_exists('mcrypt_encrypt')) {
            //     $algorithm = MCRYPT_RIJNDAEL_128;
            //     $mode = MCRYPT_MODE_CBC;
            //     $ivSize = mcrypt_get_iv_size($algorithm, $mode);
            //     $iv = mcrypt_create_iv($ivSize, MCRYPT_DEV_URANDOM);
            //     $ciphertext = $iv . mcrypt_encrypt($algorithm, $key, $plain, $mode, $iv);

        } else {
            return "";
        }

        $hmac = hash_hmac('sha256', $ciphertext, $key);
        return "{$hmac}{$ciphertext}";
    }

    protected static function _checkKey($key, $method)
    {
        if (strlen($key) < 32) {
            throw new \Exception(sprintf('Invalid key for %s, key must be at least 256 bits (32 bytes) long.', $method));
        }
    }

    public static function decrypt($cipher, $key = null, $hmacSalt = null)
    {
        if (empty($cipher)) {
            Log::error('The data to decrypt cannot be empty.');
            return "";
        }

        if ($key === null) {
            $key = config('legacy.core.security.encryptKey', '');
        }

        self::_checkKey($key, 'decrypt()');

        if ($hmacSalt === null) {
            $hmacSalt = config('legacy.core.security.salt', '');
        }

        $key = substr(hash('sha256', $key . $hmacSalt), 0, 32);
        $macSize = 64;
        $hmac = substr($cipher, 0, $macSize);
        $cipher = substr($cipher, $macSize);
        $compareHmac = hash_hmac('sha256', $cipher, $key);

        if ($hmac !== $compareHmac) {
            return false;
        }

        if (config('legacy.core.security.useOpenSsl', true) && function_exists('openssl_decrypt')) {
            $method = 'AES-256-CBC';
            $ivSize = openssl_cipher_iv_length($method);
            $iv = substr($cipher, 0, $ivSize);
            $cipher = substr($cipher, $ivSize);
            $padding = openssl_encrypt('', $method, $key, OPENSSL_RAW_DATA, substr($cipher, -$ivSize));
            $plain = openssl_decrypt("{$cipher}{$padding}", $method, $key, OPENSSL_RAW_DATA, $iv);

            // NOTE: Function 'mcrypt_decrypt' has been removed and is available up to PHP 7.2PHP(PHP6405)
            // } else if (function_exists('mcrypt_decrypt')) {
            //     $algorithm = MCRYPT_RIJNDAEL_128;
            //     $mode = MCRYPT_MODE_CBC;
            //     $ivSize = mcrypt_get_iv_size($algorithm, $mode);
            //     $iv = substr($cipher, 0, $ivSize);
            //     $cipher = substr($cipher, $ivSize);
            //     $plain = mcrypt_decrypt($algorithm, $key, $cipher, $mode, $iv);

        } else {
            return false;
        }

        return rtrim($plain, "\0");
    }
}
