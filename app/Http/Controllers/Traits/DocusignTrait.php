<?php

namespace App\Http\Controllers\Traits;

use App\Services\Legacy\DocusignToken;
use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Configuration;
use DocuSign\eSign\Client\ApiClient;

trait DocusignTrait
{
    private $config;
    private array $args = [];

    private function getEnvelopeApi(): EnvelopesApi
    {
        $this->config = new Configuration();
        $this->config->setHost($this->args['base_path']);
        $this->config->addDefaultHeader('Authorization', 'Bearer ' . $this->args['ds_access_token']);
        $apiClient = new ApiClient($this->config);
        return new EnvelopesApi($apiClient);
    }
    private function getTemplateArgs(string $returnUrl = ''): array
    {
        $token = (new DocusignToken())->getToken();

        return [
            'account_id' => config('legacy.Docusign.accountid'),
            'base_path' => config('legacy.Docusign.url') . '/restapi',
            'ds_access_token' => $token['access_token'] ?? null,
            'envelope_args' => [
                'signer_client_id' => $this->signer_client_id ?? 1000,
                'ds_return_url' => $returnUrl ?: url('/docusign/returncallback'),
            ],
        ];
    }
}
