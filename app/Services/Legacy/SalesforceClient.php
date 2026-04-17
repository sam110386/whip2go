<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Log;

/**
 * Port of CakePHP app/Lib/Salesforce.php
 *
 * The legacy Salesforce integration used a SOAP-based PHP SDK (SforcePartnerClient)
 * and had `moduleEnabled = false` effectively disabling it in production.
 * This port preserves all method signatures as stubs with logging.
 * When Salesforce integration is re-enabled, replace stubs with the appropriate
 * Laravel-compatible SDK (e.g., omniphx/forrest).
 */
class SalesforceClient
{
    private bool $moduleEnabled = false;

    private string $recordTypeOther = '0121U000000EnwW';
    private string $recordTypeDriver = '0121U000000EnwW';
    private string $recordTypeDealer = '0121U000000EnwR';

    public function createUser(array $userInfo): void
    {
        if (!$this->moduleEnabled) {
            return;
        }
        Log::info('SalesforceClient::createUser – stub', ['user_id' => $userInfo['id'] ?? null]);
    }

    public function deleteUser(): void
    {
        if (!$this->moduleEnabled) {
            return;
        }
        Log::info('SalesforceClient::deleteUser – stub');
    }

    public function updateUser(int $userId, array $updateField, string $type = 'Other'): void
    {
        if (!$this->moduleEnabled) {
            return;
        }
        Log::info('SalesforceClient::updateUser – stub', compact('userId', 'type'));
    }

    public function createBooking(array $data): void
    {
        if (!$this->moduleEnabled) {
            return;
        }
        Log::info('SalesforceClient::createBooking – stub', ['booking_id' => $data['Booking_ID__c'] ?? null]);
    }
}
