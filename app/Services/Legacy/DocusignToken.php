<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Storage;
use DocuSign\eSign\Configuration;
use DocuSign\eSign\Client\ApiClient;

class DocusignToken
{
    private string $fileName = 'temp/Docusign.txt';
    private ?Configuration $config = null;

    public function getToken(): array
    {
        $contents = Storage::disk('local')->exists($this->fileName)
            ? Storage::disk('local')->get($this->fileName)
            : '{}';

        $data = json_decode($contents, true) ?? [];

        if (
            !isset($data['expire_at'])
            || time() > $data['expire_at']
        ) {
            $this->config = new Configuration(["host" => config('legacy.Docusign.url')]);
            $obj = new ApiClient($this->config);

            $result = $obj->refreshAccessToken(
                config('legacy.Docusign.integration_key'),
                config('legacy.Docusign.secret_key'),
                $data['refresh_token'] ?? null
            );

            $resultData = json_decode(json_encode($result['result']), true);
            $resultData['expire_at'] = (time() + $resultData['expires_in'] - 3600);
            Storage::disk('local')->put($this->fileName, json_encode($resultData));
            $data = $resultData;
        }

        return $data;
    }
}
