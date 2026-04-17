<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportFeed
{
    private int $_filterSellingPriceFrom = 0;
    private int $_filterSellingPriceTo = 0;
    private array $_filterMake = [];
    private array $_filterModel = [];

    private ?object $_DealerSettingObj = null;

    public function process(): void
    {
        $runMin = date('i');

        if ($runMin == '00') {
            $savvyDealers = DB::table('savvy_dealers')
                ->where('status', 1)
                ->where(function ($q) {
                    $q->where('last_processed', '!=', date('Y-m-d'))
                      ->orWhereNull('last_processed');
                })
                ->limit(1)
                ->get();
        } else {
            $savvyDealers = DB::table('savvy_dealers')
                ->where('status', 1)
                ->where('run_now', 1)
                ->limit(1)
                ->get();
        }

        foreach ($savvyDealers as $savvyDealer) {
            $filters = json_decode($savvyDealer->filters, true) ?: [];
            $this->_filterSellingPriceFrom = !empty($filters['sellingprice']['from']) ? (int) $filters['sellingprice']['from'] : 0;
            $this->_filterSellingPriceTo = !empty($filters['sellingprice']['to']) ? (int) $filters['sellingprice']['to'] : 0;
            $this->_filterMake = !empty($filters['make']) ? array_map('trim', explode(',', strtolower($filters['make']))) : [];
            $this->_filterModel = !empty($filters['model']) ? array_map('trim', explode(',', strtolower($filters['model']))) : [];

            $seenVehicleIDs = [];

            DB::table('savvy_dealers')
                ->where('id', $savvyDealer->id)
                ->update(['last_processed' => date('Y-m-d'), 'run_now' => 0]);

            $this->_DealerSettingObj = DB::table('cs_settings')
                ->leftJoin('users', 'users.id', '=', 'cs_settings.user_id')
                ->where('cs_settings.user_id', $savvyDealer->user_id)
                ->select('cs_settings.*', 'users.address_lat', 'users.address_lng')
                ->first();

            $url = $savvyDealer->search_url;
            $vehicles = $this->sendHttpRequest($url);

            foreach ($vehicles as $vehicle) {
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
                    DB::table('vehicles')->where('id', $vehicle->id)->update(['status' => 0, 'trash' => 1]);
                } else {
                    DB::table('vehicles')->where('id', $vehicle->id)->delete();
                }
            }
        }
    }

    private function saveVehicle(array $record, int $userId): int
    {
        $alreadyExists = DB::table('vehicles')
            ->where('vin_no', $record['vin'])
            ->first();

        if (!empty($alreadyExists) && $alreadyExists->booked == 1) {
            return $alreadyExists->id;
        }

        $isUpdate = !empty($alreadyExists);
        $dataToSave = [];

        if (!$isUpdate) {
            $dataToSave['vehicle_unique_id'] = '';
        }

        $dataToSave['from_feed'] = 1;
        $dataToSave['user_id'] = $userId;
        $dataToSave['make'] = $record['make'];
        $dataToSave['model'] = $record['model'];
        $dataToSave['year'] = $record['year'];
        $dataToSave['color'] = $record['extcolor'];
        $dataToSave['vin_no'] = $record['vin'];
        $dataToSave['details'] = $record['description'];
        $dataToSave['address'] = ($record['dealeraddress'] ?? '') . ' ' . ($record['dealercity'] ?? '') . ' ' . ($record['dealerstate'] ?? '') . ' ' . ($record['dealerzip'] ?? '');
        $dataToSave['lat'] = $this->_DealerSettingObj->address_lat ?? '';
        $dataToSave['lng'] = $this->_DealerSettingObj->address_lng ?? '';
        $dataToSave['program'] = $this->_DealerSettingObj->vehicle_program ?? 2;
        $dataToSave['financing'] = $this->_DealerSettingObj->vehicle_financing ?? 2;
        $dataToSave['cab_type'] = !empty($record['standardbody']) ? $record['standardbody'] : '';
        $dataToSave['msrp'] = $record['sellingprice'] > 1 ? $record['sellingprice'] : 1500;
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
        $dataToSave['vehicleCostInclRecon'] = $record['sellingprice'] > 1000 ? $record['sellingprice'] - 1000 : 0;
        $dataToSave['plate_number'] = null;
        $dataToSave['fare_type'] = 'D';

        if (isset($record['carfaxhistoryreporturl']) && !empty($record['carfaxhistoryreporturl'])) {
            $dataToSave['accudata'] = json_encode(['carfax' => $record['carfaxhistoryreporturl']]);
        }

        $dataToSave['transmition_type'] = 'A';

        $vehicleName = (!empty($dataToSave['year']) ? substr($dataToSave['year'], -2) . '-' : '')
            . (!empty($dataToSave['make']) ? str_replace(' ', '_', $dataToSave['make']) . '-' : '')
            . (!empty($dataToSave['model']) ? str_replace(' ', '_', $dataToSave['model']) : '')
            . (!empty($dataToSave['vin_no']) ? '-' . substr($dataToSave['vin_no'], -6) : '');
        $dataToSave['vehicle_name'] = $vehicleName;

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
        } catch (\Exception $e) {
            Log::error('ImportFeed saveVehicle error', ['error' => $e->getMessage()]);
        }

        return $isUpdate ? $alreadyExists->id : ($vehicleId ?? 0);
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

    public function sendHttpRequest(string $url): array
    {
        $connection = curl_init();
        curl_setopt($connection, CURLOPT_URL, $url);
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($connection, CURLOPT_TIMEOUT, 160);
        $response = curl_exec($connection);
        curl_close($connection);

        return json_decode($response, true) ?? [];
    }
}
