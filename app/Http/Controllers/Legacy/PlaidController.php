<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\PlaidUser;
use App\Models\Legacy\User;
use App\Models\Legacy\UserIncome;
use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\UserLicenseDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlaidController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    // ─── index (Plaid Link token flow) ────────────────────────────────────────
    public function index(Request $request, $userid = '')
    {
        $session = $request->session();

        if (!empty($userid)) {
            $session->forget('plaidS');
        }

        $link_token   = '';
        $user_token   = '';
        $oauth_state_id = '';

        if (!$session->has('plaidS')) {
            $user = !empty($userid) ? base64_decode($userid) : '';

            $userObj = User::from('users as User')
                ->leftJoin('user_license_details as UserLicenseDetail', 'UserLicenseDetail.user_id', '=', 'User.id')
                ->where('User.id', $user)
                ->select([
                    'User.id', 'User.first_name', 'User.last_name', 'User.email', 'User.contact_number',
                    'User.dob', 'User.address', 'User.city', 'User.state', 'User.zip',
                    'UserLicenseDetail.givenName', 'UserLicenseDetail.lastName', 'UserLicenseDetail.dateOfBirth',
                    'UserLicenseDetail.addressStreet', 'UserLicenseDetail.addressCity',
                    'UserLicenseDetail.addressState', 'UserLicenseDetail.addressPostalCode',
                ])
                ->first();

            if (empty($userObj)) {
                abort(403, 'oooo, wrong attempt');
            }

            $exists = PlaidUser::where('user_id', $user)->orderBy('id', 'desc')->first();

            $plaidClass = '\\App\\Lib\\Legacy\\Plaid';
            $plaid      = new $plaidClass();

            // Fallback user details from license detail
            $userArr = $userObj->toArray();
            foreach (['first_name' => 'givenName', 'last_name' => 'lastName', 'dob' => 'dateOfBirth',
                      'address' => 'addressStreet', 'city' => 'addressCity',
                      'state' => 'addressState', 'zip' => 'addressPostalCode'] as $userKey => $licKey) {
                if (empty($userArr[$userKey]) && !empty($userArr[$licKey])) {
                    $userArr[$userKey] = $userArr[$licKey];
                }
            }

            if (empty($exists)) {
                $userPlaid = $plaid->createUser($userArr);
                if (!$userPlaid['status']) {
                    abort(403, 'oooo, ' . json_encode($userPlaid['message']));
                }
                $user_token = $userPlaid['user_token'];
                PlaidUser::create([
                    'user_id'       => $user,
                    'user_token'    => $userPlaid['user_token'],
                    'plaid_user_id' => $userPlaid['user_id'],
                ]);
                $exists = PlaidUser::where('user_id', $user)->latest()->first();
            } else {
                $user_token = $exists->user_token;
            }

            $resp = $plaid->create_link_token($userArr, $user_token);
            if (empty($resp['status']) || empty($resp['link_token'])) {
                abort(403, 'oooo, ' . json_encode($resp['message'] ?? 'Unable to create Plaid link token'));
            }
            $link_token = $resp['link_token'];

            // Persist link_token
            if (empty($exists->user_token)) {
                PlaidUser::where('id', $exists->id)->update(['link_token' => $link_token]);
            } else {
                $emptyRecord = PlaidUser::where('user_id', $user)->whereNull('token')->where('paystub', 0)->first();
                if ($emptyRecord) {
                    $emptyRecord->update(['link_token' => $link_token]);
                } else {
                    PlaidUser::create([
                        'user_id'       => $user,
                        'user_token'    => $user_token,
                        'plaid_user_id' => $exists->plaid_user_id ?? '',
                        'link_token'    => $link_token,
                    ]);
                }
            }

            $session->put('plaidS', ['userid' => $userid, 'user_token' => $user_token, 'link_token' => $link_token]);
        } elseif ($session->has('oauth_state_id')) {
            $plaidObj     = $session->get('plaidS');
            $userid       = $plaidObj['userid'];
            $user_token   = $plaidObj['user_token'];
            $link_token   = $plaidObj['link_token'];
            $oauth_state_id = url('/plaid/callback') . '?oauth_state_id=' . $session->get('oauth_state_id');
        }

        return view('legacy.plaid.index', compact('userid', 'user_token', 'link_token', 'oauth_state_id'));
    }

    // ─── paystub (discontinued) ───────────────────────────────────────────────
    public function paystub(Request $request, $userid)
    {
        abort(410, 'Sorry, this feature is discontinued.');
    }

    // ─── success ──────────────────────────────────────────────────────────────
    public function success()
    {
        return view('legacy.plaid.success');
    }

    // ─── saveUser (AJAX) ──────────────────────────────────────────────────────
    public function saveUser(Request $request)
    {
        if (!$request->input('isAjax')) {
            return response()->json(['status' => false]);
        }

        $user       = base64_decode($request->input('user', ''));
        $plaidClass = '\\App\\Lib\\Legacy\\Plaid';
        $plaid      = new $plaidClass();
        $return     = $plaid->saveUser($request->all(), $user);

        return response()->json($return);
    }

    // ─── webhook ──────────────────────────────────────────────────────────────
    public function webhook(Request $request)
    {
        $postData    = file_get_contents('php://input');
        $logPath     = storage_path('logs/plaid_webhook_' . date('Y-m-d') . '.log');
        @file_put_contents($logPath, "\n" . date('Y-m-d H:i:s') . '=' . $postData, FILE_APPEND);

        $webhookResp = json_decode($postData, true);
        if (!is_array($webhookResp)) {
            return response('invalid', 200);
        }

        // LINK → ITEM_ADD_RESULT: Exchange public token for access token
        if (($webhookResp['webhook_type'] ?? '') === 'LINK'
            && ($webhookResp['webhook_code'] ?? '') === 'ITEM_ADD_RESULT'
            && !empty($webhookResp['link_token'])) {

            $userObj = PlaidUser::where('link_token', $webhookResp['link_token'])->first();
            if (empty($userObj)) {
                return response('dont do anything', 200);
            }

            $plaidClass  = '\\App\\Lib\\Legacy\\Plaid';
            $plaid       = new $plaidClass();
            $authtoken   = $plaid->generateAuthToken($webhookResp['public_token']);

            if (!$authtoken['status']) {
                return response('Auth token couldnt be generated', 200);
            }

            $userObj->update([
                'item_id'         => $authtoken['item_id']         ?? '',
                'link_session_id' => $webhookResp['link_session_id'] ?? '',
                'token'           => $authtoken['access_token'],
            ]);

            User::where('id', $userObj->user_id)->update(['bank' => $userObj->id]);
            VehicleReservation::where('renter_id', $userObj->user_id)->where('status', 0)->update(['status' => 2]);
        }

        // INCOME verification webhook
        if (($webhookResp['webhook_type'] ?? '') === 'INCOME' && !empty($webhookResp['item_id'])) {
            $plaidUserId         = $webhookResp['user_id'];
            $verificationStatus  = $webhookResp['verification_status'];

            if ($verificationStatus === 'VERIFICATION_STATUS_PROCESSING_COMPLETE') {
                $plaidUser = PlaidUser::where('plaid_user_id', $plaidUserId)->first();
                if (empty($plaidUser)) {
                    return response('couldnt find the plaid', 200);
                }

                $plaidClass  = '\\App\\Lib\\Legacy\\Plaid';
                $IncomeObj   = (new $plaidClass())->getIncomeHistory($plaidUser->user_token);

                if (empty($IncomeObj['status'])) {
                    return response('didnt recieved the imcome history api data', 200);
                }

                $earnings         = $IncomeObj['income']['bank_income'] ?? [];
                $monthlyEarnings  = !empty($earnings) ? (array_sum(array_column($earnings, 'total_amount')) / count($earnings)) : 0;

                $incomeRecord = UserIncome::where('user_id', $plaidUser->user_id)->first();
                UserIncome::updateOrCreate(
                    ['user_id' => $plaidUser->user_id],
                    ['provenincome' => $monthlyEarnings]
                );
            }

            if ($verificationStatus === 'VERIFICATION_STATUS_DOCUMENT_REJECTED') {
                $plaidUser = PlaidUser::where('plaid_user_id', $plaidUserId)->first();
                if (!empty($plaidUser)) {
                    $user = User::find($plaidUser->user_id, ['contact_number']);
                    if ($user) {
                        $twilioClass = '\\App\\Lib\\Legacy\\Twilio';
                        (new $twilioClass())->onlyNotifyTwilio(
                            $user->contact_number,
                            'Sorry, your uploaded paystub docs are rejected. Please try to upload correct document or contact to support team.'
                        );
                    }
                }
            }
        }

        return response('finished', 200);
    }

    // ─── callback (OAuth redirect) ────────────────────────────────────────────
    public function callback(Request $request)
    {
        $oauthStateId = $request->query('oauth_state_id');
        if (empty($oauthStateId)) {
            return response('do nothing', 200);
        }

        Log::channel('single')->info('plaid callback', $request->query());
        $request->session()->put('oauth_state_id', $oauthStateId);

        return redirect('/plaid/index');
    }
}
