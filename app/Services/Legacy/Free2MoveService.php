<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\Free2MoveQueue;


class Free2MoveService
{
    public static function fetchDynamicFare(int $vehicleid, bool $force = false): array
    {
        $vehicleData = Vehicle::select([
            'id',
            'day_rent',
            'rent_opt',
            'model',
            'make',
            'year',
            'homenet_modelnumber',
            'msrp',
            'homenet_msrp',
            'vehicleCostInclRecon',
        ])
            ->with('depositRule:id,vehicle_id,doc_fee,incentive')
            ->where('id', $vehicleid)
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
            'doc_fee' => $vehicleData?->depositRule?->doc_fee,
            'ref_mode' => $vehicleData->homenet_modelnumber,
            'msrp' => $vehicleData->homenet_msrp,
            'invoice' => $vehicleData->vehicleCostInclRecon,
            'discount_price' => sprintf('%0.2f', ($vehicleData->vehicleCostInclRecon - ($vehicleData?->depositRule?->incentive ?? 0) + ($vehicleData?->depositRule?->doc_fee ?? 0))),
            'destination_fee' => 1500,
            'vehicle_id' => $vehicleData->id,
        ];

        if (
            $requestBody['msrp'] == 0 ||
            $requestBody['invoice'] == 0 ||
            $requestBody['discount_price'] == 0
        ) {
            Vehicle::where('id', $vehicleData->id)
                ->update([
                    'day_rent' => 0,
                    'status' => 0
                ]);

            return array_merge($defaultReturn, ['error' => 'Missing pricing data']);
        }

        self::_saveToQeueue($requestBody);

        return array_merge($defaultReturn, ['error' => 'Request is accepted by Free2Move Api']);
    }

    private static function _saveToQeueue(array $requestBody): void
    {
        Free2MoveQueue::create([
            'created' => now(),
            'data' => json_encode($requestBody),
        ]);
    }

    public static function _callApi(array $requestBody): array
    {
        $url = config('legacy.Free2Move.apiHost');
        return self::sendRequest($url, $requestBody);
    }

    public static function _callAgreementApi(array $requestBody): array
    {

        $url = config('legacy.Free2Move.apiAgreementHost', '');
        return self::sendRequest($url, $requestBody);
    }
    private static function sendRequest(string $url, array $requestBody): ?array
    {
        $startTime = microtime(true);
        $token = config('legacy.Free2Move.apiToken', '');

        $logger = Log::channel('free2move');
        $logger->info('=Request starting here=:');
        $logger->info('=Request payload is =:', ['url' => $url, 'body' => $requestBody]);

        try {
            $response = Http::withHeaders([
                'Charset' => 'UTF-8',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'Token' => $token,
            ])
                ->withoutVerifying()
                ->post($url, $requestBody);

            $endTime = microtime(true);

            $logger->info('=Response received as =:', ['response' => $response->body()]);
            $logger->info("=Start time was =: {$startTime} === end time was = {$endTime}");

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Free2Move API Error: ' . $e->getMessage());
            return null;
        }
    }
}
