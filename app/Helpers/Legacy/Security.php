<?php

namespace App\Helpers\Legacy;

use Illuminate\Support\Facades\Log;

class Security
{
    public static $hashType = null;
    public static $hashCost = '10';

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
            $string = $salt . $string;
        }


        if (!$type || $type === 'sha1') {
            if (function_exists('sha1')) {
                return sha1($string);
            }
            $type = 'sha256';
        }

        if ($type === 'sha256' && function_exists('mhash')) {
            return bin2hex(mhash(MHASH_SHA256, $string));
        }

        if (function_exists('hash')) {
            return hash($type, $string);
        }

        return md5($string);
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
                sprintf('Invalid salt: %s for blowfish Please visit http://www.php.net/crypt and read the appropriate section for building blowfish salts.)', $salt),
                ['salt_length' => strlen($salt)]
            );
            return '';
        }

        return crypt($password, $salt);
    }

    protected static function _salt($length = 22)
    {
        $salt = str_replace(
            array('+', '='),
            '.',
            base64_encode(sha1(uniqid(config('legacy.core.security.salt', ''), true), true))
        );
        return substr($salt, 0, $length);
    }
}
