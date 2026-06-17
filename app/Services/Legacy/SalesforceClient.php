<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Log;
use Exception;
use SforcePartnerClient;
use QueryOptions;
use SObject;
use SoapFault;

/**
 * Port of CakePHP app/Lib/Salesforce.php
 */
class SalesforceClient
{
    private string $_recordTypeOther = '0121U000000EnwW';
    private string $_recordTypeDriver = '0121U000000EnwW';
    private string $_recordTypeDealer = '0121U000000EnwR';
    private ?string $_username;
    private ?string $_password;
    private $mySforceConnection;
    private bool $moduleEnabled = false;


    public function __construct()
    {
        $this->_username = config('legacy.Salesforce.username');
        $this->_password = config('legacy.Salesforce.password');
        $this->moduleEnabled = (bool) config('legacy.Salesforce.enabled', false);
    }
    private function startme(): void
    {
        $this->mySforceConnection = new SforcePartnerClient();
        $this->mySforceConnection->createConnection(app_path('Vendor/Salesforce/partner.wsdl.xml'));
        $this->mySforceConnection->login($this->_username, $this->_password);
    }
    public function getUserInfo(): void
    {
        try {
            $this->startme();
            Log::info($this->mySforceConnection->getUserInfo());
        } catch (Exception $e) {
            Log::error('Salesforce getUserInfo failed: ' . $e->getMessage());
        }
    }
    public function createUser(array $userinfo): void
    {
        if (!$this->moduleEnabled) {
            return;
        }

        $this->startme();

        $createFields = [
            'FirstName' => $userinfo['first_name'] ?? '',
            'LastName' => $userinfo['last_name'] ?? '',
            'Email' => $userinfo['email'] ?? '',
            'Company' => "DIA",
            "Phone" => $userinfo['contact_number'] ?? '',
            "User_ID__c" => $userinfo['id'] ?? '',
            "Verified__c" => "No",
            "Uber_Lyft__c" => "No",
            "Credit_Card_added__c" => "No",
            "License__c" => "No",
            "RecordTypeId" => $this->_recordTypeOther
        ];

        $userid = $userinfo['id'];

        try {
            $query = "SELECT Id from Lead where User_ID__c = '$userid' OR email = '" . $userinfo['email'] . "'";
            $queryOptions = new QueryOptions(300);
            $this->mySforceConnection->setQueryOptions($queryOptions);
            $queryResponse = $this->mySforceConnection->query($query);

            if ($queryResponse->size) {
                $sObject = new SObject($queryResponse);
                $id = $sObject->{'0'}->Id;

                $createFields['Id'] = $id;

                $sObject1 = new SObject();
                $sObject1->fields = $createFields;
                $sObject1->type = 'Lead';

                $this->mySforceConnection->update([$sObject1]);
            } else {
                $sObject1 = new SObject();
                $sObject1->fields = $createFields;
                $sObject1->type = 'Lead';

                $this->mySforceConnection->create([$sObject1]);
            }

        } catch (SoapFault $fault) {
            Log::error('Salesforce createUser SoapFault: ' . $fault->getMessage());
        }
    }
    public function deleteUser(): void
    {
        if (!$this->moduleEnabled) {
            return;
        }

        $this->startme();
        $query = "SELECT Id from Lead where Email = 'Favsab2019@gmail.com'";
        $queryOptions = new QueryOptions(300);

        try {
            $this->mySforceConnection->setQueryOptions($queryOptions);
            $queryResponse = $this->mySforceConnection->query($query);

            if ($queryResponse->size) {
                $this->deleteAll($queryResponse);
            }
        } catch (SoapFault $fault) {
            Log::error('Salesforce deleteUser SoapFault: ' . $fault->getMessage());
        }
    }
    public function updateUser($userid, array $updateField, string $Type = 'Other'): void
    {
        if (!$this->moduleEnabled || empty($userid)) {
            return;
        }

        $this->startme();

        try {
            $header = match ($Type) {
                'Driver' => $this->_recordTypeDriver,
                'Dealer' => $this->_recordTypeDealer,
                default => $this->_recordTypeOther,
            };

            $query = "SELECT Id from Lead where User_ID__c = '$userid'";
            $queryOptions = new QueryOptions(300);
            $this->mySforceConnection->setQueryOptions($queryOptions);
            $queryResponse = $this->mySforceConnection->query($query);

            if ($queryResponse->size) {
                $sObject = new SObject($queryResponse);
                $id = $sObject->{'0'}->Id;

                $updateField['Id'] = $id;
                $updateField['RecordTypeId'] = $header;

                $sObject1 = new SObject();
                $sObject1->fields = $updateField;
                $sObject1->type = 'Lead';

                $this->mySforceConnection->update([$sObject1]);
            }
        } catch (SoapFault $fault) {
            Log::error('Salesforce updateUser SoapFault: ' . $fault->getMessage());
        }
    }
    public function deleteAll($queryResult): void
    {
        $records = $queryResult->records;
        $buckets = array_chunk($records, 200);

        foreach ($buckets as $bucket) {
            $ids = [];
            foreach ($bucket as $record) {
                $sObject = new SObject($record);
                $ids[] = $sObject->Id;
            }
            try {
                $this->mySforceConnection->delete($ids);
            } catch (Exception $e) {
                Log::error('Salesforce deleteAll iteration error: ' . $e->getMessage());
            }
        }

        $queryLocator = $queryResult->queryLocator ?? null;
        if (isset($queryLocator)) {
            $result = $this->mySforceConnection->queryMore($queryLocator);
            $this->deleteAll($result);
        }
    }
    public function createBooking(array $data): void
    {
        if (!$this->moduleEnabled) {
            return;
        }

        $this->startme();

        try {
            $booking = $data['Booking_ID__c'] ?? '';
            $query = "SELECT Id from Booking__c where Booking_ID__c = '$booking'";
            $queryOptions = new QueryOptions(300);
            $this->mySforceConnection->setQueryOptions($queryOptions);
            $queryResponse = $this->mySforceConnection->query($query);

            if ($queryResponse->size) {
                $sObject = new SObject($queryResponse);
                $id = $sObject->{'0'}->Id;
                $updateFields = array_merge($data, ['Id' => $id]);

                $sObject1 = new SObject();
                $sObject1->fields = $updateFields;
                $sObject1->type = 'Booking__c';

                $this->mySforceConnection->update([$sObject1]);
            } else {
                $sObject1 = new SObject();
                $sObject1->fields = $data;
                $sObject1->type = 'Booking__c';

                $this->mySforceConnection->create([$sObject1]);
            }
        } catch (SoapFault $fault) {
            Log::error('Salesforce createBooking SoapFault: ' . $fault->getMessage());
        }
    }
}
