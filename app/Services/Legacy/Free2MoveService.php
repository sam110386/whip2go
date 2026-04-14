<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Free2MoveService
{
    public static function fetchDynamicFare(int $vehicleid, bool $force = false): array
    {
        $vehicleData = DB::table('vehicles')
            ->leftJoin('deposit_rules as DepositRule', 'DepositRule.vehicle_id', '=', 'vehicles.id')
            ->where('vehicles.id', $vehicleid)
            ->select(
                'vehicles.id', 'vehicles.day_rent', 'vehicles.rent_opt',
                'vehicles.model', 'vehicles.make', 'vehicles.year',
                'vehicles.homenet_modelnumber', 'vehicles.msrp', 'vehicles.homenet_msrp',
                'vehicles.vehicleCostInclRecon',
                'DepositRule.doc_fee', 'DepositRule.id as deposit_rule_id', 'DepositRule.incentive'
            )
            ->first();

        $defaultReturn = [
            'day_rent' => 0,
            'rent_opt' => [],
            'rent_opt_des' => [
                '0 to 1 months $0 per day',
                '20% Down Payment Built w/ Approved Loan *** Continue Renting *** Return at Anytime',
            ],
        ];

        if (empty($vehicleData)) {
            return $defaultReturn;
        }

        if (!$force && !empty($vehicleData->day_rent)) {
            $rentOpt = !empty($vehicleData->rent_opt) ? json_decode($vehicleData->rent_opt, true) : [];
            if (!empty($rentOpt) && count($rentOpt) == 2) {
                $keys = array_keys($rentOpt);
                $tier1Obj = $rentOpt[$keys[0]];
                $tier2Obj = $rentOpt[$keys[1]];
                return [
                    'day_rent' => $vehicleData->day_rent,
                    'rent_opt' => [
                        ['after_day' => $tier1Obj['after_day'], 'amount' => $tier1Obj['amount']],
                        ['after_day' => $tier2Obj['after_day'], 'amount' => $tier2Obj['amount']],
                    ],
                    'rent_opt_des' => [
                        '0 to ' . sprintf('%d', $tier1Obj['after_day'] / 30) . ' months $' . $vehicleData->day_rent . ' per day',
                        '20% Down Payment Built w/ Approved Loan *** Continue Renting *** Return at Anytime',
                    ],
                ];
            }
            return [
                'day_rent' => $vehicleData->day_rent,
                'rent_opt' => [],
                'rent_opt_des' => [
                    '0 to 1 months $' . $vehicleData->day_rent . ' per day',
                    '20% Down Payment Built w/ Approved Loan *** Continue Renting *** Return at Anytime',
                ],
            ];
        }

        $requestBody = [
            'period' => '36',
            'make' => $vehicleData->make,
            'model' => $vehicleData->model,
            'year' => $vehicleData->year,
            'doc_fee' => $vehicleData->doc_fee,
            'ref_mode' => $vehicleData->homenet_modelnumber,
            'msrp' => $vehicleData->homenet_msrp,
            'invoice' => $vehicleData->vehicleCostInclRecon,
            'discount_price' => sprintf('%0.2f', ($vehicleData->vehicleCostInclRecon - ($vehicleData->incentive ?? 0) + ($vehicleData->doc_fee ?? 0))),
            'destination_fee' => 1500,
            'vehicle_id' => $vehicleData->id,
        ];

        if ($requestBody['msrp'] == 0 || $requestBody['invoice'] == 0 || $requestBody['discount_price'] == 0) {
            DB::table('vehicles')->where('id', $vehicleData->id)->update(['day_rent' => 0, 'status' => 0]);
            return array_merge($defaultReturn, ['error' => 'Missing pricing data']);
        }

        self::saveToQueue($requestBody);

        return array_merge($defaultReturn, ['error' => 'Request is accepted by Free2Move Api']);
    }

    private static function saveToQueue(array $requestBody): void
    {
        DB::table('free2move_queue')->insert([
            'created' => date('Y-m-d H:i:s'),
            'data' => json_encode($requestBody),
            'status' => 0,
        ]);
    }

    public function callApi(array $requestBody): array
    {
        $url = config('legacy.Free2Move.apiHost', '');
        $header = [
            'Content-Type: application/json',
            'Charset=UTF-8',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Token: ' . config('legacy.Free2Move.apiToken', ''),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        Log::info('Free2Move API call', ['request' => $requestBody, 'response' => $response]);

        return json_decode($response, true) ?? [];
    }

    public function callAgreementApi(array $requestBody): array
    {
        $url = config('legacy.Free2Move.apiAgreementHost', '');
        $header = [
            'Content-Type: application/json',
            'Charset=UTF-8',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Token: ' . config('legacy.Free2Move.apiToken', ''),
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        Log::info('Free2Move Agreement API', ['request' => $requestBody, 'response' => $response]);

        return json_decode($response, true) ?? [];
    }
}
