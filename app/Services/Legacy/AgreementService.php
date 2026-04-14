<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AgreementService
{
    private $_CANADA = ["NL" => "NL", "PE" => "PE", "NS" => "NS", "NB" => "NB", "QC" => "QC", "ON" => "ON", "MB" => "MB", "SK" => "SK", "AB" => "AB", "BC" => "BC", "YT" => "YT", "NT" => "NT", "NU" => "NU"];
    
    // In Laravel, we use public_path() for paths.
    private $apiurl;

    public function __construct()
    {
        // Default to localhost:3000 if not set in ENV
        $this->apiurl = config('services.agreement_api.url', 'http://localhost:3000/');
    }

    /**
     * Non PTO Agreement PDF
     */
    public function generateInsuranceToken($obj, $filename)
    {
        $obj['filename'] = public_path('files/insurancedoc/' . $filename);
        $actualReport = $this->sendHttpRequest($obj, 'generateinsurancetoken');
        if (isset($actualReport['error'])) {
            return ["status" => false, "message" => $actualReport['error'], "data" => "error returned"];
        }
        return ["status" => true, "message" => "Success", "filename" => ""];
    }

    /**
     * function New get agreement pdf
     */
    public function generateAgreementPdf($obj, $filename)
    {
        $obj['filename'] = $filename;
        $obj['tbd'] = isset($obj['tbd']) ? $obj['tbd'] : 0;
        $obj['template'] = public_path('files/agreement_templates/rental.html');
        
        if (isset($this->_CANADA[$obj['Owner']['state']])) {
            $obj['template'] = public_path('files/canada_agreement_rental.html');
        }
        
        $actualReport = $this->sendHttpRequest($obj, 'generateagreementpdf');
        if (isset($actualReport['error'])) {
            return ["status" => false, "message" => $actualReport['error'], "data" => "error returned"];
        }
        return ["status" => true, "message" => "Success", "filename" => ""];
    }

    /**
     * function New get agreement pdf
     */
    public function generateQuoteAgreementPdf($obj, $filename)
    {
        $obj['filename'] = $filename;
        $obj['tbd'] = isset($obj['tbd']) ? $obj['tbd'] : 0;
        $method = 'generateagreementpdf';
        $obj['template'] = public_path('files/agreement_templates/rental.html');

        if (file_exists(public_path('files/agreement_templates/' . $obj['Owner']['id'] . '_rental.html'))) {
            $obj['template'] = public_path('files/agreement_templates/' . $obj['Owner']['id'] . '_rental.html');
        }

        if ($obj['financing'] == 2) {
            $obj['template'] = public_path('files/agreement_templates/rent_to_own.html');
            $method = 'generateRentToOwnAgreementpdf';
            if (file_exists(public_path('files/agreement_templates/' . $obj['Owner']['id'] . '_rent_to_own.html'))) {
                $obj['template'] = public_path('files/agreement_templates/' . $obj['Owner']['id'] . '_rent_to_own.html');
            }
        }

        // if Financing is LEASE or LEASE To OWN
        if ($obj['financing'] == 3 || $obj['financing'] == 4) {
            $obj['template'] = public_path('files/agreement_templates/lease.html');
            if (file_exists(public_path('files/agreement_templates/' . $obj['Owner']['id'] . '_lease.html'))) {
                $obj['template'] = public_path('files/agreement_templates/' . $obj['Owner']['id'] . '_lease.html');
            }
            if ($obj['financing'] == 4) { // Lease to Own
                $obj['template'] = public_path('files/agreement_templates/lease_to_own.html');
                if (file_exists(public_path('files/agreement_templates/' . $obj['Owner']['id'] . '_lease_to_own.html'))) {
                    $obj['template'] = public_path('files/agreement_templates/' . $obj['Owner']['id'] . '_lease_to_own.html');
                }
            }
            $method = 'generateLeaseAgreementpdf';
        }

        $actualReport = $this->sendHttpRequest($obj, $method);
        if (isset($actualReport['error'])) {
            return ["status" => false, "message" => $actualReport['error'], "data" => "error returned"];
        }
        return ["status" => true, "message" => "Success", "filename" => ""];
    }

    public function generateCMMCard($obj, $filename)
    {
        $obj['filename'] = $filename;
        $method = 'cmmdigitalcard';
        $obj['template'] = public_path('files/agreement_templates/maintenance_card.html');
        $actualReport = $this->sendHttpRequest($obj, $method);
        if (isset($actualReport['error'])) {
            return ["status" => false, "message" => $actualReport['error'], "data" => "error returned"];
        }
        return ["status" => true, "message" => "Success", "filename" => ""];
    }

    private function sendHttpRequest($requestBody = [], $action = '')
    {
        $url = $this->apiurl . $action;
        
        try {
            $response = Http::withHeaders([
                'Content-type' => 'application/json',
                'Accept-Charset' => 'utf-8',
            ])->post($url, $requestBody);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Agreement API error: " . $response->body());
            return ['error' => 'API Request failed with status ' . $response->status()];

        } catch (\Exception $e) {
            Log::error("Agreement API exception: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
