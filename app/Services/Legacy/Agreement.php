<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\Legacy\Number as LegacyNumber;

/**
 * Ported from CakePHP app/Controller/Component/AgreementComponent.php
 */
class Agreement
{
    private $_CANADA = [
        "NL" => "NL",
        "PE" => "PE",
        "NS" => "NS",
        "NB" => "NB",
        "QC" => "QC",
        "ON" => "ON",
        "MB" => "MB",
        "SK" => "SK",
        "AB" => "AB",
        "BC" => "BC",
        "YT" => "YT",
        "NT" => "NT",
        "NU" => "NU"
    ];
    private $_CANADARentalAgreementTemplate;
    private $_RentalAgreementTemplate;
    private $_RentToOwnAgreementTemplate;
    private $_LeaseAgreementTemplate;
    private $_LeaseToOwnAgreementTemplate;
    private $apiurl;

    public function __construct()
    {
        $this->apiurl = config('legacy.Agreement.api_url', 'http://localhost:3000/');
        $this->_CANADARentalAgreementTemplate = public_path('files/canada_agreement_rental.html');
        $this->_RentalAgreementTemplate = public_path('files/agreement_templates/rental.html');
        $this->_RentToOwnAgreementTemplate = public_path('files/agreement_templates/rent_to_own.html');
        $this->_LeaseAgreementTemplate = public_path('files/agreement_templates/lease.html');
        $this->_LeaseToOwnAgreementTemplate = public_path('files/agreement_templates/lease_to_own.html');
    }
    public function generateBuyingAgreement(array $obj, string $filename)
    {
        $obj['filename'] = public_path('files/agreements/temp/' . $filename);
        $actualReport = $this->sendHttpRequest($obj, 'generatebuyingagreement');

        if (isset($actualReport['error'])) {
            return [
                "status" => false,
                "message" => $actualReport['error'],
                "data" => "error returned"
            ];
        }

        return [
            "status" => true,
            "message" => "Success",
            "filename" => ""
        ];
    }
    public function generateInsuranceToken(array $obj, string $filename)
    {
        $obj['filename'] = public_path('files/insurancedoc/' . $filename);
        $actualReport = $this->sendHttpRequest($obj, 'generateinsurancetoken');

        if (isset($actualReport['error'])) {
            return [
                "status" => false,
                "message" => $actualReport['error'],
                "data" => "error returned"
            ];
        }

        return [
            "status" => true,
            "message" => "Success",
            "filename" => ""
        ];
    }
    public function generateAgreementPdf(array $obj, string $filename)
    {
        $obj['filename'] = $filename;
        $obj['tbd'] = isset($obj['tbd']) ? $obj['tbd'] : 0;
        $obj['template'] = $this->_RentalAgreementTemplate;

        if (isset($this->_CANADA[$obj['owner']['state']])) {
            $obj['template'] = $this->_CANADARentalAgreementTemplate;
        }

        $actualReport = $this->sendHttpRequest($obj, 'generateagreementpdf');

        if (isset($actualReport['error'])) {
            return [
                "status" => false,
                "message" => $actualReport['error'],
                "data" => "error returned"
            ];
        }

        return [
            "status" => true,
            "message" => "Success",
            "filename" => ""
        ];
    }
    public function generateQuoteAgreementPdf(array $obj, string $filename)
    {
        $obj['filename'] = $filename;
        $obj['tbd'] = isset($obj['tbd']) ? $obj['tbd'] : 0;
        $method = 'generateagreementpdf';
        $obj['template'] = $this->_RentalAgreementTemplate;

        if (file_exists(public_path('files/agreement_templates/' . $obj['owner']['id'] . '_rental.html'))) {
            $obj['template'] = public_path('files/agreement_templates/' . $obj['owner']['id'] . '_rental.html');
        }

        if ($obj['financing'] == 2) {
            $obj['template'] = $this->_RentToOwnAgreementTemplate;
            $method = 'generateRentToOwnAgreementpdf';

            if (file_exists(public_path('files/agreement_templates/' . $obj['owner']['id'] . '_rent_to_own.html'))) {
                $obj['template'] = public_path('files/agreement_templates/' . $obj['owner']['id'] . '_rent_to_own.html');
            }
        }

        if (
            $obj['financing'] == 3
            || $obj['financing'] == 4
        ) {
            $obj['template'] = $this->_LeaseAgreementTemplate;

            if (file_exists(public_path('files/agreement_templates/' . $obj['owner']['id'] . '_lease.html'))) {
                $obj['template'] = public_path('files/agreement_templates/' . $obj['owner']['id'] . '_lease.html');
            }

            if ($obj['financing'] == 4) { // Lease to Own
                $obj['template'] = $this->_LeaseToOwnAgreementTemplate;

                if (file_exists(public_path('files/agreement_templates/' . $obj['owner']['id'] . '_lease_to_own.html'))) {
                    $obj['template'] = public_path('files/agreement_templates/' . $obj['owner']['id'] . '_lease_to_own.html');
                }
            }

            $method = 'generateLeaseAgreementpdf';
        }

        $obj['currency'] = LegacyNumber::getCurrencySymbol($obj['currency'] ?? 'USD');
        $actualReport = $this->sendHttpRequest($obj, $method);

        if (isset($actualReport['error'])) {
            return [
                "status" => false,
                "message" => $actualReport['error'],
                "data" => "error returned"
            ];
        }

        return [
            "status" => true,
            "message" => "Success",
            "filename" => ""
        ];
    }
    public function generatePowerOfAttorny(array $obj, string $filename)
    {
        $obj['filename'] = $filename;
        $method = 'powerofatorny';
        $obj['currency'] = LegacyNumber::getCurrencySymbol($obj['currency'] ?? 'USD');
        $actualReport = $this->sendHttpRequest($obj, $method);

        if (isset($actualReport['error'])) {
            return [
                "status" => false,
                "message" => $actualReport['error'],
                "data" => "error returned"
            ];
        }

        return [
            "status" => true,
            "message" => "Success",
            "filename" => ""
        ];
    }
    public function generateLoanAgreement(array $obj, string $filename)
    {
        $obj['filename'] = $filename;
        $method = 'loanagreement';
        $obj['currency'] = LegacyNumber::getCurrencySymbol($obj['currency'] ?? 'USD');
        $actualReport = $this->sendHttpRequest($obj, $method);

        if (isset($actualReport['error'])) {
            return [
                "status" => false,
                "message" => $actualReport['error'],
                "data" => "error returned"
            ];
        }

        return [
            "status" => true,
            "message" => "Success",
            "filename" => ""
        ];
    }
    public function generateDriverFianancedQuoteAgreement(array $obj, string $filename)
    {
        $obj['filename'] = $filename;
        $method = 'loanagreement';
        $obj['currency'] = LegacyNumber::getCurrencySymbol($obj['currency'] ?? 'USD');
        $actualReport = $this->sendHttpRequest($obj, $method);

        if (isset($actualReport['error'])) {
            return [
                "status" => false,
                "message" => $actualReport['error'],
                "data" => "error returned"
            ];
        }

        return [
            "status" => true,
            "message" => "Success",
            "filename" => ""
        ];
    }
    public function generateCMMCard(array $obj, string $filename)
    {
        $obj['filename'] = $filename;
        $method = 'cmmdigitalcard';
        $obj['template'] = public_path('files/agreement_templates/maintenance_card.html');
        $actualReport = $this->sendHttpRequest($obj, $method);

        if (isset($actualReport['error'])) {
            return [
                "status" => false,
                "message" => $actualReport['error'],
                "data" => "error returned"
            ];
        }

        return [
            "status" => true,
            "message" => "Success",
            "filename" => ""
        ];
    }
    public function generatePaymentreciept(array $obj, string $filename)
    {
        $obj['filename'] = $filename;
        $method = 'paymentreciept';
        $obj['currency'] = LegacyNumber::getCurrencySymbol($obj['currency'] ?? 'USD');
        $actualReport = $this->sendHttpRequest($obj, $method);

        if (isset($actualReport['error'])) {
            return [
                "status" => false,
                "message" => $actualReport['error'],
                "data" => "error returned"
            ];
        }

        return [
            "status" => true,
            "message" => "Success",
            "filename" => ""
        ];
    }
    private function sendHttpRequest(array $requestBody = [], string $action = '')
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

