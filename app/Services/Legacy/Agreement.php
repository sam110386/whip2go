<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;

/**
 * Ported from CakePHP app/Controller/Component/AgreementComponent.php
 *
 * Calls a local Node.js API (localhost:3000) to generate various PDF agreements
 * (buying, insurance, rental, lease, power of attorney, loan, CMM card, receipts).
 */
class Agreement
{
    private array $canada = [
        'NL' => 'NL', 'PE' => 'PE', 'NS' => 'NS', 'NB' => 'NB',
        'QC' => 'QC', 'ON' => 'ON', 'MB' => 'MB', 'SK' => 'SK',
        'AB' => 'AB', 'BC' => 'BC', 'YT' => 'YT', 'NT' => 'NT',
        'NU' => 'NU',
    ];

    private string $canadaRentalAgreementTemplate;
    private string $rentalAgreementTemplate;
    private string $rentToOwnAgreementTemplate;
    private string $leaseAgreementTemplate;
    private string $leaseToOwnAgreementTemplate;
    private string $apiUrl = 'http://localhost:3000/';

    public function __construct()
    {
        $this->canadaRentalAgreementTemplate = public_path('files/canada_agreement_rental.html');
        $this->rentalAgreementTemplate       = public_path('files/agreement_templates/rental.html');
        $this->rentToOwnAgreementTemplate    = public_path('files/agreement_templates/rent_to_own.html');
        $this->leaseAgreementTemplate        = public_path('files/agreement_templates/lease.html');
        $this->leaseToOwnAgreementTemplate   = public_path('files/agreement_templates/lease_to_own.html');
    }

    public function generateBuyingAgreement(array $obj, string $filename): array
    {
        $obj['filename'] = '/var/www/shared/agreements/temp/' . $filename;

        $result = $this->sendHttpRequest($obj, 'generatebuyingagreement');

        if (isset($result['error'])) {
            return ['status' => false, 'message' => $result['error'], 'data' => 'error returned'];
        }

        return ['status' => true, 'message' => 'Success', 'filename' => ''];
    }

    public function generateInsuranceToken(array $obj, string $filename): array
    {
        $obj['filename'] = '/var/www/shared/insurancedoc/' . $filename;

        $result = $this->sendHttpRequest($obj, 'generateinsurancetoken');

        if (isset($result['error'])) {
            return ['status' => false, 'message' => $result['error'], 'data' => 'error returned'];
        }

        return ['status' => true, 'message' => 'Success', 'filename' => ''];
    }

    public function generateAgreementPdf(array $obj, string $filename): array
    {
        $obj['filename'] = $filename;
        $obj['tbd'] = $obj['tbd'] ?? 0;
        $obj['template'] = $this->rentalAgreementTemplate;

        if (isset($this->canada[$obj['Owner']['state'] ?? ''])) {
            $obj['template'] = $this->canadaRentalAgreementTemplate;
        }

        $result = $this->sendHttpRequest($obj, 'generateagreementpdf');

        if (isset($result['error'])) {
            return ['status' => false, 'message' => $result['error'], 'data' => 'error returned'];
        }

        return ['status' => true, 'message' => 'Success', 'filename' => ''];
    }

