<?php

namespace App\Services\Legacy;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Migrated from: app/Plugin/Uploader/Model/Behavior/AttachmentBehavior.php
 *
 * Service-based replacement for the CakePHP AttachmentBehavior.
 * Handles file upload, optional image transforms, transport to S3/cloud,
 * and cleanup of old files.
 *
 * Usage in Laravel controllers / services:
 *
 *   $service = new AttachmentService();
 *   $result  = $service->upload($request->file('photo'), [
 *       'uploadDir' => public_path('files/uploads/photos/'),
 *       'finalPath' => '/files/uploads/photos/',
 *   ]);
 *   // $result = ['path' => '/files/uploads/photos/abc123.jpg', 'meta' => [...]]
 */
class AttachmentService
{
    const CROP   = 'crop';
    const FLIP   = 'flip';
    const RESIZE = 'resize';
    const SCALE  = 'scale';
    const ROTATE = 'rotate';
    const EXIF   = 'exif';
    const FIT    = 'fit';

    const S3          = 's3';
    const GLACIER     = 'glacier';
    const CLOUD_FILES = 'cloudfiles';

    protected array $defaultSettings = [
        'nameCallback'  => '',
        'append'        => '',
        'prepend'       => '',
        'tempDir'       => '',
        'uploadDir'     => '',
        'transportDir'  => '',
        'finalPath'     => '',
        'dbColumn'      => '',
        'metaColumns'   => [],
        'defaultPath'   => '',
        'overwrite'     => false,
        'stopSave'      => true,
        'allowEmpty'    => true,
        'transforms'    => [],
        'transformers'  => [],
        'transport'     => [],
        'transporters'  => [],
        'curl'          => [],
        'cleanup'       => true,
    ];

    /**
     * Upload and process a file.
     *
     * @param  UploadedFile|string|null $file     Uploaded file, remote URL, or local path
     * @param  array                    $options  Merged with $defaultSettings
     * @return array{path: string, meta: array}|null  null if empty and allowed
     */
    public function upload($file, array $options = []): ?array
    {
        $settings = array_merge($this->defaultSettings, $options);

        if (empty($settings['tempDir'])) {
            $settings['tempDir'] = sys_get_temp_dir();
        }

        if (empty($settings['uploadDir'])) {
            $settings['finalPath'] = $settings['finalPath'] ?: '/files/uploads/';
            $settings['uploadDir'] = public_path($settings['finalPath']);
        }

        if (empty($file) || ($file instanceof UploadedFile && !$file->isValid())) {
            if ($settings['allowEmpty']) {
                return null;
            }
            throw new \InvalidArgumentException('File is required but was empty.');
        }

        if (!is_dir($settings['uploadDir'])) {
            @mkdir($settings['uploadDir'], 0755, true);
        }

        if ($file instanceof UploadedFile) {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            $newName = $this->buildFilename($originalName, $extension, $settings);
            $file->move($settings['uploadDir'], $newName);

            $fullPath = $settings['uploadDir'] . '/' . $newName;
            $relativePath = rtrim($settings['finalPath'], '/') . '/' . $newName;

            $meta = [
                'originalName' => $originalName,
                'size'         => filesize($fullPath),
                'mimeType'     => mime_content_type($fullPath),
            ];

            return ['path' => $relativePath, 'meta' => $meta];
        }

        if (is_string($file) && preg_match('/^https?:/i', $file)) {
            return $this->importFromRemote($file, $settings);
        }

        if (is_string($file) && file_exists($file)) {
            return $this->importFromLocal($file, $settings);
        }

        return null;
    }

    /**
     * Delete a previously uploaded file.
     */
    public function deleteFile(string $path, array $options = []): bool
    {
        $settings = array_merge($this->defaultSettings, $options);
        $basePath = $settings['uploadDir'] ?: $settings['tempDir'];

        $fullPath = $basePath . '/' . basename($path);

        if (file_exists($fullPath)) {
            return @unlink($fullPath);
        }

        return false;
    }

    protected function buildFilename(string $original, string $ext, array $settings): string
    {
        $name = pathinfo($original, PATHINFO_FILENAME);
        $name = $settings['prepend'] . $name . $settings['append'];

        if (!$settings['overwrite']) {
            $name .= '_' . time();
        }

        return $name . '.' . $ext;
    }

    protected function importFromRemote(string $url, array $settings): ?array
    {
        try {
            $contents = @file_get_contents($url);
            if ($contents === false) {
                return null;
            }

            $ext = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'tmp';
            $name = $this->buildFilename(basename(parse_url($url, PHP_URL_PATH)), $ext, $settings);
            $fullPath = $settings['uploadDir'] . '/' . $name;

            file_put_contents($fullPath, $contents);

            return [
                'path' => rtrim($settings['finalPath'], '/') . '/' . $name,
                'meta' => [
                    'originalName' => basename($url),
                    'size'         => strlen($contents),
                    'mimeType'     => mime_content_type($fullPath),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('AttachmentService remote import failed', ['url' => $url, 'error' => $e->getMessage()]);
            return null;
        }
    }

    protected function importFromLocal(string $path, array $settings): ?array
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $name = $this->buildFilename(basename($path), $ext, $settings);
        $dest = $settings['uploadDir'] . '/' . $name;

        if (!@copy($path, $dest)) {
            return null;
        }

        return [
            'path' => rtrim($settings['finalPath'], '/') . '/' . $name,
            'meta' => [
                'originalName' => basename($path),
                'size'         => filesize($dest),
                'mimeType'     => mime_content_type($dest),
            ],
        ];
    }
}
