<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Legacy\DriverFinancedInsuranceQuote;
use App\Models\Legacy\InsuranceQuote;
use App\Models\Legacy\OrderDepositRule;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\DocusignTrait;
use Exception;

/**
 * Ported from CakePHP InsuranceProvider/Controller/DocusignController.php
 */
class DocusignController extends LegacyAppController
{
    use DocusignTrait;

    public function listdocuments(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $quoteid = $request->input('quoteid');
        $myModal = $request->input('model', 'myModal');
        $orderDepositRuleId = $request->input('OrderDepositRuleId');

        $this->args = $this->getTemplateArgs();
        $envelopeApi = $this->getEnvelopeApi();
        $accountId = $this->args['account_id'];

        $orderRuleObj = OrderDepositRule::select('id', 'insurance_payer', 'vehicle_reservation_id')->find($orderDepositRuleId);

        if (!$orderRuleObj) {
            abort(404, 'Order Deposit Rule not found.');
        }

        $insurancePayer = $orderRuleObj->insurance_payer;
        $docusignEnvelopeId = '';

        if (in_array($insurancePayer, [5, 6, 7])) {
            $insuranceQuoteObj = DriverFinancedInsuranceQuote::select('id', 'docusign_envelope_id', 'docusign_status')
                ->where('order_id', $orderRuleObj->vehicle_reservation_id)
                ->first();

            if (
                $insuranceQuoteObj
                && $insuranceQuoteObj->docusign_status == 1
                && !empty($insuranceQuoteObj->docusign_envelope_id)
            ) {
                $docusignEnvelopeId = $insuranceQuoteObj->docusign_envelope_id;
            }
        } else {
            $insuranceObj = InsuranceQuote::where('id', $quoteid)
                ->where('docusign_envelope_id', '!=', '')
                ->first();

            if ($insuranceObj) {
                $docusignEnvelopeId = $insuranceObj->docusign_envelope_id;
            }
        }

        $envelopObj = [];
        try {
            if (!empty($docusignEnvelopeId)) {
                $envelopObj = $envelopeApi->listDocuments($accountId, $docusignEnvelopeId);
            }
        } catch (Exception $e) {
            return response($e->getMessage(), 500);
        }

        return view('admin.insurance_provider.docusign._listdocuments', compact(
            'docusignEnvelopeId',
            'envelopObj',
            'myModal',
            'orderDepositRuleId'
        ));
    }
    public function fetchdocument(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $docusign_envelope_id = $request->input('docusign_envelope_id');
        $document_id = $request->input('document_id', 1);
        $orderDepositRuleId = $request->input('OrderDepositRuleId', 0);
        $document_name = $request->input('document_name');
        $document_name = $document_name
            ? preg_replace('/\s+/', '_', $document_name)
            : $document_id;

        $this->args = $this->getTemplateArgs();
        $envelopeApi = $this->getEnvelopeApi();
        $accountId = $this->args['account_id'];

        $file = '';
        try {
            if (!empty($docusign_envelope_id) && !empty($document_id)) {
                $envelopObj = $envelopeApi->getDocument($accountId, $document_id, $docusign_envelope_id);

                $file = 'files/Agreement_Sign_Doc/OrderRuleId_' . $orderDepositRuleId . '/' . $document_name . '.pdf';
                $dir = public_path('files/Agreement_Sign_Doc/OrderRuleId_' . $orderDepositRuleId);

                if (!file_exists($dir)) {
                    @mkdir($dir, 0755, true);
                }

                $fileName = public_path($file);
                $fp = fopen($fileName, 'w');
                fwrite($fp, file_get_contents($envelopObj->getPathname()));
                fclose($fp);
            }
        } catch (Exception $e) {
            return response($e->getMessage(), 500);
        }

        return view('admin.insurance_provider.docusign._displaydocument', compact('file'));
    }
}
