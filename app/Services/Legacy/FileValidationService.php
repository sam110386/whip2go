<?php

namespace App\Services\Legacy;

use Illuminate\Http\UploadedFile;

/**
 * Migrated from: app/Plugin/Uploader/Model/Behavior/FileValidationBehavior.php
 *
 * Service-based replacement for the CakePHP FileValidationBehavior.
 * Validates uploaded files for size, dimensions, extension, and MIME type.
 *
 * Usage:
 *
 *   $validator = new FileValidationService();
 *   $errors = $validator->validate($request->file('photo'), [
 *       'extension' => ['jpg', 'png', 'gif'],
 *       'filesize'  => 5 * 1024 * 1024,
 *       'maxWidth'  => 2000,
 *       'maxHeight' => 2000,
 *       'required'  => true,
 *   ]);
 */
class FileValidationService
{
    protected array $messages = [
        'width'     => 'Your image width is invalid; required width is %s',
        'height'    => 'Your image height is invalid; required height is %s',
        'minWidth'  => 'Your image width is too small; minimum width %s',
        'minHeight' => 'Your image height is too small; minimum height %s',
        'maxWidth'  => 'Your image width is too large; maximum width %s',
        'maxHeight' => 'Your image height is too large; maximum height %s',
        'filesize'  => 'Your file size is too large; maximum size %s',
        'extension' => 'Your file extension is not allowed; allowed extensions: %s',
        'type'      => 'Your file type is not allowed; allowed types: %s',
        'mimeType'  => 'Your file type is not allowed; allowed types: %s',
        'required'  => 'This file is required',
    ];

    /**
     * Validate a file against the given rules.
     *
     * @param  UploadedFile|string|null $file
     * @param  array                    $rules  e.g. ['required' => true, 'filesize' => 5242880, 'extension' => ['jpg','png']]
     * @return array  List of error messages; empty if valid.
     */
    public function validate($file, array $rules = []): array
    {
        $errors = [];

        $isEmpty = $this->isEmpty($file);

        if (!empty($rules['required']) && $isEmpty) {
            $errors[] = $this->messages['required'];
            return $errors;
        }

        if ($isEmpty) {
            return $errors;
        }

        $filePath = ($file instanceof UploadedFile) ? $file->getRealPath() : $file;
        $fileSize = ($file instanceof UploadedFile) ? $file->getSize() : (is_file($filePath) ? filesize($filePath) : 0);
        $ext = ($file instanceof UploadedFile) ? strtolower($file->getClientOriginalExtension()) : strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = ($file instanceof UploadedFile) ? $file->getMimeType() : (is_file($filePath) ? mime_content_type($filePath) : '');

        $dimensions = null;
        if (is_file($filePath)) {
            $info = @getimagesize($filePath);
            if ($info) {
                $dimensions = ['width' => $info[0], 'height' => $info[1]];
            }
        }

        foreach ($rules as $rule => $value) {
            switch ($rule) {
                case 'filesize':
                    if ($fileSize > $value) {
                        $errors[] = sprintf($this->messages['filesize'], $this->formatBytes($value));
                    }
                    break;

                case 'extension':
                    $allowed = (array) $value;
                    if (!in_array($ext, $allowed)) {
                        $errors[] = sprintf($this->messages['extension'], implode(', ', $allowed));
                    }
                    break;

                case 'mimeType':
                    $allowed = (array) $value;
                    if (!in_array($mime, $allowed)) {
                        $errors[] = sprintf($this->messages['mimeType'], implode(', ', $allowed));
                    }
                    break;

                case 'type':
                    $allowed = (array) $value;
                    $fileType = explode('/', $mime)[0] ?? '';
                    if (!in_array($fileType, $allowed)) {
                        $errors[] = sprintf($this->messages['type'], implode(', ', $allowed));
                    }
                    break;

                case 'width':
                    if ($dimensions && $dimensions['width'] !== (int) $value) {
                        $errors[] = sprintf($this->messages['width'], $value);
                    }
                    break;

                case 'height':
                    if ($dimensions && $dimensions['height'] !== (int) $value) {
                        $errors[] = sprintf($this->messages['height'], $value);
                    }
                    break;

                case 'minWidth':
                    if ($dimensions && $dimensions['width'] < (int) $value) {
                        $errors[] = sprintf($this->messages['minWidth'], $value);
                    }
                    break;

                case 'minHeight':
                    if ($dimensions && $dimensions['height'] < (int) $value) {
                        $errors[] = sprintf($this->messages['minHeight'], $value);
                    }
                    break;

                case 'maxWidth':
                    if ($dimensions && $dimensions['width'] > (int) $value) {
                        $errors[] = sprintf($this->messages['maxWidth'], $value);
                    }
                    break;

                case 'maxHeight':
                    if ($dimensions && $dimensions['height'] > (int) $value) {
                        $errors[] = sprintf($this->messages['maxHeight'], $value);
                    }
                    break;
            }
        }

        return $errors;
    }

    protected function isEmpty($value): bool
    {
        if ($value instanceof UploadedFile) {
            return !$value->isValid() || empty($value->getRealPath());
        }

        if (is_array($value)) {
            return empty($value['tmp_name']);
        }

        return empty($value);
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
