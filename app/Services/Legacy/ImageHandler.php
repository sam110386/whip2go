<?php

namespace App\Services\Legacy;

/**
 * Ported from CakePHP app/Controller/Component/ImagexComponent.php (ImageComponent class).
 * Image upload and thumbnail generation using GD/phpThumb.
 */
class ImageHandler
{
    private array $allowed_mime_types = [
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/gif',
    ];

    private array $allowed_extensions = [
        'jpg',
        'jpeg',
        'png',
        'gif',
    ];

    private array $save_paths = [
        'upload' => '',
        'thumb'  => '',
    ];

    private ?string $file_path = null;

    public int $width = 100;
    public int $height = 100;

    /** @var int|string */
    private $zoom_crop = 0;

    private array $errorMsg = [];

    /**
     * Set paths for upload and thumb directories.
     *
     * @return bool|void
     */
    public function set_paths(string $upload_path, string $thumb_path)
    {
        if (!empty($upload_path) && is_writable($upload_path)
            && !empty($thumb_path) && is_writable($thumb_path)) {
            $this->save_paths = [
                'upload' => $upload_path,
                'thumb'  => $thumb_path,
            ];
            return true;
        }
        return false;
    }

    /**
     * Set zoom crop for phpThumb.
     *
     * @param int|string $zoom_crop
     * @return bool
     */
    public function set_zoom_crop($zoom_crop): bool
    {
        if (empty($zoom_crop) || $zoom_crop === '') {
            return false;
        }

        $allowed_zoom_crop_param = ['T', 'B', 'L', 'R', 'TL', 'TR', 'BL', 'BR'];

        if ($zoom_crop === 1 || $zoom_crop === 'C') {
            $this->zoom_crop = 1;
        } elseif (extension_loaded('magickwand')
            && in_array($zoom_crop, $allowed_zoom_crop_param)) {
            $this->zoom_crop = $zoom_crop;
        } else {
            return false;
        }

        return true;
    }

    /**
     * Upload image from request data.
     *
     * TODO: This method originally relied on $this->controller->data (CakePHP request data).
     *       It needs to be refactored to accept an UploadedFile or array of file data
     *       and an entity ID directly as parameters instead.
     *
     * @param string $field  Dot-notation field e.g. "Model.field"
     * @param array  $data   Request data array (replaces $this->controller->data)
     * @param int    $entityId  Entity ID (replaces $this->controller->data['Featured_location']['id'])
     * @return string|false  Destination path or false
     */
    public function upload_image(string $field, array $data = [], int $entityId = 0)
    {
        if (empty($field)) {
            return false;
        }

        $exploded = explode('.', $field);
        if (count($exploded) !== 2) {
            return false;
        }

        [$model, $value] = $exploded;

        if (array_key_exists($model, $data)
            && array_key_exists($value, $data[$model])
            && is_array($data[$model][$value])) {

            $file = &$data[$model][$value];

            if (array_key_exists('error', $file) && $file['error'] === 0) {
                if (!in_array($file['type'], $this->allowed_mime_types)) {
                    return false;
                }

                $exploded = explode('.', $file['name']);
                $extension = end($exploded);

                if (in_array($extension, $this->allowed_extensions)) {
                    $uploaded_arr = explode('.', $file['name']);
                    $destination = $this->save_paths['upload'] . $uploaded_arr[0] . '_' . $entityId . '.' . $extension;

                    move_uploaded_file($file['tmp_name'], $destination);

                    if (file_exists($destination)) {
                        $this->file_path = $destination;
                        return $destination;
                    }
                }
                return false;
            }
            return false;
        }
        return false;
    }

    /**
     * Wrapper: generate thumbnail from most recently uploaded file.
     *
     * @return string|false
     */
    public function thumb_uploaded_file()
    {
        return $this->thumb($this->file_path);
    }

    /**
     * Generate a thumbnail from a source file.
     *
     * TODO: phpThumb vendor dependency — install via Composer (e.g. masterexploder/phpthumb)
     *       and replace the manual class instantiation below.
     *
     * @return string|false  Thumb destination path or false
     */
    public function thumb(?string $file)
    {
        if (empty($file) || !file_exists($file)) {
            return false;
        }

        // TODO: Replace with Composer-installed phpThumb.
        // Original: App::import('Vendor', 'phpThumb', ['file' => 'phpThumb/phpthumb.class.php']);
        // $phpThumb = new \phpThumb();
        if (!class_exists('phpThumb')) {
            $this->errorMsg[] = 'phpThumb class not available. Install via Composer.';
            return false;
        }

        /** @var \phpThumb $phpThumb */
        $phpThumb = new \phpThumb();

        $phpThumb->setSourceFilename($file);
        $phpThumb->setParameter('w', $this->width);
        $phpThumb->setParameter('h', $this->height);
        $phpThumb->setParameter('zc', $this->zoom_crop);

        $pathinfo = pathinfo($file);
        $destination = $this->save_paths['thumb'] . $pathinfo['filename'] . '_thumb.' . $pathinfo['extension'];

        if (file_exists($destination)) {
            unlink($destination);
        }

        if ($phpThumb->generateThumbnail()
            && $phpThumb->RenderToFile($destination)) {
            return $destination;
        }

        return false;
    }
}
