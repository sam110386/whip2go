<?php

namespace App\Services\Legacy;

class SignatureService
{
    private function fixbbox($bbox)
    {
        $xcorr = 0 - $bbox[6]; // northwest X
        $ycorr = 0 - $bbox[7]; // northwest Y
        $tmp_bbox['left'] = $bbox[6] + $xcorr;
        $tmp_bbox['top'] = $bbox[7] + $ycorr;
        $tmp_bbox['width'] = $bbox[2] + $xcorr;
        $tmp_bbox['height'] = $bbox[3] + $ycorr;

        return $tmp_bbox;
    }

    public function createSignature($userid, $username)
    {
        if (!extension_loaded('gd')) {
            throw new \Exception('GD library is not installed or enabled.');
        }

        $width = 120;
        $height = 70;
        $text = $username;
        $formattedText = implode('', str_split($text));
        
        // Use a path that works in the new project. Assuming fonts are in public/fonts
        $fontFamily = public_path('fonts/Geovana.ttf');
        
        if (!file_exists($fontFamily)) {
            // Fallback font or throw error if critical
            \Log::warning("Font not found at $fontFamily. Ensure fonts are ported.");
            // For now, if font is missing, we might need a placeholder or a different font.
            // But usually, the fonts folder should be moved.
        }

        $fontSize = 30;

        $bbox_raw = imagettfbbox($fontSize, 0, $fontFamily, $formattedText);
        if (!$bbox_raw) {
            // Fallback if failing to read font
            return false;
        }
        
        $bbox = $this->fixbbox($bbox_raw);

        $img = imagecreate($bbox['width'] + 17, $height);

        // Create some colors
        $white = imagecolorallocate($img, 255, 255, 255);
        $textColor = imagecolorallocate($img, 0, 0, 0);

        imagettftext($img, $fontSize, 0, 7, 50, $textColor, $fontFamily, $formattedText);
        
        $file_dir = public_path('files/signatures/');
        if (!file_exists($file_dir)) {
            mkdir($file_dir, 0755, true);
        }

        $file_path = $file_dir . $userid . ".png";
        imagepng($img, $file_path);
        imagedestroy($img);
        
        return $file_path;
    }
}
