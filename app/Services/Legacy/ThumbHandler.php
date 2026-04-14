<?php

namespace App\Services\Legacy;

/**
 * Ported from CakePHP app/Controller/Component/ThumbComponent.php
 * Image resizing/thumbnailing using pure PHP GD functions.
 */
class ThumbHandler
{
    public bool $save_to_file = true;
    public int $image_quality = 100;
    public int $image_type = 0;
    public int $max_x = 100;
    public int $max_y = 100;
    public string $image_folder = 'img';
    public string $thumb_folder = 'img/category/Thumb';

    /** @var resource|\GdImage|null */
    public $img_res = null;

    public function isExists(string $image_name, bool $thumb = false): bool
    {
        $fldr = $thumb ? $this->thumb_folder : $this->image_folder;
        return file_exists($fldr . '/' . $image_name);
    }

    /**
     * @return resource|bool|null
     */
    public function saveImage(string $filename, bool $thumb = true)
    {
        $res = null;
        if (!$this->isExists($filename, $thumb)) {
            switch ($this->image_type) {
                case 1:
                    if ($this->save_to_file) {
                        $res = imagegif($this->img_res, $this->thumb_folder . '/' . $filename, 100);
                        chmod($this->thumb_folder . '/' . $filename, 0777);
                    } else {
                        header('Content-type: image/gif');
                        $res = imagegif($this->img_res);
                    }
                    break;
                case 2:
                    if ($this->save_to_file) {
                        $res = imagejpeg($this->img_res, $filename, $this->image_quality);
                    } else {
                        header('Content-type: image/jpeg');
                        $res = imagejpeg($this->img_res, null, $this->image_quality);
                    }
                    break;
                case 3:
                    if ($this->save_to_file) {
                        $res = imagepng($this->img_res, $filename, $this->image_quality);
                    } else {
                        header('Content-type: image/png');
                        $res = imagepng($this->img_res);
                    }
                    break;
            }
        }
        return $res;
    }

    /**
     * @return resource|\GdImage|null
     */
    public function imageCreateFromType(string $filename)
    {
        $res = null;
        switch ($this->image_type) {
            case 1:
                $res = imagecreatefromgif($filename);
                break;
            case 2:
                $res = imagecreatefromjpeg($filename);
                break;
            case 3:
                $res = imagecreatefrompng($filename);
                break;
        }
        return $res;
    }

