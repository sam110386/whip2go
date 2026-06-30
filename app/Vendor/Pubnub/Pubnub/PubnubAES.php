<?php
 
namespace Pubnub\Pubnub;


class PubnubAES
{
    public function decrypt($cipherText, $cipherKey)
    {
        $iv = "0123456789012345";

        if (gettype($cipherText) != "string") {
            return "DECRYPTION_ERROR";
        }

        $decoded = base64_decode($cipherText);

        $shaCipherKey = hash("sha256", $cipherKey);
        $paddedCipherKey = substr($shaCipherKey, 0, 32);

        $decrypted = openssl_decrypt($decoded, 'aes-256-cbc', $paddedCipherKey, OPENSSL_RAW_DATA, $iv);

        return $decrypted;
    }
    
    public function encrypt($plain_text, $cipherKey)
    {
        $iv = "0123456789012345";

        $shaCipherKey = hash("sha256", $cipherKey);
        $paddedCipherKey = substr($shaCipherKey, 0, 32);

        $encrypted = openssl_encrypt($plain_text, 'aes-256-cbc', $paddedCipherKey, OPENSSL_RAW_DATA, $iv);

        return base64_encode($encrypted);
    }

    public function pkcs5Pad($text, $blockSize)
    {
        $pad = $blockSize - (strlen($text) % $blockSize);
        return $text . str_repeat(chr($pad), $pad);
    }
    
    public function unPadPKCS7($data, $blockSize)
    {
        $length = strlen($data);
        if ($length > 0) {
            $first = substr($data, -1);

            if (ord($first) <= $blockSize) {
                for ($i = $length - 2; $i > 0; $i--) {
                    if (ord($data[$i]) != ord($first)) {
                        break;
                    }
                }

                return substr($data, 0, $i + 1);
            }
        }
        return $data;
    }

    public function isBlank($word)
    {
        if (($word == null) || ($word == false)) {
            return true;
        } else {
            return false;
        }
    }
}
