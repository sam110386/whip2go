<?php

namespace App\Services\Legacy;

/**
 * Ported from CakePHP app/Controller/Component/FileComponent.php
 *
 * File upload utility: move, delete, validate, and sanitize uploaded files.
 */
class FileHandler
{
    public string $fileName = '';
    public string $destPath = '';
    public bool $useHash = false;

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fname): void
    {
        $this->fileName = $fname;
    }

    public function setDestPath(string $path): void
    {
        $this->destPath = $path;
    }

    /**
     * Move an uploaded file to the destination path.
     *
     * @return string|false The final filename on success, false on failure.
     */
    public function uploadFile(string $originName, string $tmp_name, bool $getRandomName = false)
    {
        if (!is_dir($this->destPath)) {
            return false;
        }

        $extDot = explode('.', $this->fileName);
        $ext = $extDot[count($extDot) - 1];

        $this->fileName = $getRandomName ? $this->getRandomFileName() : $this->fileName;

        if (strpos($this->fileName, '.') === false) {
            $this->fileName .= '.' . $ext;
        }

        $baseName = substr($this->fileName, 0, strlen($this->fileName) - (strlen($ext) + 1));
        $this->fileName = $this->clean_string($baseName) . '.' . $ext;

        $dest = rtrim($this->destPath, '/') . '/' . $this->fileName;

        if (move_uploaded_file($tmp_name, $dest)) {
            chmod($dest, 0777);
            return $this->fileName;
        }

        return false;
    }

    public function deleteFile(?string $oldFileName = null): void
    {
        if ($oldFileName === null) {
            return;
        }
        $destFile = rtrim($this->destPath, '/') . '/' . $oldFileName;
        if (file_exists($destFile)) {
            @unlink($destFile);
        }
    }

    public function deleteFile_bulkupload(?string $oldFileName = null): void
    {
        if ($oldFileName !== null && file_exists($oldFileName)) {
            @unlink($oldFileName);
        }
    }

    public function getRandomFileName(): string
    {
        if ($this->useHash) {
            $fileName = md5(bin2hex(random_bytes(5)));
        } else {
            $fileName = (string) random_int(1000, 1000000);
        }

        if ($this->is_exists($fileName)) {
            return $this->getRandomFileName();
        }

        return $fileName;
    }

    public function is_exists(string $filename): bool
    {
        return file_exists(rtrim($this->destPath, '/') . '/' . $filename);
    }

    public function createHashValue(string $fileName): string
    {
        return md5(file_get_contents($fileName) . filesize($fileName));
    }

    public function getFileExt(string $fileName): string
    {
        return substr($fileName, strpos($fileName, '/') + 1);
    }

    /**
     * Sanitize a filename: keep only word chars, collapse separators, lowercase.
     */
    public function clean_string(string $text): string
    {
        $text = preg_replace('/[^\w]/', '_', $text);
        $text = preg_replace('/[\_\-]{1,}/', '_', $text);
        $text = preg_replace('/^\_?(.*?)\_?$/', '\1', $text);

        return strtolower($text);
    }

    public function validateCsvFile(string $file_name): bool
    {
        $file_name = strtolower(trim($file_name));
        $parts = explode('.', $file_name);
        $file_type = end($parts);

        return in_array($file_type, ['csv']);
    }

    public function validateImage(string $file_type): bool
    {
        return in_array(
            strtolower(trim($file_type)),
            ['png', 'jpg', 'jpeg', 'gif', 'pjpeg']
        );
    }

    public function validateGifJpgImage(string $file_type): bool
    {
        return in_array(
            strtolower(trim($file_type)),
            ['jpg', 'jpeg', 'gif', 'pjpeg', 'pjpg']
        );
    }
}
