<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Legacy\User;
use App\Models\Legacy\RevSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class StripeAuthsController extends Controller
{
    public function index(Request $request)
    {
        $status = "error";
        if ($request->has(['code', 'state'])) {
            $authorizationCode = $request->input('code');
            $state = $request->input('state');
            $userid = base64_decode($state);

            $response = Http::asForm()->post('https://connect.stripe.com/oauth/token', [
                'client_secret' => Config::get('services.stripe.secret'),
                'code' => $authorizationCode,
                'grant_type' => 'authorization_code',
            ]);

            $data = $response->json();

            if (isset($data['stripe_user_id'])) {
                if (!empty($userid)) {
                    $stripeUserId = $data['stripe_user_id'];
                    User::where('id', $userid)->update([
                        'is_dealer' => 2,
                        'is_owner' => 1,
                        'stripe_key' => $stripeUserId
                    ]);
                    
                    // RevShare Setting
                    $revSetting = new RevSetting();
                    $revSetting->adduserToRevShare($userid);
                    
                    // TODO: Salesforce Update logic
                    
                    session()->flash('success', 'You are successfully connected with us, please login to use our services.');
                    $status = "success";
                } else {
                    session()->flash('error', 'Sorry, your record not found. Please try again');
                }
            } else {
                session()->flash('error', $data['error_description'] ?? 'Unexpected error occurred.');
            }
        } else {
            session()->flash('error', 'Sorry, something went wrong.');
        }

        return view('legacy.stripe_auths.index', compact('status'));
    }

    public function mbindex(Request $request)
    {
        // Mobile version of OAuth callback
        return $this->index($request);
    }
}
