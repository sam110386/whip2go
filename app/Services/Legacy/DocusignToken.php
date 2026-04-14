<?php

namespace App\Services\Legacy;

class DocusignToken
{
    private string $tempFile;
    private $config;

    public function __construct()
    {
        $this->tempFile = storage_path('app/Docusign.txt');
    }

    public function getToken(): array
    {
        $fhandle = fopen($this->tempFile, "r+");
        $contents = fread($fhandle, filesize($this->tempFile));
        $data = json_decode($contents, true);
        if (!isset($data['expire_at']) || time() > $data['expire_at']) {
            $this->config = new \DocuSign\eSign\Configuration(["host" => config('legacy.Docusign.url')]);
            $obj = new \DocuSign\eSign\Client\ApiClient($this->config);
            $result = $obj->refreshAccessToken(
                config('legacy.Docusign.integration_key'),
                config('legacy.Docusign.secret_key'),
                $data['refresh_token']
            );
            $result = json_decode(json_encode($result['result']), true);
            $fhandle = fopen($this->tempFile, "w");
            $result['expire_at'] = (time() + $result['expires_in'] - 3600);
            fwrite($fhandle, json_encode($result));
            $data = $result;
        }
        fclose($fhandle);
        return $data;
    }
}
