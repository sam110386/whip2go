<?php

namespace App\Services\Legacy;

/**
 * Ported from CakePHP app/Controller/Component/UploadComponent.php
 * File upload with optional GD image processing (resize, crop).
 */
class UploadHandler
{
    public $_file;
    public $_filepath;
    public $_destination;
    public $_name;
    public $_short;
    public $_rules;
    public $_allowed;

    public $errors;
    public $result;
    public $error;

    /**
     * Handle uploads of any type.
     *
     * @param array       $file        $_FILES element
     * @param string      $destination Upload directory
     * @param string|null $name        Override saved filename
     * @param array|null  $rules       Image processing rules [type, size, output, quality]
     * @param array|null  $allowed     Allowed extensions
     * @return string|false  Basename on success, false on failure
     */
    public function upload(array $file, string $destination, ?string $name = null, ?array $rules = null, ?array $allowed = null)
    {
        $this->result = false;
        $this->error = false;

        $this->_file = $file;
        $this->_destination = $destination;
        if (!is_null($rules)) {
            $this->_rules = $rules;
        }

        if (!is_null($allowed)) {
            $this->_allowed = $allowed;
        } else {
            $this->_allowed = ['jpg', 'jpeg', 'gif', 'png', 'csv', 'doc', 'docx', 'txt', 'pdf', 'org', 'mp4'];
        }

        if (substr($this->_destination, -1) !== '/') {
            $this->_destination .= '/';
        }

        if (isset($file) && is_array($file) && !$this->upload_error($file['error'])) {

            $fileName = ($name === null)
                ? $this->uniquename($destination . $file['name'])
                : $destination . $name;
            $inputFileName = $destination . $file['name'];
            $fileTmp = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileType = $file['type'];
            $fileError = $file['error'];

            $this->_name = $fileName;

            if (!in_array($this->ext($inputFileName), $this->_allowed)) {
                $this->addError('File type not allowed.');
            } else {
                if (is_uploaded_file($fileTmp)) {
                    if ($rules === null) {
                        $output = $fileName;
                        if (copy($fileTmp, $output)) {
                            chmod($output, 0755);
                            $this->result = basename($this->_name);
                        } else {
                            $this->addError("Could not move '$fileName' to '$destination'");
                        }
                    } else {
                        if (function_exists('imagecreatefromjpeg')) {
                            if (!isset($rules['output'])) {
                                $rules['output'] = null;
                            }
                            if (!isset($rules['quality'])) {
                                $rules['quality'] = null;
                            }
                            if (isset($rules['type']) && isset($rules['size'])) {
                                $this->image($this->_file, $rules['type'], $rules['size'], $rules['output'], $rules['quality']);
                            } else {
                                $this->addError('Invalid "rules" parameter');
                            }
                        } else {
                            $this->addError('GD library is not installed');
                        }
                    }
                } else {
                    $this->addError("Possible file upload attack on '$fileName'");
                }
            }
        } else {
            $this->addError('Possible file upload attack');
        }

        return $this->result;
    }

    /**
     * Return the extension of a file.
     */
    public function ext(string $file): string
    {
        return trim(substr($file, strrpos($file, '.') + 1));
    }

    /**
     * Add an error message to the stack.
     */
    public function addError(string $message): void
    {
        if (!is_array($this->errors)) {
            $this->errors = [];
        }
        $this->errors[] = $message;
    }

