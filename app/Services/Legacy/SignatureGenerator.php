<?php

namespace App\Services\Legacy;

/**
 * Ported from CakePHP app/Controller/Component/SignatureComponent.php
 *
 * Creates PNG signature images using GD library and a TTF font.
 */
class SignatureGenerator
{
    public function fixbbox(array $bbox): array
    {
        $xcorr = 0 - $bbox[6]; // northwest X
        $ycorr = 0 - $bbox[7]; // northwest Y

        return [
            'left'   => $bbox[6] + $xcorr,
            'top'    => $bbox[7] + $ycorr,
            'width'  => $bbox[2] + $xcorr,
            'height' => $bbox[3] + $ycorr,
        ];
    }

    public function createSignature($userid, $username): string
    {
        $height = 70;
        $text = $username;
        $formattedText = implode('', str_split($text));
        $fontFamily = public_path('fonts/Geovana.ttf');
        $fontSize = 30;

        $bbox = $this->fixbbox(imagettfbbox($fontSize, 0, $fontFamily, $formattedText));

        $img = imagecreate($bbox['width'] + 17, $height);

        imagecolorallocate($img, 255, 255, 255); // white background (first allocated = bg)
        $textColor = imagecolorallocate($img, 0, 0, 0);

        imagettftext($img, $fontSize, 0, 7, 50, $textColor, $fontFamily, $formattedText);

        $file = public_path('files/signatures/' . $userid . '.png');
        imagepng($img, $file);
        imagedestroy($img);

        return $file;
    }
}
