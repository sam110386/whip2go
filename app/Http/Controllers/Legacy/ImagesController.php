<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ImagesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private string $MEMORY_TO_ALLOCATE = '100M';
    private int $DEFAULT_QUALITY = 90;
    private string $CACHE_DIR = '';
    private string $DOCUMENT_ROOT = '';

    public function index(Request $request): void
    {
        $this->CACHE_DIR = public_path('img/imagecache/');
        $this->DOCUMENT_ROOT = public_path('/');

        if (!$request->has('image')) {
            header('HTTP/1.1 400 Bad Request');
            echo 'Error: no image was specified';
            exit();
        }

        $image = preg_replace('/^(s?f|ht)tps?:\/\/[^\/]+/i', '', (string) $request->query('image'));

        if ($image[0] !== '/' || strpos(dirname($image), ':') !== false || preg_match('/(\.\.|<|>)/', $image)) {
            header('HTTP/1.1 400 Bad Request');
            echo 'Error: malformed image path. Image paths must begin with \'/\'';
            exit();
        }

        if (!$image) {
            header('HTTP/1.1 400 Bad Request');
            echo 'Error: no image was specified';
            exit();
        }

        $docRoot = preg_replace('/\/$/', '', $this->DOCUMENT_ROOT);

        if (!file_exists($docRoot . $image)) {
            header('HTTP/1.1 404 Not Found');
            echo 'Error: image does not exist: ' . $docRoot . $image;
            exit();
        }

        $size = GetImageSize($docRoot . $image);
        $mime = $size['mime'];

        if (substr($mime, 0, 6) !== 'image/') {
            header('HTTP/1.1 400 Bad Request');
            echo 'Error: requested file is not an accepted type: ' . $docRoot . $image;
            exit();
        }

        $width = $size[0];
        $height = $size[1];

        $maxWidth = $request->has('width') ? (int) $request->query('width') : 0;
        $maxHeight = $request->has('height') ? (int) $request->query('height') : 0;

        $color = $request->has('color')
            ? preg_replace('/[^0-9a-fA-F]/', '', (string) $request->query('color'))
            : false;

        if (!$maxWidth && $maxHeight) {
            $maxWidth = 99999999999999;
        } elseif ($maxWidth && !$maxHeight) {
            $maxHeight = 99999999999999;
        } elseif ($color && !$maxWidth && !$maxHeight) {
            $maxWidth = $width;
            $maxHeight = $height;
        }

        if ((!$maxWidth && !$maxHeight) || (!$color && $maxWidth >= $width && $maxHeight >= $height)) {
            $data = file_get_contents($docRoot . '/' . $image);
            $lastModifiedString = gmdate('D, d M Y H:i:s', filemtime($docRoot . '/' . $image)) . ' GMT';
            $etag = md5($data);
            $this->doConditionalGet($etag, $lastModifiedString);
            header("Content-type: $mime");
            header('Content-Length: ' . strlen($data));
            echo $data;
            exit();
        }

        $offsetX = 0;
        $offsetY = 0;

        if ($request->has('cropratio')) {
            $cropRatio = explode(':', (string) $request->query('cropratio'));
            if (count($cropRatio) == 2) {
                $ratioComputed = $width / $height;
                $cropRatioComputed = (float) $cropRatio[0] / (float) $cropRatio[1];
                if ($ratioComputed < $cropRatioComputed) {
                    $origHeight = $height;
                    $height = $width / $cropRatioComputed;
                    $offsetY = ($origHeight - $height) / 2;
                } elseif ($ratioComputed > $cropRatioComputed) {
                    $origWidth = $width;
                    $width = $height * $cropRatioComputed;
                    $offsetX = ($origWidth - $width) / 2;
                }
            }
        }

        $xRatio = $maxWidth / $width;
        $yRatio = $maxHeight / $height;

        if ($xRatio * $height < $maxHeight) {
            $tnHeight = ceil($xRatio * $height);
            $tnWidth = $maxWidth;
        } else {
            $tnWidth = ceil($yRatio * $width);
            $tnHeight = $maxHeight;
        }

        $quality = $request->has('quality') ? (int) $request->query('quality') : $this->DEFAULT_QUALITY;

        $resizedImageSource = $tnWidth . 'x' . $tnHeight . 'x' . $quality;
        if ($color) {
            $resizedImageSource .= 'x' . $color;
        }
        if ($request->has('cropratio')) {
            $resizedImageSource .= 'x' . (string) $request->query('cropratio');
        }
        $resizedImageSource .= '-' . $image;
        $resizedImage = md5($resizedImageSource);
        $resized = $this->CACHE_DIR . $resizedImage;

        if (!$request->has('nocache') && file_exists($resized)) {
            $imageModified = filemtime($docRoot . $image);
            $thumbModified = filemtime($resized);
            if ($imageModified < $thumbModified) {
                $data = file_get_contents($resized);
                $lastModifiedString = gmdate('D, d M Y H:i:s', $thumbModified) . ' GMT';
                $etag = md5($data);
                $this->doConditionalGet($etag, $lastModifiedString);
                header("Content-type: $mime");
                header('Content-Length: ' . strlen($data));
                echo $data;
                exit();
            }
        }

        ini_set('memory_limit', $this->MEMORY_TO_ALLOCATE);

        $dst = imagecreatetruecolor($tnWidth, $tnHeight);

        switch ($size['mime']) {
            case 'image/gif':
                $creationFunction = 'ImageCreateFromGif';
                $outputFunction = 'ImagePng';
                $mime = 'image/png';
                $doSharpen = false;
                $quality = round(10 - ($quality / 10));
                break;
            case 'image/x-png':
            case 'image/png':
                $creationFunction = 'ImageCreateFromPng';
                $outputFunction = 'ImagePng';
                $doSharpen = false;
                $quality = round(10 - ($quality / 10));
                break;
            default:
                $creationFunction = 'ImageCreateFromJpeg';
                $outputFunction = 'ImageJpeg';
                $doSharpen = true;
                break;
        }

        $src = $creationFunction($docRoot . $image);

        if (in_array($size['mime'], ['image/gif', 'image/png'])) {
            if (!$color) {
                imagealphablending($dst, false);
                imagesavealpha($dst, true);
            } else {
                if ($color[0] === '#') {
                    $color = substr($color, 1);
                }
                $background = false;
                if (strlen($color) == 6) {
                    $background = imagecolorallocate($dst, hexdec($color[0] . $color[1]), hexdec($color[2] . $color[3]), hexdec($color[4] . $color[5]));
                } elseif (strlen($color) == 3) {
                    $background = imagecolorallocate($dst, hexdec($color[0] . $color[0]), hexdec($color[1] . $color[1]), hexdec($color[2] . $color[2]));
                }
                if ($background) {
                    imagefill($dst, 0, 0, $background);
                }
            }
        }

        ImageCopyResampled($dst, $src, 0, 0, $offsetX, $offsetY, $tnWidth, $tnHeight, $width, $height);

        if ($doSharpen) {
            $sharpness = $this->findSharp($width, $tnWidth);
            $sharpenMatrix = [
                [-1, -2, -1],
                [-2, $sharpness + 12, -2],
                [-1, -2, -1],
            ];
            $divisor = $sharpness;
            $offset = 0;
            imageconvolution($dst, $sharpenMatrix, $divisor, $offset);
        }

        if (!file_exists($this->CACHE_DIR)) {
            mkdir($this->CACHE_DIR, 0755);
        }

        if (!is_readable($this->CACHE_DIR)) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Error: the cache directory is not readable';
            exit();
        } elseif (!is_writable($this->CACHE_DIR)) {
            header('HTTP/1.1 500 Internal Server Error');
            echo 'Error: the cache directory is not writable';
            exit();
        }

        $outputFunction($dst, $resized, $quality);

        ob_start();
        $outputFunction($dst, null, $quality);
        $data = ob_get_contents();
        ob_end_clean();

        ImageDestroy($src);
        ImageDestroy($dst);

        $lastModifiedString = gmdate('D, d M Y H:i:s', filemtime($resized)) . ' GMT';
        $etag = md5($data);
        $this->doConditionalGet($etag, $lastModifiedString);

        header("Content-type: $mime");
        header('Content-Length: ' . strlen($data));
        echo $data;
        exit();
    }

    public function crop(Request $request)
    {
        $file = $request->input('vehicleimage');
        $name = $request->input('image');

        $filename = public_path('img/custom/vehicle_photo/' . $name);
        file_put_contents($filename, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file)));

        return response()->json(['status' => true, 'message' => '', 'url' => $filename]);
    }

    private function findSharp(int $orig, int $final): int
    {
        $final = $final * (750.0 / $orig);
        $a = 52;
        $b = -0.27810650887573124;
        $c = .00047337278106508946;
        $result = $a + $b * $final + $c * $final * $final;
        return max(round($result), 0);
    }

    private function doConditionalGet(string $etag, string $lastModified): void
    {
        header("Last-Modified: $lastModified");
        header("ETag: \"{$etag}\"");

        $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH'])
            ? stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])
            : false;
        $if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
            ? stripslashes($_SERVER['HTTP_IF_MODIFIED_SINCE'])
            : false;

        if (!$if_modified_since && !$if_none_match) {
            return;
        }
        if ($if_none_match && $if_none_match != $etag && $if_none_match != '"' . $etag . '"') {
            return;
        }
        if ($if_modified_since && $if_modified_since != $lastModified) {
            return;
        }

        header('HTTP/1.1 304 Not Modified');
        exit();
    }
}
