<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\DocusignToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocusignController extends LegacyAppController
{
    private $config;
    private array $args;

    public function listdocuments(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $order = $request->input('quoteid');
        $myModal = $request->input('model', 'myModal');
        $OrderDepositRuleId = $request->input('OrderDepositRuleId');

        $this->args = $this->getTemplateArgs();
        $args = $this->args;
        $envelope_api = $this->getEnvelopeApi();

        $orderRuleObj = DB::table('order_deposit_rules')
            ->where('id', $OrderDepositRuleId)
            ->first(['id', 'insurance_payer', 'vehicle_reservation_id']);

        $insurance_payer = $orderRuleObj->insurance_payer ?? null;
        $docusign_envelope_id = '';

        if (in_array($insurance_payer, [5, 6, 7])) {
            $InsuranceQuoteObj = DB::table('driver_financed_insurance_quotes')
                ->where('order_id', $orderRuleObj->vehicle_reservation_id)
                ->first(['id', 'docusign_envelope_id', 'docusign_status']);

            if ($InsuranceQuoteObj
                && $InsuranceQuoteObj->docusign_status == 1
                && $InsuranceQuoteObj->docusign_envelope_id != ''
            ) {
                $docusign_envelope_id = $InsuranceQuoteObj->docusign_envelope_id;
            }
        } else {
            $insuranceObj = DB::table('insurance_quotes')
                ->where('id', $order)
                ->where('docusign_envelope_id', '!=', '')
                ->first();

            $docusign_envelope_id = $insuranceObj->docusign_envelope_id ?? '';
        }

        $envelopObj = [];
        try {
            if (!empty($docusign_envelope_id)) {
                $envelopObj = $envelope_api->listDocuments($args['account_id'], $docusign_envelope_id);
            }
        } catch (\Exception $e) {
            return response($e->getMessage(), 500);
        }

        return view('admin.insurance_provider.docusign._listdocuments', compact(
            'docusign_envelope_id',
            'envelopObj',
            'myModal',
            'OrderDepositRuleId'
        ));
    }

    public function fetchdocument(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $docusign_envelope_id = $request->input('docusign_envelope_id');
        $document_id = $request->input('document_id', 1);
        $OrderDepositRuleId = $request->input('OrderDepositRuleId', 0);
        $document_name = $request->input('document_name');
        $document_name = $document_name ? preg_replace('/\s+/', '_', $document_name) : $document_id;

        $this->args = $this->getTemplateArgs();
        $args = $this->args;
        $envelope_api = $this->getEnvelopeApi();

        $file = '';
        try {
            if (!empty($docusign_envelope_id) && !empty($document_id)) {
                $envelopObj = $envelope_api->getDocument($args['account_id'], $document_id, $docusign_envelope_id);

                $file = 'files/Agreement_Sign_Doc/OrderRuleId_' . $OrderDepositRuleId . '/' . $document_name . '.pdf';
                $dir = public_path('files/Agreement_Sign_Doc/OrderRuleId_' . $OrderDepositRuleId);
                if (!file_exists($dir)) {
                    @mkdir($dir, 0755, true);
                }

                $fileName = public_path($file);
                $fp = fopen($fileName, 'w');
                fwrite($fp, file_get_contents($envelopObj->getPathname()));
                fclose($fp);
            }
        } catch (\Exception $e) {
            return response($e->getMessage(), 500);
        }

        return view('admin.insurance_provider.docusign._displaydocument', compact('file'));
    }

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
            'account_id'      => config('legacy.Docusign.accountid'),
            'base_path'       => config('legacy.Docusign.url') . '/restapi',
            'ds_access_token' => $token['access_token'],
            'envelope_args'   => [
                'signer_client_id' => 1000,
                'ds_return_url'    => url('/insurance_provider/docusign/returncallback'),
            ],
        ];
    }
}
