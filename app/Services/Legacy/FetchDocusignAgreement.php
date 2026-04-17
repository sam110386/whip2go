<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

class FetchDocusignAgreement
{
    private $config;
    private array $args;
    private int $signer_client_id = 1000;

    private function getEnvelopeApi(): \DocuSign\eSign\Api\EnvelopesApi
    {
        $this->config = new \DocuSign\eSign\Configuration();
        $this->config->setHost($this->args['base_path']);
        $this->config->addDefaultHeader('Authorization', 'Bearer ' . $this->args['ds_access_token']);
        $apiClient = new \DocuSign\eSign\Client\ApiClient($this->config);
        return new \DocuSign\eSign\Api\EnvelopesApi($apiClient);
    }

    private function getTemplateArgs(): array
    {
        $token = (new DocusignToken())->getToken();
        return [
            'account_id' => config('legacy.Docusign.accountid'),
            'base_path' => config('legacy.Docusign.url') . '/restapi',
            'ds_access_token' => $token['access_token'],
            'envelope_args' => [
                'signer_client_id' => $this->signer_client_id,
                'ds_return_url' => url('returncallback'),
            ],
        ];
    }

    public function listdocuments($orderid = null, $reservationId = null, $OrderDepositRuleId = null): array
    {
        if (empty($orderid) || empty($reservationId) || empty($OrderDepositRuleId)) {
            return ["status" => false, "message" => "Invalid request", 'result' => ["file" => ""]];
        }

        $orderRuleObj = null;
        if ($orderid) {
            $orderObj = DB::table('cs_orders')->where('id', $orderid)->first(['id', 'parent_id']);
            $lookupId = $orderObj->parent_id ?? $orderObj->id;
            $orderRuleObj = DB::table('order_deposit_rules')->where('cs_order_id', $lookupId)->first(['id', 'insurance_payer', 'vehicle_reservation_id']);
        }
        if ($reservationId) {
            $orderRuleObj = DB::table('order_deposit_rules')->where('vehicle_reservation_id', $reservationId)->first(['id', 'insurance_payer', 'vehicle_reservation_id']);
        }
        if ($OrderDepositRuleId) {
            $orderRuleObj = DB::table('order_deposit_rules')->where('id', $OrderDepositRuleId)->first(['id', 'insurance_payer', 'vehicle_reservation_id']);
        }

        $tempfile = 'DRIVEITAWAY_Lease_Agreement.pdf';
        $file = 'files/Agreement_Sign_Doc/OrderRuleId_' . $OrderDepositRuleId . '/' . $tempfile . '.pdf';
        $fileName = public_path($file);
        if (file_exists($fileName)) {
            return ["status" => true, "message" => "File found", 'result' => ["file" => config('app.url') . '/' . $file]];
        }

        $this->args = $this->getTemplateArgs();
        $envelope_api = $this->getEnvelopeApi();
        $insurance_payer = $orderRuleObj->insurance_payer ?? null;

        if (in_array($insurance_payer, [5, 6, 7])) {
            $InsuranceQuoteObj = DB::table('driver_financed_insurance_quotes')
                ->where('order_id', $orderRuleObj->vehicle_reservation_id)
                ->first(['id', 'docusign_envelope_id', 'docusign_status']);
            $docusign_envelope_id = ($InsuranceQuoteObj && $InsuranceQuoteObj->docusign_status == 1 && $InsuranceQuoteObj->docusign_envelope_id != '')
                ? $InsuranceQuoteObj->docusign_envelope_id
                : '';
        } else {
            $insuranceObj = DB::table('insurance_quotes')
                ->where('id', $orderRuleObj->vehicle_reservation_id)
                ->where('docusign_envelope_id', '!=', '')
                ->first();
            $docusign_envelope_id = $insuranceObj->docusign_envelope_id ?? "";
        }

        try {
            if (!empty($docusign_envelope_id)) {
                $envelopObj = $envelope_api->listDocuments($this->args['account_id'], $docusign_envelope_id);
            } else {
                return ["status" => false, "message" => "No agreement found", 'result' => ["file" => ""]];
            }
        } catch (\Exception $e) {
            return ["status" => false, "message" => $e->getMessage(), 'result' => ["file" => ""]];
        }

        foreach ($envelopObj['envelope_documents'] as $document) {
            if ($document['name'] === "DRIVEITAWAY Lease Agreement") {
                return $this->fetchDocument($docusign_envelope_id, $document, $OrderDepositRuleId);
            }
        }
        return ["status" => false, "message" => "No agreement found", 'result' => ["file" => ""]];
    }

    private function fetchDocument(string $docusign_envelope_id, array $document, $OrderDepositRuleId = 0): array
    {
        $document_id = $document['document_id'] ?? 1;
        $document_name = isset($document['document_name']) ? preg_replace('/\s+/', '_', $document['document_name']) : $document_id;
        $this->args = $this->getTemplateArgs();
        $envelope_api = $this->getEnvelopeApi();
        $file = '';
        try {
            if (!empty($docusign_envelope_id) && !empty($document_id)) {
                $envelopObj = $envelope_api->getDocument($this->args['account_id'], $document_id, $docusign_envelope_id);
                $file = 'files/Agreement_Sign_Doc/OrderRuleId_' . $OrderDepositRuleId . '/' . $document_name . '.pdf';
                $fileName = public_path($file);
                if (file_exists($fileName)) {
                    return ["status" => true, "message" => "File found", 'result' => ["file" => config('app.url') . '/' . $file]];
                }
                $dir = public_path('files/Agreement_Sign_Doc/OrderRuleId_' . $OrderDepositRuleId);
                if (!file_exists($dir)) {
                    @mkdir($dir, 0755, true);
                }
                $fp = fopen($fileName, 'w');
                fwrite($fp, file_get_contents($envelopObj->getPathname()));
                fclose($fp);
                return ["status" => true, "message" => "File found", 'result' => ["file" => config('app.url') . '/' . $file]];
            }
        } catch (\Exception $e) {
            return ["status" => false, "message" => $e->getMessage(), 'result' => ["file" => ""]];
        }
        return ["status" => false, "message" => "Sorry something went wrong", 'result' => ["file" => ""]];
    }
}