    /**
     * GD image processing: resize, resizemin, resizecrop, crop.
     *
     * @param array       $file
     * @param string|null $type
     * @param mixed       $size    Array [w, h] or single int
     * @param string|null $output  'jpg', 'png', 'gif'
     * @param int|null    $quality 1-100
     */
    public function image(array $file, ?string $type = null, $size = null, ?string $output = null, ?int $quality = null): void
    {
        $type = strtolower($type ?? 'resize');
        $output = strtolower($output ?? 'jpg');

        if (is_null($size)) {
            $size = 100;
        }
        if (is_null($quality)) {
            $quality = 75;
        }

        if (is_array($size)) {
            $maxW = intval($size[0]);
            $maxH = intval($size[1]);
        } else {
            $maxScale = intval($size);
        }

        if (isset($maxScale)) {
            if (!$maxScale) {
                $this->addError('Max scale must be set');
            }
        } else {
            if (!$maxW || !$maxH) {
                $this->addError('Size width and height must be set');
                return;
            }
            if ($type === 'resize') {
                $this->addError('Provide only one number for size');
            }
        }

        if (!in_array($output, ['jpg', 'png', 'gif'])) {
            $this->addError('Cannot output file as ' . strtoupper($output));
        }

        if (is_numeric($quality)) {
            $quality = intval($quality);
            if ($quality > 100 || $quality < 1) {
                $quality = 75;
            }
        } else {
            $quality = 75;
        }

        $uploadSize = getimagesize($file['tmp_name']);
        $uploadWidth = $uploadSize[0];
        $uploadHeight = $uploadSize[1];
        $uploadType = $uploadSize[2];

        if ($uploadType != 1 && $uploadType != 2 && $uploadType != 3) {
            $this->addError('File type must be GIF, PNG, or JPG to resize');
        }

        switch ($uploadType) {
            case 1:  $srcImg = imagecreatefromgif($file['tmp_name']); break;
            case 2:  $srcImg = imagecreatefromjpeg($file['tmp_name']); break;
            case 3:  $srcImg = imagecreatefrompng($file['tmp_name']); break;
            default: $this->addError('File type must be GIF, PNG, or JPG to resize'); return;
        }

        $dstImg = null;

        switch ($type) {
            case 'resize':
                if ($uploadWidth > $maxScale || $uploadHeight > $maxScale) {
                    if ($uploadWidth > $uploadHeight) {
                        $newX = $maxScale;
                        $newY = ($uploadHeight * $newX) / $uploadWidth;
                    } elseif ($uploadWidth < $uploadHeight) {
                        $newY = $maxScale;
                        $newX = ($newY * $uploadWidth) / $uploadHeight;
                    } else {
                        $newX = $newY = $maxScale;
                    }
                } else {
                    $newX = $uploadWidth;
                    $newY = $uploadHeight;
                }
                $dstImg = imagecreatetruecolor((int)$newX, (int)$newY);
                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, (int)$newX, (int)$newY, $uploadWidth, $uploadHeight);
                break;

            case 'resizemin':
                $ratioX = $maxW / $uploadWidth;
                $ratioY = $maxH / $uploadHeight;
                if (($uploadWidth == $maxW) && ($uploadHeight == $maxH)) {
                    $newX = $uploadWidth;
                    $newY = $uploadHeight;
                } elseif (($ratioX * $uploadHeight) > $maxH) {
                    $newX = $maxW;
                    $newY = ceil($ratioX * $uploadHeight);
                } else {
                    $newX = ceil($ratioY * $uploadWidth);
                    $newY = $maxH;
                }
                $dstImg = imagecreatetruecolor((int)$newX, (int)$newY);
                imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, (int)$newX, (int)$newY, $uploadWidth, $uploadHeight);
                break;

            case 'resizecrop':
                $ratioX = $maxW / $uploadWidth;
                $ratioY = $maxH / $uploadHeight;
                if ($ratioX < $ratioY) {
                    $newX = round(($uploadWidth - ($maxW / $ratioY)) / 2);
                    $newY = 0;
                    $uploadWidth = round($maxW / $ratioY);
                } else {
                    $newX = 0;
                    $newY = round(($uploadHeight - ($maxH / $ratioX)) / 2);
                    $uploadHeight = round($maxH / $ratioX);
                }
                $dstImg = imagecreatetruecolor($maxW, $maxH);
                imagecopyresampled($dstImg, $srcImg, 0, 0, (int)$newX, (int)$newY, $maxW, $maxH, $uploadWidth, $uploadHeight);
                break;

            case 'crop':
                $startY = ($uploadHeight - $maxH) / 2;
                $startX = ($uploadWidth - $maxW) / 2;
                $dstImg = imagecreatetruecolor($maxW, $maxH);
                imagecopyresampled($dstImg, $srcImg, 0, 0, (int)$startX, (int)$startY, $maxW, $maxH, $maxW, $maxH);
                break;

            default:
                $this->addError("Resize function \"$type\" does not exist");
                return;
        }

        $write = false;
        switch ($output) {
            case 'jpg':
                $write = imagejpeg($dstImg, $this->_name, $quality);
                break;
            case 'png':
                $write = imagepng($dstImg, $this->_name . '.png', $quality);
                break;
            case 'gif':
                $write = imagegif($dstImg, $this->_name . '.gif', $quality);
                break;
        }

        imagedestroy($dstImg);

        if ($write) {
            $this->result = basename($this->_name);
        } else {
            $this->addError('Could not write ' . $this->_name . ' to ' . $this->_destination);
        }
    }

    public function newname(string $file): string
    {
        return time() . '.' . $this->ext($file);
    }

    public function uniquename(string $file): string
    {
        $parts = pathinfo($file);
        $dir = $parts['dirname'];
        $file = preg_replace('/[^[:alnum:]_.\-]/', '', $parts['basename']);
        $ext = $parts['extension'] ?? '';
        if ($ext) {
            $ext = '.' . $ext;
            $file = substr($file, 0, -strlen($ext));
        }
        $i = 0;
        while (file_exists($dir . '/' . $file . $i . $ext)) {
            $i++;
        }
        return $dir . '/' . $file . $i . $ext;
    }

    /**
     * @return string|false  Error message or false if no error
     */
    public function upload_error(int $errorobj)
    {
        $error = false;
        switch ($errorobj) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
                $error = 'The uploaded file exceeds the upload_max_filesize directive (' . ini_get('upload_max_filesize') . ') in php.ini.';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error = 'The uploaded file was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error = 'No file was uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error = 'Missing a temporary folder.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error = 'Failed to write file to disk';
                break;
            default:
                $error = 'Unknown File Error';
        }
        return $error;
    }
}
