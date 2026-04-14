<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HomenetFeed
{
    private int $_filterSellingPriceFrom = 0;
    private int $_filterSellingPriceTo = 0;
    private int $_filterOdometer = 0;
    private int $_filterOderDays = 0;
    private array $_filterMake = [];
    private array $_filterModel = [];
    private array $_filterYear = [];

    private ?object $_DealerSettingObj = null;
    private ?object $_depositTemplateObj = null;

    private string $_vehicleFeeds = '';

    public function process(): void
    {
        $savvyDealers = DB::table('savvy_dealers')
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('last_processed', '!=', date('Y-m-d'))
                  ->orWhereNull('last_processed');
            })
            ->limit(1)
            ->get();

        foreach ($savvyDealers as $savvyDealer) {
            $this->pullAndProcess($savvyDealer);

            DB::table('savvy_dealers')
                ->where('id', $savvyDealer->id)
                ->update(['last_processed' => date('Y-m-d'), 'run_now' => 0]);
        }
    }

    public function pullAndProcess(object $savvyDealer): void
    {
        $filters = json_decode($savvyDealer->filters, true) ?: [];
        $this->_filterSellingPriceFrom = !empty($filters['sellingprice']['from']) ? (int) $filters['sellingprice']['from'] : 0;
        $this->_filterSellingPriceTo = !empty($filters['sellingprice']['to']) ? (int) $filters['sellingprice']['to'] : 0;
        $this->_filterOdometer = !empty($filters['odometer']) ? (int) $filters['odometer'] : 0;
        $this->_filterOderDays = !empty($filters['older_days']) ? (int) $filters['older_days'] : 0;
        $this->_filterMake = !empty($filters['make']) ? array_map('trim', explode(',', strtolower($filters['make']))) : [];
        $this->_filterModel = !empty($filters['model']) ? array_map('trim', explode(',', strtolower($filters['model']))) : [];
        $this->_filterYear = !empty($filters['year']) ? explode('-', strtolower($filters['year'])) : [];

        $seenVehicleIDs = [];

        $this->_DealerSettingObj = DB::table('cs_settings')
            ->where('user_id', $savvyDealer->user_id)
            ->first();

        $this->_depositTemplateObj = DB::table('deposit_templates')
            ->where('user_id', $savvyDealer->user_id)
            ->first();

        $url = $savvyDealer->search_url;
        $feedResponse = $this->pullFeed($url);
        if (!$feedResponse['status']) {
            return;
        }

        [$header, $vehicles] = $this->parseVehicleData();
        if (empty($header) || empty($vehicles)) {
            return;
        }

        foreach ($vehicles as $vehicleTemp) {
            $vehicle = $this->sortVehicleWithHeader($header, $vehicleTemp);
            if (empty($vehicle)) {
                continue;
            }
            if ($this->_filterSellingPriceFrom != 0 && $vehicle['sellingprice'] < $this->_filterSellingPriceFrom) {
                continue;
            }
            if ($this->_filterSellingPriceTo != 0 && $vehicle['sellingprice'] > $this->_filterSellingPriceTo) {
                continue;
            }
            if (!empty($this->_filterMake) && !in_array(strtolower($vehicle['make']), $this->_filterMake)) {
                continue;
            }
            if (!empty($this->_filterModel) && !in_array(strtolower($vehicle['model']), $this->_filterModel)) {
                continue;
            }
            if (!empty($this->_filterYear) && !empty($this->_filterYear[0]) && !empty($this->_filterYear[1]) && ((int) $vehicle['year'] < (int) $this->_filterYear[0])) {
                continue;
            }
            if (!empty($this->_filterOdometer) && !empty($this->_filterOderDays) && !empty($vehicle['miles']) && !empty($vehicle['datamodifieddate'])) {
                $olderDays = $this->daysBetweenDates($vehicle['datamodifieddate'], date('Y-m-d H:i:s'));
                if ($vehicle['miles'] > $this->_filterOdometer && $olderDays > $this->_filterOderDays) {
                    continue;
                }
            }
            $seenVehicleIDs[] = $this->saveVehicle($vehicle, $savvyDealer->user_id);
        }

        if (empty($seenVehicleIDs)) {
            return;
        }

        $staleVehicles = DB::table('vehicles')
            ->where('from_feed', 1)
            ->where('user_id', $savvyDealer->user_id)
            ->whereNotIn('id', $seenVehicleIDs)
            ->select('id')
            ->get();

        foreach ($staleVehicles as $vehicle) {
            $activeBookings = DB::table('cs_orders')
                ->where('vehicle_id', $vehicle->id)
                ->where('status', 1)
                ->count();
            if ($activeBookings > 0) {
                continue;
            }

            $pendingBookings = DB::table('vehicle_reservations')
                ->where('vehicle_id', $vehicle->id)
                ->where('status', 0)
                ->count();
            if ($pendingBookings > 0) {
                continue;
            }

            $previousBookings = DB::table('cs_orders')
                ->where('vehicle_id', $vehicle->id)
                ->count();
            if ($previousBookings > 0) {
                // vehicle has past bookings - could mark unlisted
            } else {
                DB::table('vehicles')->where('id', $vehicle->id)->delete();
            }
        }
    }

    public function sortVehicleWithHeader(array $header, array $vehicleTemp): array
    {
        $vehicle = [];
        foreach ($header as $key => $val) {
            $vehicle[$val] = $vehicleTemp[$key] ?? '';
        }
        return $vehicle;
    }

    private function saveVehicle(array $record, int $userId): int
    {
        $alreadyExists = DB::table('vehicles')
            ->where('vin_no', $record['vin'])
            ->first();

        if (!empty($alreadyExists) && $alreadyExists->booked == 1) {
            return $alreadyExists->id;
        }

        $dataToSave = [];
        $isUpdate = !empty($alreadyExists);

        if ($isUpdate) {
            $dataToSave['homenet_msrp'] = $record['msrp'] > 1
                ? $record['msrp']
                : ($alreadyExists->homenet_msrp > 0 ? $alreadyExists->homenet_msrp : $record['sellingprice']);
            $dataToSave['msrp'] = $record['sellingprice'] > 1
                ? $record['sellingprice']
                : ($alreadyExists->msrp > 0 ? $alreadyExists->msrp : $dataToSave['homenet_msrp']);
            $dataToSave['vehicleCostInclRecon'] = ($userId == 46092)
                ? ($record['msrp'] ? ($record['msrp'] - 1500) : 0)
                : ($record['invoice'] ? $record['invoice'] : ($record['msrp'] ? $record['msrp'] - 1000 : 0));
            $dataToSave['homenet_modelnumber'] = $record['modelnumber'] ?? 0;
            $sellingPremium = $this->_depositTemplateObj->selling_premium ?? 0;
            $dataToSave['premium_msrp'] = sprintf('%0.2f', $dataToSave['msrp'] + $sellingPremium);
        } else {
            $dataToSave['vehicle_unique_id'] = '';
            $dataToSave['homenet_msrp'] = $record['msrp'] > 1 ? $record['msrp'] : ($record['sellingprice'] > 0 ? $record['sellingprice'] : 0);
            $dataToSave['msrp'] = $record['sellingprice'] > 1 ? $record['sellingprice'] : ($dataToSave['homenet_msrp'] ?? 0);
            $sellingPremium = $this->_depositTemplateObj->selling_premium ?? 0;
            $dataToSave['premium_msrp'] = sprintf('%0.2f', $dataToSave['msrp'] + $sellingPremium);
            $dataToSave['vehicleCostInclRecon'] = ($userId == 46092)
                ? ($record['msrp'] ? ($record['msrp'] - 1500) : 0)
                : ($record['invoice'] ? $record['invoice'] : ($record['msrp'] ? $record['msrp'] - 1000 : 0));
            $dataToSave['homenet_modelnumber'] = $record['modelnumber'] ?? '';
            $dataToSave['type'] = 'real';
            $dataToSave['maintenance_included_fee'] = $this->_depositTemplateObj->maintenance_included_fee ?? 0;
            $dataToSave['roadside_assistance_included'] = $this->_depositTemplateObj->roadside_assistance_included ?? 0;
        }

        $dataToSave['from_feed'] = 1;
        $dataToSave['user_id'] = $userId;
        $dataToSave['make'] = $record['make'];
        $dataToSave['model'] = $record['model'];
        $dataToSave['year'] = $record['year'];
        $dataToSave['color'] = $record['extcolor'];
        $dataToSave['vin_no'] = $record['vin'];
        $dataToSave['details'] = $record['description'];
        $dataToSave['program'] = $this->_DealerSettingObj->vehicle_program ?? 2;
        $dataToSave['financing'] = $this->_DealerSettingObj->vehicle_financing ?? 2;
        $dataToSave['cab_type'] = !empty($record['standardbody']) ? $record['standardbody'] : '';
        $dataToSave['interior_color'] = $record['intcolor'];
        $dataToSave['trim'] = $record['trim'];
        $dataToSave['doors'] = $record['doors'];
        $dataToSave['odometer'] = $record['miles'];
        $dataToSave['allowed_miles'] = $this->_DealerSettingObj->allowed_miles ?? 150;
        $dataToSave['stock_no'] = $record['stock'];
        $dataToSave['engine'] = $record['engliters'];
        $dataToSave['mpg_city'] = $record['epacity'];
        $dataToSave['mpg_hwy'] = $record['epahighway'];
        $dataToSave['equipment'] = !empty($record['options']) ? $this->refineEquipment($record['options']) : '';
        $dataToSave['plate_number'] = null;
        $dataToSave['status'] = $isUpdate ? $alreadyExists->status : $this->vehicleListUnlistStatus($dataToSave);
        $dataToSave['fare_type'] = $this->_depositTemplateObj->fare_type ?? 'D';

        if (isset($record['carfaxhistoryreporturl']) && !empty($record['carfaxhistoryreporturl'])) {
            $dataToSave['accudata'] = json_encode(['carfax' => $record['carfaxhistoryreporturl']]);
        }
        $dataToSave['transmition_type'] = 'A';

        $vehicleName = (!empty($dataToSave['year']) ? substr($dataToSave['year'], -2) . '-' : '')
            . (!empty($dataToSave['make']) ? str_replace(' ', '_', $dataToSave['make']) . '-' : '')
            . (!empty($dataToSave['model']) ? str_replace(' ', '_', $dataToSave['model']) : '')
            . (!empty($dataToSave['vin_no']) ? '-' . substr($dataToSave['vin_no'], -6) : '');
        $dataToSave['vehicle_name'] = $vehicleName;
        $dataToSave['address'] = $this->_DealerSettingObj->address ?? '';
        $dataToSave['lat'] = $this->_DealerSettingObj->address_lat ?? '';
        $dataToSave['lng'] = $this->_DealerSettingObj->address_lng ?? '';
        $dataToSave['multi_location'] = $this->_DealerSettingObj->multi_location ?? 0;

        try {
            if ($isUpdate) {
                DB::table('vehicles')->where('id', $alreadyExists->id)->update($dataToSave);
                $vehicleId = $alreadyExists->id;
            } else {
                $vehicleId = DB::table('vehicles')->insertGetId($dataToSave);
                if ($vehicleId < 999) {
                    $uniqueNo = '1' . sprintf('%04d', $vehicleId);
                } else {
                    $uniqueNo = $vehicleId;
                }
                DB::table('vehicles')->where('id', $vehicleId)->update(['vehicle_unique_id' => $uniqueNo]);
            }

            $this->saveVehicleDepositRule([
                'user_id' => $userId,
                'vehicle_id' => $vehicleId,
                'vehicle_unique_id' => $isUpdate ? $alreadyExists->vehicle_unique_id : ($uniqueNo ?? $vehicleId),
                'make' => $dataToSave['make'],
                'model' => $dataToSave['model'],
            ]);

            if (!empty($record['imagelist'])) {
                if ($isUpdate) {
                    DB::table('vehicle_images')->where('vehicle_id', $vehicleId)->delete();
                }
                $images = array_slice(explode('|', $record['imagelist']), 0, 15);
                $iorder = 1;
                foreach ($images as $imgurl) {
                    $this->addVehicleImage($vehicleId, $imgurl, $iorder++);
                }
            }

            $this->saveVehicleLocation($vehicleId);

            if (!$isUpdate && $dataToSave['fare_type'] == 'L') {
                Free2MoveService::fetchDynamicFare($vehicleId, true);
            }
        } catch (\Exception $e) {
            Log::error('HomenetFeed saveVehicle error', ['error' => $e->getMessage()]);
        }

        return $isUpdate ? $alreadyExists->id : ($vehicleId ?? 0);
    }

    private function saveVehicleLocation(int $vehicleId): void
    {
        DB::table('vehicle_locations')->where('vehicle_id', $vehicleId)->delete();
        $locations = !empty($this->_DealerSettingObj->locations) ? json_decode($this->_DealerSettingObj->locations, true) : [];
        foreach ($locations as $location) {
            DB::table('vehicle_locations')->insert([
                'vehicle_id' => $vehicleId,
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'address' => $location['address'],
                'geo' => DB::raw("POINT({$location['lng']},{$location['lat']})"),
            ]);
        }
    }

    public function refineEquipment(string $standardequipment): string
    {
        $parts = explode(',', $standardequipment);
        $parts = array_filter($parts, 'strlen');
        return implode('|', $parts);
    }

    private function addVehicleImage(int $vehicleid, string $imgurl, int $iorder): void
    {
        $pathParts = pathinfo($imgurl);
        if (empty($pathParts['extension'])) {
            return;
        }
        DB::table('vehicle_images')->insert([
            'filename' => $imgurl,
            'remote' => 1,
            'vehicle_id' => $vehicleid,
            'iorder' => $iorder,
        ]);
    }

    private function vehicleListUnlistStatus(array $vehicle = []): int
    {
        $newStatus = 1;
        if (empty($this->_DealerSettingObj) || empty($this->_DealerSettingObj->listing_rule)) {
            return $newStatus;
        }

        $listingRule = $this->_DealerSettingObj->listing_rule;
        $unlistRules = json_decode($this->_DealerSettingObj->unlist_rules ?? '{}', true);

        if ($listingRule === 'unlist_all') {
            $newStatus = 0;
        } elseif ($listingRule === 'unlist_by_ymm' && !empty($unlistRules)) {
            foreach ($unlistRules as $rule) {
                $yearOk = empty($rule['year']) || (isset($vehicle['year']) && (string) $vehicle['year'] === (string) $rule['year']);
                $makeOk = empty($rule['make']) || (isset($vehicle['make']) && strcasecmp(trim($vehicle['make']), trim($rule['make'])) === 0);
                $modelOk = empty($rule['model']) || (isset($vehicle['model']) && strcasecmp(trim($vehicle['model']), trim($rule['model'])) === 0);
                if ($yearOk && $makeOk && $modelOk) {
                    $newStatus = 0;
                    break;
                }
            }
        }
        return $newStatus;
    }

    public function pullFeed(string $url): array
    {
        $url .= ',vin,vehicleid,stock,miles,msrp,year,make,model,imagelist,extcolor,description,dealeraddress,dealercity,dealerstate,dealerzip,standardbody,sellingprice,intcolor,trim,doors,engliters,epacity,epahighway,options';
        $response = $this->sendHttpRequest($url);

        if ($response['status'] != 'true') {
            return ['status' => false, 'message' => $response['message'], 'result' => []];
        }
        return ['status' => true, 'message' => $response['message'], 'result' => []];
    }

    public function parseVehicleData(): array
    {
        $xml = new \XMLReader();
        $xml->XML(html_entity_decode($this->_vehicleFeeds));
        $header = [];
        $vehicles = [];
        while ($xml->read()) {
            if ($xml->name == 'F') {
                $index = 0;
                while ($xml->getAttribute('_' . $index)) {
                    $header['_' . $index] = $xml->getAttribute('_' . $index++);
                }
            }
            if ($xml->name == 'V') {
                $vehicle = [];
                $index = 0;
                while ($xml->getAttribute('_' . $index) != null || $xml->getAttribute('_' . $index) === '') {
                    $vehicle['_' . $index] = $xml->getAttribute('_' . $index++);
                }
                $vehicles[] = $vehicle;
            }
        }
        return [$header, $vehicles];
    }

    public function saveVehicleDepositRule(array $data): void
    {
        $exists = DB::table('deposit_rules')->where('vehicle_id', $data['vehicle_id'])->first();
        if (!empty($exists)) {
            return;
        }

        $tpl = $this->_depositTemplateObj;
        $dataToSave = [
            'user_id' => $data['user_id'],
            'vehicle_id' => $data['vehicle_id'],
            'title' => $data['vehicle_unique_id'],
            'deposit_amt' => $tpl->deposit_amt ?? 200,
            'deposit_event' => $tpl->deposit_event ?? 'P',
            'deposit_type' => $tpl->deposit_type ?? 'C',
            'charge_rent' => $tpl->charge_rent ?? 'S',
            'emf' => $tpl->emf ?? 0.5,
            'emf_insu' => $tpl->emf_insu ?? 0.15,
            'tax' => $tpl->tax ?? 0,
            'lateness_fee' => $tpl->lateness_fee ?? 5,
            'cancellation_fee' => $tpl->cancellation_fee ?? 10,
            'insurance_fee' => $tpl->insurance_fee ?? 0.25,
            'insurance_event' => $tpl->insurance_event ?? 'S',
            'initial_event' => $tpl->initial_event ?? 'P',
            'initial_fee' => $tpl->initial_fee ?? 0,
            'total_deposit_amt' => $tpl->total_deposit_amt ?? 0,
            'deposit_amt_opt' => $tpl->deposit_amt_opt ?? '',
            'initial_fee_opt' => $tpl->initial_fee_opt ?? '',
            'total_initial_fee' => $tpl->total_initial_fee ?? 0,
            'depreciation_rate' => $tpl->depreciation_rate ?? 0,
            'financing' => $tpl->financing ?? 0,
            'financing_type' => $tpl->financing_type ?? 0,
            'monthly_maintenance' => $tpl->monthly_maintenance ?? 0,
            'disposition_fee' => $tpl->disposition_fee ?? 0,
            'write_down_allocation' => $tpl->write_down_allocation ?? 0,
            'prepaid_initial_fee' => $tpl->prepaid_initial_fee ?? 0,
            'prepaid_initial_fee_data' => $tpl->prepaid_initial_fee_data ?? null,
            'program_length' => $tpl->program_length ?? 365,
            'capitalize_starting_fee' => $tpl->capitalize_starting_fee ?? null,
            'insurance_payer' => $tpl->insurance_payer ?? null,
            'return_fee' => $tpl->return_fee ?? null,
            'doc_fee' => $tpl->doc_fee ?? null,
        ];

        $incentives = json_decode($tpl->incentives ?? '{}', true) ?: [];
        foreach ($incentives as $incentive) {
            if (strcasecmp($data['make'], $incentive['make']) !== 0) {
                continue;
            }
            if (!in_array($data['model'], $incentive['model'])) {
                continue;
            }
            if (!empty($incentive['year']) && ($data['year'] ?? '') != $incentive['year']) {
                continue;
            }
            if (!empty($incentive['trim']) && ($data['trim'] ?? '') != $incentive['trim']) {
                continue;
            }
            $dataToSave['incentive'] = $incentive['amount'];
            break;
        }

        DB::table('deposit_rules')->insert($dataToSave);
    }

    public function sendHttpRequest(string $url): array
    {
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection, CURLOPT_TIMEOUT, 160);
        $response = curl_exec($connection);
        curl_close($connection);

        if (empty($response)) {
            return ['status' => 'false', 'message' => 'Response is blank', 'result' => ''];
        }

        $xml = new \XMLReader();
        $xml->XML($response);
        $status = 'false';
        $message = '';

        while ($xml->read()) {
            if ($xml->name == 'IsSuccess') {
                $status = $xml->readString();
                $xml->next();
            }
            if ($xml->name == 'ErrorMessage') {
                $message = $xml->readString();
                $xml->next();
            }
            if ($xml->name == 'CompressedVehicles') {
                $this->_vehicleFeeds = $xml->readInnerXML();
                $xml->next();
            }
        }

        return ['status' => $status, 'message' => $message, 'result' => []];
    }

    private function daysBetweenDates(string $date1, string $date2): int
    {
        $d1 = strtotime($date1);
        $d2 = strtotime($date2);
        return (int) abs(($d2 - $d1) / 86400);
    }
}
