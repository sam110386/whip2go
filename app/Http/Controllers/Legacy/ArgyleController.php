<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Legacy\User as LegacyUser;
use App\Models\Legacy\ArgyleUser as LegacyArgyleUser;
use App\Models\Legacy\ArgyleUserRecord as LegacyArgyleUserRecord;
use App\Models\Legacy\VehicleReservation as LegacyVehicleReservation;
use App\Models\Legacy\ArgyleActivity as LegacyArgyleActivity;

class ArgyleController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index(Request $request, $userid)
    {
        $decodedUserId = base64_decode($userid, true);
        if ($decodedUserId === false) {
            return redirect('/argyle/error');
        }

        $ouruser = LegacyUser::query()->whereKey((int)$decodedUserId)->count();
        if (!$ouruser) {
            return redirect('/argyle/error');
        }

        $argyleObj = LegacyArgyleUser::query()->where('user_id', (int)$decodedUserId)->first();

        $uberlyftPartners = ['lyft', 'grubhub', 'doordash', 'shipt', 'postmates', 'uber'];
        $incomedataPartners = [];
        $token = $argyleObj ? $argyleObj->auth_token : null;

        return view('argyle.index', [
            'userid' => $userid,
            'token' => $token,
            'uberlyftPartners' => $uberlyftPartners,
            'incomedataPartners' => $incomedataPartners,
        ]);
    }

    public function linkincome(Request $request, $userid)
    {
        return redirect('/argyle/index/' . $userid);
    }

    public function uber(Request $request, $userid)
    {
        return view('argyle.uber', ['userid' => $userid]);
    }

    public function lyft(Request $request, $userid)
    {
        return view('argyle.lyft', ['userid' => $userid]);
    }

    public function error(Request $request)
    {
        return view('argyle.error');
    }

    public function success(Request $request)
    {
        return view('argyle.success');
    }

    public function saveUser(Request $request)
    {
        $return = ["status" => false, "msg" => "Sorry, something went wrong, please try again"];
        if (!$request->input('isAjax')) {
            $return['msg'] = "sorry wrong page";
            return response()->json($return);
        }

        $userEncoded = $request->input('user', '');
        $user = $userEncoded !== '' ? base64_decode($userEncoded) : "";
        $accountId = trim((string)$request->input('accountId'));
        $userId = trim((string)$request->input('userId'));
        $account = trim((string)$request->input('account'));
        $income = filter_var($request->input('income'), FILTER_VALIDATE_BOOLEAN);

        $userObj = LegacyUser::query()->select(['id', 'uberlyft_verified', 'uber_lyft'])->find((int)$user);
        if (empty($userObj)) {
            return response()->json($return);
        }

        $exists = LegacyArgyleUser::query()
            ->select(['id', 'income'])
            ->where('user_id', (int)$user)
            ->first();

        if (empty($exists)) {
            $return['msg'] = "sorry wrong page";
            return response()->json($return);
        }

        try {
            LegacyArgyleUserRecord::query()->create([
                "user_id" => (int)$user,
                "account_id" => $accountId,
                "account" => $account,
                "argyle_user_id" => $exists->id
            ]);
        } catch (\Exception $e) {
            // CakePHP ignored this error intentionally.
        }

        if ($income) {
            $exists->update(['income' => 1]);
        }
        
        LegacyUser::query()->whereKey((int)$user)->update([
            "uberlyft_verified" => 1,
            "uber_lyft" => 1
        ]);
        
        if ($userObj->uber_lyft == 0 || $income) {
            // Custom model logic replication 
            if (method_exists(LegacyVehicleReservation::class, 'updatePendingBooking')) {
                $vRes = new LegacyVehicleReservation();
                $vRes->updatePendingBooking((int)$user, 1);
            }
        }

        return response()->json(["status" => true, "msg" => "You are successfully connected now"]);
    }

    public function saveToken(Request $request)
    {
        $return = ["status" => false, "msg" => "Sorry, something went wrong, please try again"];
        if (!$request->input('isAjax')) {
            $return['msg'] = "sorry wrong page";
            return response()->json($return);
        }

        $userEncoded = $request->input('user', '');
        $user = $userEncoded !== '' ? base64_decode($userEncoded) : "";
        $token = $request->input('token');
        $userId = $request->input('userId');

        $userObj = LegacyUser::query()->select(['id', 'uberlyft_verified', 'uber_lyft'])->find((int)$user);
        if (empty($userObj)) {
            return response()->json($return);
        }

        $exists = LegacyArgyleUser::query()->where('user_id', (int)$user)->first();
        if ($exists) {
            $exists->update([
                "auth_token" => $token,
                "argyle_user_id" => $userId
            ]);
        } else {
            LegacyArgyleUser::query()->create([
                "user_id" => (int)$user,
                "auth_token" => $token,
                "argyle_user_id" => $userId
            ]);
        }

        return response()->json(["status" => true, "msg" => "You are succcessfully connected now"]);
    }

    public function refreshToken(Request $request)
    {
        $return = ["status" => false, "msg" => "Sorry, something went wrong, please try again"];
        if (!$request->input('isAjax')) {
            $return['msg'] = "sorry wrong page";
            return response()->json($return);
        }

        $userEncoded = $request->input('user', '');
        $user = $userEncoded !== '' ? base64_decode($userEncoded) : "";
        $exists = LegacyArgyleUser::query()->where('user_id', (int)$user)->first();

        if (empty($user) || empty($exists) || empty($exists->argyle_user_id)) {
            return response()->json($return);
        }

        $requestBody = ["user" => $exists->argyle_user_id];
        $resp = $this->sendHttpRequest($requestBody);

        if (empty($resp) || empty($resp['access'])) {
            return response()->json($return);
        }

        $token = $resp['access'];
        LegacyArgyleUser::query()->whereKey((int)$exists->id)->update([
            "auth_token" => $token
        ]);

        return response()->json(["status" => true, "msg" => "You are succcessfully connected now", "token" => $token]);
    }

    private function sendHttpRequest(array $requestBody)
    {
        $clientId = config('services.argyle.client_id', config('argyle.client_id', ''));
        $clientSecret = config('services.argyle.client_secret', config('argyle.client_secret', ''));
        $apiHost = config('services.argyle.api_host', config('argyle.apiHost', ''));

        $url = rtrim($apiHost, '/') . '/user-tokens';

        $response = Http::withHeaders([
            'Charset' => 'UTF-8',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache'
        ])
        ->withBasicAuth($clientId, $clientSecret)
        ->withoutVerifying()
        ->post($url, $requestBody);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    public function webhook(Request $request)
    {
        $postData = $request->getContent();
        if (empty($postData)) {
            return response("Sorry, wrong effort!!");
        }

        Log::build(['driver' => 'single', 'path' => storage_path('logs/argyle_' . date('Y-m-d') . '.log')])
            ->info('Argyle Webhook', ['data' => $postData]);

        $decodedData = json_decode($postData, true);
        if (isset($decodedData['event']) && $decodedData['event'] === 'activities.updated') {
            if (method_exists(LegacyArgyleActivity::class, 'processWebhookData')) {
                $activityModel = new LegacyArgyleActivity();
                $activityModel->processWebhookData($decodedData['data'] ?? []);
            } else {
                Log::warning("LegacyArgyleActivity::processWebhookData not found.");
            }
        }
        
        return response('finished');
    }
}