    public function generateQuoteAgreementPdf(array $obj, string $filename): array
    {
        $obj['filename'] = $filename;
        $obj['tbd'] = $obj['tbd'] ?? 0;
        $method = 'generateagreementpdf';
        $obj['template'] = $this->rentalAgreementTemplate;

        $ownerId = $obj['Owner']['id'] ?? '';
        $templateBase = public_path('files/agreement_templates/');

        if (file_exists($templateBase . $ownerId . '_rental.html')) {
            $obj['template'] = $templateBase . $ownerId . '_rental.html';
        }

        if (($obj['financing'] ?? 0) == 2) {
            $obj['template'] = $this->rentToOwnAgreementTemplate;
            $method = 'generateRentToOwnAgreementpdf';
            if (file_exists($templateBase . $ownerId . '_rent_to_own.html')) {
                $obj['template'] = $templateBase . $ownerId . '_rent_to_own.html';
            }
        }

        if (($obj['financing'] ?? 0) == 3 || ($obj['financing'] ?? 0) == 4) {
            $obj['template'] = $this->leaseAgreementTemplate;
            if (file_exists($templateBase . $ownerId . '_lease.html')) {
                $obj['template'] = $templateBase . $ownerId . '_lease.html';
            }
            if (($obj['financing'] ?? 0) == 4) {
                $obj['template'] = $this->leaseToOwnAgreementTemplate;
                if (file_exists($templateBase . $ownerId . '_lease_to_own.html')) {
                    $obj['template'] = $templateBase . $ownerId . '_lease_to_own.html';
                }
            }
            $method = 'generateLeaseAgreementpdf';
        }

        $obj['currency'] = NumberFormatter::getCurrencySymbol($obj['currency'] ?? 'USD');

        $result = $this->sendHttpRequest($obj, $method);

        if (isset($result['error'])) {
            return ['status' => false, 'message' => $result['error'], 'data' => 'error returned'];
        }

        return ['status' => true, 'message' => 'Success', 'filename' => ''];
    }

    public function generatePowerOfAttorny(array $obj, string $filename): array
    {
        $obj['filename'] = $filename;
        $obj['currency'] = NumberFormatter::getCurrencySymbol($obj['currency'] ?? 'USD');

        $result = $this->sendHttpRequest($obj, 'powerofatorny');

        if (isset($result['error'])) {
            return ['status' => false, 'message' => $result['error'], 'data' => 'error returned'];
        }

        return ['status' => true, 'message' => 'Success', 'filename' => ''];
    }

    public function generateLoanAgreement(array $obj, string $filename): array
    {
        $obj['filename'] = $filename;
        $obj['currency'] = NumberFormatter::getCurrencySymbol($obj['currency'] ?? 'USD');

        $result = $this->sendHttpRequest($obj, 'loanagreement');

        if (isset($result['error'])) {
            return ['status' => false, 'message' => $result['error'], 'data' => 'error returned'];
        }

        return ['status' => true, 'message' => 'Success', 'filename' => ''];
    }

    public function generateDriverFianancedQuoteAgreement(array $obj, string $filename): array
    {
        $obj['filename'] = $filename;
        $obj['currency'] = NumberFormatter::getCurrencySymbol($obj['currency'] ?? 'USD');

        $result = $this->sendHttpRequest($obj, 'loanagreement');

        if (isset($result['error'])) {
            return ['status' => false, 'message' => $result['error'], 'data' => 'error returned'];
        }

        return ['status' => true, 'message' => 'Success', 'filename' => ''];
    }

    public function generateCMMCard(array $obj, string $filename): array
    {
        $obj['filename'] = $filename;
        $obj['template'] = public_path('files/agreement_templates/maintenance_card.html');

        $result = $this->sendHttpRequest($obj, 'cmmdigitalcard');

        if (isset($result['error'])) {
            return ['status' => false, 'message' => $result['error'], 'data' => 'error returned'];
        }

        return ['status' => true, 'message' => 'Success', 'filename' => ''];
    }

    public function generatePaymentreciept(array $obj, string $filename): array
    {
        $obj['filename'] = $filename;
        $obj['currency'] = NumberFormatter::getCurrencySymbol($obj['currency'] ?? 'USD');

        $result = $this->sendHttpRequest($obj, 'paymentreciept');

        if (isset($result['error'])) {
            return ['status' => false, 'message' => $result['error'], 'data' => 'error returned'];
        }

        return ['status' => true, 'message' => 'Success', 'filename' => ''];
    }

    public function sendHttpRequest(array $requestBody = [], string $action = ''): ?array
    {
        $url = $this->apiUrl . $action;

        $response = Http::withHeaders([
            'Content-Type'   => 'application/json',
            'Accept-Charset' => 'utf-8',
        ])->post($url, $requestBody);

        return $response->json();
    }
}
