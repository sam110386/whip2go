<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccutradeController extends LegacyApiBaseController
{
    // Action stubs (CakePHP: app/Controller/AccutradeController.php)
    public function getAuthToken(Request $request)
    {
        $pepper = 'driveitaway';
        $salt = 'DYhG93b0qyJfIxfs2guVoUubWwvniR2G0FgaC9mi';

        $raw = (string) $request->getContent();
        $dataValues = [];
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $dataValues = $decoded;
            }
        }
        if (empty($dataValues)) {
            $dataValues = $request->input();
        }

        $username = isset($dataValues['username']) ? trim((string) $dataValues['username']) : '';
        $password = isset($dataValues['password']) ? (string) $dataValues['password'] : '';

        if ($username === '' || $password === '') {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid inputs',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $user = DB::table('users')
            ->select(['id', 'password'])
            ->where('username', $username)
            ->where('is_admin', 1)
            ->first();

        if (empty($user)) {
            return response()->json([
                'message' => 'Sorry, you are not registered user.',
                'status' => 0,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $hash = sha1($salt . trim($password));
        $storedPassword = (string) ($user->password ?? '');
        if ($storedPassword !== $hash) {
            return response()->json([
                'status' => 0,
                'message' => 'Sorry, your password does not match, please try again',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $token = $this->getUniqueId(8);

        DB::table('users')
            ->where('id', (int) $user->id)
            ->update(['token' => $token]);

        $legacyRoot = realpath(base_path('..'));
        require_once $legacyRoot . '/app/Vendor/JWT/JWT/JWT.php';

        $now = time();
        $jwtPayload = [
            'iss' => 'https://www.whip2go.com',
            'aud' => 'https://www.whip2go.com',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + 6 * 3600,
            'user' => [
                'userId' => (int) $user->id,
                'token' => $token,
            ],
        ];

        $authToken = \JWT::encode($jwtPayload, $pepper);

        return response()->json([
            'status' => 1,
            'message' => '',
            'auth_token' => $authToken,
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function refreshToken(Request $request)
    {
        $pepper = 'driveitaway';

        $jwtToken = $this->getJwtFromAuthorizationHeader($request);
        if ($jwtToken === '') {
            return response()->json([
                'status' => 0,
                'message' => 'Token is required',
                'auth_token' => '',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $legacyRoot = realpath(base_path('..'));
        require_once $legacyRoot . '/app/Vendor/JWT/JWT/JWT.php';

        try {
            \JWT::decode($jwtToken, $pepper, ['HS256']);
            return response()->json([
                'status' => 1,
                'message' => 'Token is valid',
                'auth_token' => $jwtToken,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        } catch (\ExpiredException $e) {
            \JWT::$leeway = 720000;
            $decoded = (array) \JWT::decode($jwtToken, $pepper, ['HS256']);
            $decoded['iat'] = time();
            $decoded['exp'] = time() + 72 * 3600;

            $newToken = \JWT::encode($decoded, $pepper);
            return response()->json([
                'status' => 1,
                'message' => 'Token refreshed',
                'auth_token' => $newToken,
            ])->header('Content-Type', 'application/json; charset=utf-8');
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage(),
                'auth_token' => '',
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }
    }

    public function addVehicles(Request $request)
    {
        $userObj = $this->requireAccutradeJwtUser($request);
        if (!is_array($userObj)) {
            return $userObj;
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = [];
        }

        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];
        if (empty($dataValues) || empty($userObj['id']) || !isset($dataValues[0])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $return = ['status' => 1, 'message' => 'Vehicle data added', 'result' => []];
        foreach ($dataValues as $row) {
            if (!is_array($row) || empty($row['vin'])) {
                continue;
            }
            $this->saveAccutradeVehicleRecord($row);
            $return['result'][] = (string) $row['vin'];
        }

        return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function addVehicle(Request $request)
    {
        $userObj = $this->requireAccutradeJwtUser($request);
        if (!is_array($userObj)) {
            return $userObj;
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = [];
        }

        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];
        if (empty($dataValues['appraisal']) || empty($userObj['id']) || empty($dataValues['appraisal']['vin'])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $appraisal = $dataValues['appraisal'];
        if (!is_array($appraisal)) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $dealerId = $this->getAccutradeDealerId($appraisal);
        if ($dealerId < 0) {
            return response()->json([
                'status' => 0,
                'message' => 'Could not find dealer',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $this->saveAccutradeVehicleRecord($appraisal);

        return response()->json([
            'status' => 1,
            'message' => 'Vehicle data added',
            'result' => [(string) $appraisal['vin']],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function removeVehicle(Request $request)
    {
        $userObj = $this->requireAccutradeJwtUser($request);
        if (!is_array($userObj)) {
            return $userObj;
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = [];
        }

        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];
        if (empty($dataValues['appraisal']) || empty($userObj['id']) || empty($dataValues['appraisal']['vin'])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $vin = (string) $dataValues['appraisal']['vin'];
        $vehicle = DB::table('vehicles')->select(['id'])->where('vin_no', $vin)->first();
        if (!empty($vehicle) && Schema::hasColumn('vehicles', 'trash')) {
            DB::table('vehicles')->where('id', (int) $vehicle->id)->update(['trash' => 1]);
            return response()->json([
                'status' => 1,
                'message' => 'Vehicle record removed successfully',
                'result' => [],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        return response()->json([
            'status' => 0,
            'message' => 'Sorry, Vehicle vin not found',
            'result' => [],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function removeVehicles(Request $request)
    {
        $userObj = $this->requireAccutradeJwtUser($request);
        if (!is_array($userObj)) {
            return $userObj;
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = [];
        }

        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];
        if (empty($dataValues) || empty($userObj['id']) || !isset($dataValues[0])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $return = ['status' => 1, 'message' => 'Vehicles removed', 'result' => []];
        foreach ($dataValues as $row) {
            if (!is_array($row) || empty($row['vin'])) {
                continue;
            }
            $vehicle = DB::table('vehicles')->select(['id'])->where('vin_no', (string) $row['vin'])->first();
            if (!empty($vehicle) && Schema::hasColumn('vehicles', 'trash')) {
                DB::table('vehicles')->where('id', (int) $vehicle->id)->update(['trash' => 1]);
                $return['result'][] = (string) $row['vin'];
            }
        }

        return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function checkVehicleStatus(Request $request)
    {
        $userObj = $this->requireAccutradeJwtUser($request);
        if (!is_array($userObj)) {
            return $userObj;
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = [];
        }

        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];
        if (empty($dataValues) || empty($userObj['id']) || empty($dataValues['vin'])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $vin = (string) $dataValues['vin'];
        $vehicle = DB::table('vehicles')
            ->where('vin_no', $vin)
            ->first();

        if (empty($vehicle)) {
            return response()->json([
                'status' => 1,
                'message' => 'Success',
                'result' => ['status' => 'Unlisted'],
            ])->header('Content-Type', 'application/json; charset=utf-8');
        }

        $passtime = isset($vehicle->passtime_status) ? (int) $vehicle->passtime_status : null;
        $booked = isset($vehicle->booked) ? (int) $vehicle->booked : 0;
        $statusCode = isset($vehicle->status) ? (string) $vehicle->status : '1';

        if ($passtime === 0) {
            $statusLabel = 'Starter Disabled';
        } elseif ($passtime === 1 && $booked === 1) {
            $statusLabel = 'Booked';
        } else {
            $map = $this->vehicleStatusMap();
            $statusLabel = $map[$statusCode] ?? 'Active';
        }

        return response()->json([
            'status' => 1,
            'message' => 'Success',
            'result' => ['status' => $statusLabel],
        ])->header('Content-Type', 'application/json; charset=utf-8');
    }

    public function checkDealerExists(Request $request)
    {
        $userObj = $this->requireAccutradeJwtUser($request);
        if (!is_array($userObj)) {
            return $userObj;
        }

        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw, true);
        if (!is_array($dataValues)) {
            $dataValues = [];
        }

        $return = ['status' => 0, 'message' => 'Invalid input body', 'result' => []];
        if (empty($dataValues) || empty($userObj['id']) || empty($dataValues['phone'])) {
            return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
        }

        $phone = preg_replace('/[^0-9]/', '', (string) $dataValues['phone']);
        $phone = (string) $phone;
        if ($phone !== '') {
            $dealer = DB::table('users')
                ->select(['id', 'is_dealer'])
                ->where('username', $phone)
                ->first();

            if (!empty($dealer)) {
                if (!empty($dealer->is_dealer)) {
                    $return = ['status' => 1, 'message' => 'Dealer found', 'result' => ['dealer_id' => (int) $dealer->id]];
                } else {
                    $return['message'] = "Sorry, Phone is registered but user didnt complete profile till dealer account complete";
                }
            } else {
                $return['message'] = 'Sorry, dealer is not registered yet';
            }
        }

        return response()->json($return)->header('Content-Type', 'application/json; charset=utf-8');
    }

    private function getUniqueId(int $length = 32, string $pool = ''): string
    {
        if ($pool === '') {
            $pool = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        }
        mt_srand((double) microtime() * 1000000);
        $uniqueId = '';
        for ($index = 0; $index < $length; $index++) {
            $uniqueId .= substr($pool, (mt_rand() % strlen($pool)), 1);
        }
        return $uniqueId;
    }

    private function getJwtFromAuthorizationHeader(Request $request): string
    {
        $authorization = (string) ($request->header('Authorization') ?? '');
        if ($authorization === '') {
            $authorization = (string) ($request->header('authorization') ?? '');
        }
        return trim(str_replace('Basic', '', $authorization));
    }

    private function requireAccutradeJwtUser(Request $request)
    {
        $pepper = 'driveitaway';
        $jwtToken = $this->getJwtFromAuthorizationHeader($request);

        $legacyRoot = realpath(base_path('..'));
        require_once $legacyRoot . '/app/Vendor/JWT/JWT/JWT.php';

        try {
            $decoded = \JWT::decode($jwtToken, $pepper, ['HS256']);
            $userId = isset($decoded->user->userId) ? (int) $decoded->user->userId : 0;
            $userToken = isset($decoded->user->token) ? (string) $decoded->user->token : '';
            if ($userId <= 0 || $userToken === '') {
                throw new \Exception('Please login again');
            }

            $user = DB::table('users')
                ->where('id', $userId)
                ->where('token', $userToken)
                ->where('status', 1)
                ->where('is_admin', 1)
                ->first();

            if (empty($user)) {
                throw new \Exception('Please login again');
            }

            return (array) $user;
        } catch (\Exception $e) {
            return response()
                ->json(['status' => 0, 'message' => 'Please log back in'])
                ->setStatusCode(400)
                ->header('Content-Type', 'application/json; charset=utf-8');
        }
    }

    private function getAccutradeDealerId(array $dealerInfo): int
    {
        $phone = preg_replace('/[^0-9]/', '', (string) ($dealerInfo['dealer_contact_phone'] ?? ''));
        $phone = (string) $phone;
        if ($phone === '') {
            return -1;
        }

        $dealer = DB::table('users')
            ->select(['id', 'address_lat', 'address_lng', 'is_dealer'])
            ->where('username', $phone)
            ->where('is_dealer', 1)
            ->first();

        if (empty($dealer)) {
            $this->saveMarketplacePendingDealer($phone, $dealerInfo);
            return -1;
        }

        $this->dealerObj = (array) $dealer;
        return (int) $dealer->id;
    }

    private array $dealerObj = [];

    private function saveMarketplacePendingDealer(string $phone, array $dealerInfo): void
    {
        if (!Schema::hasTable('marketplace_pdealers')) {
            return;
        }

        $name = (string) ($dealerInfo['dealer_name'] ?? $dealerInfo['dealer'] ?? 'Unknown');
        $address = (string) ($dealerInfo['dealer_address'] ?? $dealerInfo['address'] ?? '');
        if ($address === '') {
            $address = (string) ($dealerInfo['dealer_street'] ?? '');
        }

        try {
            DB::table('marketplace_pdealers')->updateOrInsert(
                ['phone' => $phone],
                [
                    'name' => $name !== '' ? $name : 'Unknown',
                    'address' => $address !== '' ? $address : 'Unknown',
                    'status' => 0,
                ]
            );
        } catch (\Exception $e) {
        }
    }

    private function getDealerProgram(int $userId): array
    {
        if (!Schema::hasTable('cs_settings')) {
            return [2, 4];
        }

        $row = DB::table('cs_settings')->where('user_id', $userId)->first();
        $program = !empty($row) && isset($row->vehicle_program) ? (int) $row->vehicle_program : 2;
        $financing = !empty($row) && isset($row->vehicle_financing) ? (int) $row->vehicle_financing : 4;
        return [$program, $financing];
    }

    private function saveAccutradeVehicleRecord(array $record): void
    {
        $vin = (string) ($record['vin'] ?? '');
        if ($vin === '') {
            return;
        }

        $existing = DB::table('vehicles')->where('vin_no', $vin)->first();
        if (!empty($existing)) {
            if (Schema::hasColumn('vehicles', 'accudata')) {
                $accu = json_encode([
                    'market' => $record['market'] ?? null,
                    'offerPrice' => $record['offerPrice'] ?? null,
                    'trade' => $record['trade'] ?? null,
                    'retail' => $record['retail'] ?? null,
                ]);
                DB::table('vehicles')->where('id', (int) $existing->id)->update(['accudata' => $accu]);
            }
            return;
        }

        $dealerId = $this->getAccutradeDealerId($record);
        if ($dealerId < 0) {
            return;
        }

        $msrp = (int) ($record['offerPrice'] ?? 0);
        $market = (int) ($record['market'] ?? 0);
        if ($msrp <= 1 && $market > 0) {
            $msrp = $market - 1000;
        }

        $color = '';
        if (!empty($record['color']) && is_array($record['color'])) {
            $color = (string) ($record['color']['color'] ?? '');
        }

        $intColor = '';
        if (!empty($record['intColor']) && is_array($record['intColor'])) {
            $intColor = (string) ($record['intColor']['color'] ?? '');
        }

        [$program, $financing] = $this->getDealerProgram($dealerId);

        $candidate = [
            'user_id' => $dealerId,
            'make' => $record['make'] ?? null,
            'model' => $record['model'] ?? null,
            'year' => $record['year'] ?? null,
            'color' => $color !== '' ? $color : null,
            'vin_no' => $vin,
            'stock_no' => $record['id'] ?? null,
            'interior_color' => $intColor !== '' ? $intColor : null,
            'trim' => $record['mmrTrim'] ?? null,
            'odometer' => ((int) ($record['odometer'] ?? 0) > 1) ? (int) $record['odometer'] : 0,
            'msrp' => $msrp,
            'status' => 1,
            'address' => $record['dealer_address'] ?? null,
            'lat' => !empty($this->dealerObj) ? ($this->dealerObj['address_lat'] ?? null) : null,
            'lng' => !empty($this->dealerObj) ? ($this->dealerObj['address_lng'] ?? null) : null,
            'engine' => $record['engine'] ?? null,
            'program' => $program,
            'financing' => $financing,
            'vehicle_unique_id' => '',
            'accudata' => json_encode([
                'market' => $record['market'] ?? null,
                'offerPrice' => $record['offerPrice'] ?? null,
                'trade' => $record['trade'] ?? null,
                'retail' => $record['retail'] ?? null,
            ]),
        ];

        $vehicleName =
            (!empty($candidate['year']) ? substr((string) $candidate['year'], -2) . '-' : '') .
            (!empty($candidate['make']) ? str_replace(' ', '_', (string) $candidate['make']) . '-' : '') .
            (!empty($candidate['model']) ? str_replace(' ', '_', (string) $candidate['model']) : '') .
            (!empty($candidate['vin_no']) ? '-' . substr((string) $candidate['vin_no'], -6) : '');
        $candidate['vehicle_name'] = $vehicleName;

        $insert = [];
        foreach ($candidate as $col => $val) {
            if ($val === null) {
                continue;
            }
            if (Schema::hasColumn('vehicles', $col)) {
                $insert[$col] = $val;
            }
        }

        if (empty($insert)) {
            return;
        }

        $newId = DB::table('vehicles')->insertGetId($insert);

        if (Schema::hasColumn('vehicles', 'vehicle_unique_id')) {
            $uniqueNo = ($newId < 999) ? ('1' . sprintf('%04d', $newId)) : (string) $newId;
            DB::table('vehicles')->where('id', (int) $newId)->update(['vehicle_unique_id' => $uniqueNo]);
        }
    }

    private function vehicleStatusMap(): array
    {
        return [
            '0' => 'Unlisted',
            '1' => 'Active',
            '4' => 'Inactive',
            '2' => 'In Body Shop',
            '3' => 'In Maintenance',
            '5' => 'Maintenance Issues',
            '6' => 'Booked',
            '8' => 'Starter Disabled',
            '9' => 'Starter Enabled',
            '10' => 'Waiting For Review',
            '11' => 'Deleted',
            '12' => 'Undo Deleted',
        ];
    }
}
