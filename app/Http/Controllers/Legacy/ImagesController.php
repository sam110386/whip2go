<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;

class ImagesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private $MEMORY_TO_ALLOCATE = '100M';
    private $DEFAULT_QUALITY = 90;

    public function index(Request $request)
    {
        $cacheDir = public_path('img/imagecache/');
        $docRoot = public_path();

        if (!$request->has('image')) {
            return response('Error: no image was specified', 400);
        }

        $image = preg_replace('/^(s?f|ht)tps?:\/\/[^\/]+/i', '', (string)$request->query('image'));

        if ($image === '' || $image[0] != '/' || strpos(dirname($image), ':') !== false || preg_match('/(\.\.|<|>)/', $image)) {
            return response("Error: malformed image path. Image paths must begin with '/'", 400);
        }

        $docRoot = preg_replace('/\/$/', '', $docRoot);

        if (!file_exists($docRoot . $image)) {
            return response('Error: image does not exist: ' . $docRoot . $image, 404);
        }

        $size = getimagesize($docRoot . $image);
        if (!$size) {
            return response('Error: unable to retrieve image size.', 400);
        }
        $mime = $size['mime'];

        if (substr($mime, 0, 6) != 'image/') {
            return response('Error: requested file is not an accepted type: ' . $docRoot . $image, 400);
        }

        $width = $size[0];
        $height = $size[1];

        $maxWidth = (int)$request->query('width', 0);
        $maxHeight = (int)$request->query('height', 0);
        $color = $request->query('color') ? preg_replace('/[^0-9a-fA-F]/', '', (string)$request->query('color')) : false;

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

            if ($response = $this->doConditionalGet($request, $etag, $lastModifiedString)) {
                return $response;
            }

            return response($data)
                ->header('Content-type', $mime)
                ->header('Content-Length', strlen($data))
                ->header('Last-Modified', $lastModifiedString)
                ->header('ETag', "\"{$etag}\"");
        }

        $offsetX = 0;
        $offsetY = 0;

        if ($request->has('cropratio')) {
            $cropRatio = explode(':', (string)$request->query('cropratio'));
            if (count($cropRatio) == 2) {
                $ratioComputed = $width / $height;
                $cropRatioComputed = (float)$cropRatio[0] / (float)$cropRatio[1];

                if ($ratioComputed < $cropRatioComputed) {
                    $origHeight = $height;
                    $height = (int)($width / $cropRatioComputed);
                    $offsetY = (int)(($origHeight - $height) / 2);
                } else if ($ratioComputed > $cropRatioComputed) {
                    $origWidth = $width;
                    $width = (int)($height * $cropRatioComputed);
                    $offsetX = (int)(($origWidth - $width) / 2);
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

        $quality = (int)$request->query('quality', $this->DEFAULT_QUALITY);

        $resizedImageSource = $tnWidth . 'x' . $tnHeight . 'x' . $quality;
        if ($color) $resizedImageSource .= 'x' . $color;
        if ($request->has('cropratio')) $resizedImageSource .= 'x' . (string)$request->query('cropratio');
        $resizedImageSource .= '-' . $image;

        $resizedImage = md5($resizedImageSource);
        $resized = $cacheDir . $resizedImage;

        if (!$request->has('nocache') && file_exists($resized)) {
            $imageModified = filemtime($docRoot . $image);
            $thumbModified = filemtime($resized);

            if ($imageModified < $thumbModified) {
                $data = file_get_contents($resized);
                $lastModifiedString = gmdate('D, d M Y H:i:s', $thumbModified) . ' GMT';
                $etag = md5($data);

                if ($response = $this->doConditionalGet($request, $etag, $lastModifiedString)) {
                    return $response;
                }

                return response($data)
                    ->header('Content-type', $mime)
                    ->header('Content-Length', strlen($data))
                    ->header('Last-Modified', $lastModifiedString)
                    ->header('ETag', "\"{$etag}\"");
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
                if ($color[0] == '#') $color = substr($color, 1);
                $background = false;

                if (strlen($color) == 6) {
                    $background = imagecolorallocate($dst, hexdec($color[0] . $color[1]), hexdec($color[2] . $color[3]), hexdec($color[4] . $color[5]));
                } else if (strlen($color) == 3) {
                    $background = imagecolorallocate($dst, hexdec($color[0] . $color[0]), hexdec($color[1] . $color[1]), hexdec($color[2] . $color[2]));
                }
                if ($background) {
                    imagefill($dst, 0, 0, $background);
                }
            }
        }

        imagecopyresampled($dst, $src, 0, 0, $offsetX, $offsetY, $tnWidth, $tnHeight, $width, $height);

        if ($doSharpen) {
            $sharpness = $this->findSharp($width, $tnWidth);
            $sharpenMatrix = [
                [-1, -2, -1],
                [-2, $sharpness + 12, -2],
                [-1, -2, -1]
            ];
            $divisor = $sharpness;
            imageconvolution($dst, $sharpenMatrix, $divisor, 0);
        }

        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        if (!is_readable($cacheDir) || !is_writable($cacheDir)) {
            return response('Error: the cache directory is not readable/writable', 500);
        }

        $outputFunction($dst, $resized, $quality);

        ob_start();
        $outputFunction($dst, null, $quality);
        $data = ob_get_contents();
        ob_end_clean();

        imagedestroy($src);
        imagedestroy($dst);

        $lastModifiedString = gmdate('D, d M Y H:i:s', filemtime($resized)) . ' GMT';
        $etag = md5($data);

        if ($response = $this->doConditionalGet($request, $etag, $lastModifiedString)) {
            return $response;
        }

        return response($data)
            ->header('Content-type', $mime)
            ->header('Content-Length', strlen($data))
            ->header('Last-Modified', $lastModifiedString)
            ->header('ETag', "\"{$etag}\"");
    }

    public function crop(Request $request)
    {
        $file = $request->input('vehicleimage', '');
        $name = $request->input('image', '');

        $filename = public_path('img/custom/vehicle_photo/' . $name);
        
        $imgDir = dirname($filename);
        if (!file_exists($imgDir)) {
            mkdir($imgDir, 0755, true);
        }

        // Clean out data:image... prefix and write
        file_put_contents($filename, base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file)));

        return response()->json([
            'status' => true,
            'message' => '',
            'url' => $filename
        ]);
    }

    private function findSharp($orig, $final)
    {
        $final = $final * (750.0 / $orig);
        $a = 52;
        $b = -0.27810650887573124;
        $c = .00047337278106508946;
        $result = $a + $b * $final + $c * $final * $final;
        return max(round($result), 0);
    }

    private function doConditionalGet(Request $request, $etag, $lastModified)
    {
        $ifNoneMatch = stripslashes($request->header('If-None-Match', ''));
        $ifModifiedSince = stripslashes($request->header('If-Modified-Since', ''));

        if (!$ifModifiedSince && !$ifNoneMatch) {
            return null;
        }

        if ($ifNoneMatch && $ifNoneMatch != $etag && $ifNoneMatch != '"' . $etag . '"') {
            return null;
        }

        if ($ifModifiedSince && $ifModifiedSince != $lastModified) {
            return null;
        }

        // Return a response directly replacing `exit`
        return response('', 304);
    }
}