    /**
     * @return string|false
     */
    public function createThumbnail(string $from_file)
    {
        if (!$this->isExists($from_file)) {
            return false;
        }
        if ($this->isExists('thumb_' . $from_file, true)) {
            return 'thumb_' . $from_file;
        }

        [$orig_x, $orig_y, $orig_img_type, $img_sizes] = getimagesize($this->image_folder . '/' . $from_file);
        $this->image_type = $orig_img_type;

        if (!$this->save_to_file) {
            switch ($orig_img_type) {
                case 1:
                    header('Content-type: image/gif');
                    readfile($this->image_folder . '/' . $from_file);
                    break;
                case 2:
                    header('Content-type: image/jpeg');
                    readfile($this->image_folder . '/' . $from_file);
                    break;
                case 3:
                    header('Content-type: image/png');
                    readfile($this->image_folder . '/' . $from_file);
                    break;
            }
        }

        [$width, $height] = getimagesize($this->image_folder . '/' . $from_file);
        $new_width = 120;
        $new_height = 100;

        $hx = (100 / ($width / $new_width)) * 0.01;
        $hx = @round($new_height * $hx);

        $wx = (100 / ($new_height / ($height ?? 1))) * 0.01;
        $wx = @round($new_width * $wx);

        if ($hx < $new_width) {
            $new_height = (100 / ($width / $new_width)) * 0.01;
            $new_height = @round($height * $new_height);
        } else {
            $new_width = (100 / ($height / $new_height)) * 0.01;
            $new_width = @round($width * $new_width);
        }

        if ($this->image_type == 1) {
            $ni = imagecreate($new_width, $new_height);
        } else {
            $ni = imagecreatetruecolor($new_width, $new_height);
        }
        $ni = imagecreatetruecolor($new_width, $new_height);

        $image = $this->imageCreateFromType($this->image_folder . '/' . $from_file);
        imagecopyresampled($ni, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        copy($this->image_folder . '/' . $from_file, $this->thumb_folder . '/thumb_' . $from_file);

        if (imagegif($ni, $this->thumb_folder . '/thumb_' . $from_file, 100)) {
            return 'thumb_' . $from_file;
        }

        return false;
    }

    /**
     * Resize an image with background fill, maintaining aspect ratio.
     *
     * @param string      $id          Image filename
     * @param string|null &$mime       Receives the MIME type (by reference)
     * @param string      $imgFolder   Path to image folder
     * @param int|false   $newWidth
     * @param int|false   $newHeight
     * @param string      $bgcolor     Hex colour for background bars
     * @param bool        $resample    Use resampling (true) or simple resize
     * @param bool        $cache       Write cache file
     * @param string|false $cacheFolder
     * @param bool        $cacheClear
     * @param string|false $tempFolder
     * @return void
     */
    public function getResized(
        string $id,
        ?string &$mime,
        string $imgFolder,
        $newWidth = false,
        $newHeight = false,
        string $bgcolor = '#F8B031',
        bool $resample = true,
        bool $cache = false,
        $cacheFolder = false,
        bool $cacheClear = false,
        $tempFolder = false
    ): void {
        $img = $imgFolder . $id;
        [$oldWidth, $oldHeight, $type] = getimagesize($img);
        $ext = $this->image_type_to_extension($type);
        $mime = image_type_to_mime_type($type);

        if ($cache && is_writable($cacheFolder)) {
            $dest = $cacheFolder . $id;
        } elseif (is_writable($tempFolder)) {
            $dest = $tempFolder . $id;
        } else {
            return;
        }

        if ($newWidth || $newHeight) {
            if ($cacheClear && file_exists($dest)) {
                unlink($dest);
            }

            if (($newWidth > $oldWidth) && ($newHeight > $oldHeight)) {
                $applyWidth = $oldWidth;
                $applyHeight = $oldHeight;
            } else {
                if (($newWidth / $newHeight) < ($oldWidth / $oldHeight)) {
                    $applyHeight = $newWidth * $oldHeight / $oldWidth;
                    $applyWidth = $newWidth;
                } else {
                    $applyWidth = $newHeight * $oldWidth / $oldHeight;
                    $applyHeight = $newHeight;
                }
            }

            switch ($ext) {
                case 'gif':
                    $oldImage = imagecreatefromgif($img);
                    $newImage = imagecreatetruecolor($newWidth, $newHeight);
                    break;
                case 'png':
                    $oldImage = imagecreatefrompng($img);
                    $newImage = imagecreatetruecolor($newWidth, $newHeight);
                    break;
                case 'jpg':
                case 'jpeg':
                    $oldImage = imagecreatefromjpeg($img);
                    $newImage = imagecreatetruecolor($newWidth, $newHeight);
                    break;
                default:
                    return;
            }

            $red = 15;
            $green = 117;
            $blue = 188;
            sscanf($bgcolor, '%2x%2x%2x', $red, $green, $blue);
            $newColor = imagecolorallocate($newImage, $red, $green, $blue);
            imagefill($newImage, 0, 0, $newColor);

            if ($resample) {
                imagecopyresampled(
                    $newImage, $oldImage,
                    (int)(($newWidth - $applyWidth) / 2), (int)(($newHeight - $applyHeight) / 2),
                    0, 0,
                    (int)$applyWidth, (int)$applyHeight,
                    $oldWidth, $oldHeight
                );
            } else {
                imagecopyresized(
                    $newImage, $oldImage,
                    (int)(($newWidth - $applyWidth) / 2), (int)(($newHeight - $applyHeight) / 2),
                    0, 0,
                    (int)$applyWidth, (int)$applyHeight,
                    $oldWidth, $oldHeight
                );
            }

            switch ($ext) {
                case 'gif':
                case 'png':
                    imagepng($newImage, $dest);
                    break;
                case 'jpg':
                case 'jpeg':
                    imagejpeg($newImage, $dest);
                    break;
            }
        }
    }

    /**
     * Resize/stretch image to exact dimensions (banner-style).
     * Same signature as getResized but forces exact width/height.
     */
    public function getBanner(
        string $id,
        ?string &$mime,
        string $imgFolder,
        $newWidth = false,
        $newHeight = false,
        string $bgcolor = '#FFFFFF',
        bool $resample = true,
        bool $cache = false,
        $cacheFolder = false,
        bool $cacheClear = false,
        $tempFolder = false
    ): void {
        $img = $imgFolder . $id;
        [$oldWidth, $oldHeight, $type] = getimagesize($img);
        $ext = $this->image_type_to_extension($type);
        $mime = image_type_to_mime_type($type);

        if ($cache && is_writable($cacheFolder)) {
            $dest = $cacheFolder . $id;
        } elseif (is_writable($tempFolder)) {
            $dest = $tempFolder . $id;
        } else {
            return;
        }

        $applyWidth = $newWidth;
        $applyHeight = $newHeight;

        if (!$newWidth && !$newHeight) {
            return;
        }

        if ($cacheClear && file_exists($dest)) {
            unlink($dest);
        }

        switch ($ext) {
            case 'gif':
                $oldImage = imagecreatefromgif($img);
                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                break;
            case 'png':
                $oldImage = imagecreatefrompng($img);
                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                break;
            case 'jpg':
            case 'jpeg':
                $oldImage = imagecreatefromjpeg($img);
                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                break;
            default:
                return;
        }

        $red = 255;
        $green = 255;
        $blue = 255;
        sscanf($bgcolor, '%2x%2x%2x', $red, $green, $blue);
        $newColor = imagecolorallocate($newImage, $red, $green, $blue);
        imagefill($newImage, 0, 0, $newColor);

        if ($resample) {
            imagecopyresampled(
                $newImage, $oldImage,
                (int)(($newWidth - $applyWidth) / 2), (int)(($newHeight - $applyHeight) / 2),
                0, 0,
                (int)$applyWidth, (int)$applyHeight,
                $oldWidth, $oldHeight
            );
        } else {
            imagecopyresized(
                $newImage, $oldImage,
                (int)(($newWidth - $applyWidth) / 2), (int)(($newHeight - $applyHeight) / 2),
                0, 0,
                (int)$applyWidth, (int)$applyHeight,
                $oldWidth, $oldHeight
            );
        }

        switch ($ext) {
            case 'gif':
            case 'png':
                imagepng($newImage, $dest);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($newImage, $dest);
                break;
        }
    }

    /**
     * Convert PHP image type constant to file extension string.
     *
     * @return string|false
     */
    public function image_type_to_extension(int $imagetype)
    {
        if (empty($imagetype)) {
            return false;
        }
        switch ($imagetype) {
            case IMAGETYPE_GIF:      return 'gif';
            case IMAGETYPE_JPEG:     return 'jpg';
            case IMAGETYPE_PNG:      return 'png';
            case IMAGETYPE_SWF:      return 'swf';
            case IMAGETYPE_PSD:      return 'psd';
            case IMAGETYPE_BMP:      return 'bmp';
            case IMAGETYPE_TIFF_II:  return 'tiff';
            case IMAGETYPE_TIFF_MM:  return 'tiff';
            case IMAGETYPE_JPC:      return 'jpc';
            case IMAGETYPE_JP2:      return 'jp2';
            case IMAGETYPE_JPX:      return 'jpf';
            case IMAGETYPE_JB2:      return 'jb2';
            case IMAGETYPE_SWC:      return 'swc';
            case IMAGETYPE_IFF:      return 'aiff';
            case IMAGETYPE_WBMP:     return 'wbmp';
            case IMAGETYPE_XBM:      return 'xbm';
            default:                 return false;
        }
    }
}
