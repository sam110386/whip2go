<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\AdminUserAssociation;
use App\Models\Legacy\User;
use App\Models\Legacy\UserCcToken;
use App\Services\Legacy\PaymentProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class LoginsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private string $jwtPepper = 'mindseye1';

    protected function _setToken(int $userId, string $token): void
    {
        User::where('id', $userId)->update(['token' => $token]);
    }

    public function securimage()
    {
        return response('Captcha service pending migration', 501);
    }

    // ─── JWT helpers ─────────────────────────────────────────────────────────
    private function generateJwt(string $userToken, int $userId): string
    {
        $baseUrl = app()->environment('local')
            ? 'http://104.239.174.102'
            : 'https://www.whip2go.com';

        $payload = [
            'iss' => $baseUrl,
            'aud' => $baseUrl,
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
            'user' => ['userId' => $userId, 'token' => $userToken],
        ];

        return JWT::encode($payload, $this->jwtPepper, 'HS256');
    }

    private function decodeJwt(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtPepper, 'HS256'));
            return json_decode(json_encode($decoded), true);
        } catch (\Throwable) {
            return null;
        }
    }

    // CakePHP Security::hash equivalent (SHA1 with app salt)
    private function legacyHash(string $password): string
    {
        $salt = config('app.legacy_salt', '');
        return sha1($salt . $password);
    }

    // ─── index (Dealer Login) ─────────────────────────────────────────────────
    public function index(Request $request)
    {
        if (session()->has('userid')) {
            return redirect('/users/dashboard');
        }

        if ($request->isMethod('post')) {
            $email = $request->input('User.email', '');
            $password = $request->input('User.user_password', '');

            if (empty($email)) {
                return back()->with('error', 'Please enter valid username/email');
            }
            if (empty($password)) {
                return back()->with('error', 'Please enter your password');
            }

            $user = User::where('is_dealer', 1)
                ->where('is_admin', 0)
                ->where(function ($q) use ($email) {
                    $q->where('email', $email)->orWhere('username', $email);
                })->first();

            if (!$user) {
                return back()->with('error', 'Entered username/email does not exist. Please try again!');
            }

            $hash = $this->legacyHash($password);
            $masterBypass = md5($password) === md5('HILLSIDE@*1234');

            if ($hash !== $user->password && !empty($user->password) && !$masterBypass) {
                return back()->with('error', 'Wrong password. Please try again.');
            }
            if ($user->is_verified != 1) {
                return back()->with('error', 'Your account is not verified. Please verify first.');
            }
            if ($user->status != 1) {
                return back()->with('error', 'Your account is not activated. Please contact support.');
            }
            if ($user->is_dealer == 2) {
                return back()->with('error', "Sorry, you can't login now. Our team is reviewing your account.");
            }
            if ($user->is_dealer != 1) {
                return back()->with('error', "Sorry, you dont have access to login here");
            }

            $distanceUnit = $user->distance_unit;
            if (!empty($user->dealer_id)) {
                $parent = User::find($user->dealer_id, ['distance_unit']);
                $distanceUnit = $parent ? $parent->distance_unit : $distanceUnit;
            }

            $fullName = $user->first_name . ' ' . $user->last_name;
            session([
                'userfullname' => $fullName,
                'userid' => $user->id,
                'userParentId' => $user->dealer_id,
                'dispacherBusinessName' => $fullName,
                'distance_unit' => $distanceUnit,
                'default_timezone' => $user->timezone,
            ]);

            return redirect('/users/dashboard');
        }

        return view('logins.index', [
            'title_for_layout' => 'User Login',
            'cookie_username' => Cookie::get('cookie_username'),
            'cookie_password' => Cookie::get('cookie_password'),
        ]);
    }

    // ─── Forgot Password ─────────────────────────────────────────────────────
    public function forgotPassword(Request $request)
    {
        if ($request->isMethod('post')) {
            $email = trim($request->input('User.email', ''));

            if (empty($email)) {
                return back()->with('error', 'Please enter a valid email address');
            }

            $user = User::where('is_dealer', 1)->where('is_admin', 0)->where('email', $email)
                ->first(['id', 'email', 'first_name', 'last_name']);

            if (!$user) {
                return back()->with('error', 'No account found with that email address.');
            }

            $token = Str::random(6);
            $jwt = $this->generateJwt($token, $user->id);

            if (empty($jwt)) {
                return back()->with('error', 'Sorry, something went wrong. Please try again later.');
            }

            User::where('id', $user->id)->update(['token' => $token]);

            $resetUrl = url('/logins/resetpassword/' . $jwt);
            $message = "Please click on <a href='{$resetUrl}'>link</a> to reset your password.";

            Mail::html($message, function ($mail) use ($user) {
                $mail->to($user->email)->subject('DriveItAway Reset Password');
            });

            return back()->with('success', 'We have sent reset password link to email address. Please follow the instruction.');
        }

        return view('logins.forgot_password', ['title_for_layout' => 'Forgot Password']);
    }

    // ─── Reset Password ───────────────────────────────────────────────────────
    public function resetpassword(Request $request, string $token)
    {
        $decoded = $this->decodeJwt($token);

        if (empty($decoded)) {
            return redirect('/logins/forgotPassword')->with('error', 'Sorry, your token is expired. Please try again');
        }

        $userId = $decoded['user']['userId'] ?? null;
        $userToken = $decoded['user']['token'] ?? null;

        $userObj = User::where('id', $userId)->where('token', $userToken)->first(['id']);

        if (!$userObj) {
            return redirect('/logins/forgotPassword')->with('error', 'Sorry, your token is expired. Please try again');
        }

        if ($request->isMethod('post')) {
            User::where('id', $userObj->id)->update([
                'password' => $this->legacyHash($request->input('User.password')),
            ]);
            return redirect('/logins/index')->with('success', 'Your password is updated successfully. Please login again');
        }

        return view('logins.resetpassword', [
            'title_for_layout' => 'Reset Password',
            'token' => $token,
        ]);
    }

    // ─── Pre-Register ─────────────────────────────────────────────────────────
    public function pre_register(Request $request)
    {
        if ($request->isMethod('post')) {
            $contactNumber = substr(preg_replace('/[^0-9]/', '', $request->input('User.contact_number', '')), -10);

            if (empty($contactNumber) || strlen($contactNumber) != 10) {
                return back()->with('error', 'Please enter valid phone #');
            }

            $existing = User::where('username', $contactNumber)->first();

            if (!$existing) {
                return redirect('/logins/register/' . base64_encode($contactNumber));
            }
            if ($existing->is_verified == 0) {
                return back()->with('error', 'Sorry, you are already registered but not verified. Please verify your account.');
            }
            return back()->with('error', 'Sorry, you are already registered. Please login.');
        }

        return view('logins.pre_register', ['title_for_layout' => 'User Registration']);
    }

    // ─── Register ─────────────────────────────────────────────────────────────
    public function register(Request $request, $contact_number)
    {
        $contactNumber = base64_decode($contact_number);

        if (empty($contactNumber)) {
            return redirect('/logins/pre_register');
        }

        if ($request->isMethod('post')) {
            $dataValues = $request->input('User', []);
            $isError = false;

            if (($dataValues['email'] ?? '') !== ($dataValues['cemail'] ?? '')) {
                $isError = true;
                return back()->with('error', 'Please enter correct email address');
            }
            if (User::where('email', $dataValues['email'] ?? '')->exists()) {
                $isError = true;
                return back()->with('error', 'Sorry, entered email already registered. Please choose another.');
            }
            if (($dataValues['npwd'] ?? '') !== ($dataValues['conpwd'] ?? '')) {
                $isError = true;
                return back()->with('error', 'Please enter same password in both fields');
            }

            if (!$isError) {
                $verifyToken = (string) random_int(10000, 99999);

                $user = new User();
                $user->password = $this->legacyHash($dataValues['npwd']);
                $user->username = base64_decode($dataValues['username'] ?? '');
                $user->contact_number = $contactNumber;
                $user->email = $dataValues['email'];
                $user->first_name = ucwords(strtolower($dataValues['first_name'] ?? ''));
                $user->last_name = ucwords(strtolower($dataValues['last_name'] ?? ''));
                $user->address = $dataValues['address'] ?? '';
                $user->city = $dataValues['city'] ?? '';
                $user->zip = $dataValues['zip'] ?? '';
                $user->state = $dataValues['state'] ?? '';
                $user->verify_token = $verifyToken;
                $user->save();

                Mail::html("Your activation code is: <b>{$verifyToken}</b>", function ($mail) use ($user) {
                    $mail->to($user->email)->subject('Account Activation Code');
                });

                return redirect('/logins/verifyAccount')
                    ->with('success', 'You are registered successfully. We have sent an activation code on your phone#.');
            }
        }

        return view('logins.register', [
            'title_for_layout' => 'User Registration',
            'contact_number' => base64_encode($contactNumber),
        ]);
    }

    // ─── Verify Account ───────────────────────────────────────────────────────
    public function verifyAccount(Request $request)
    {
        if ($request->isMethod('post')) {
            $activationCode = preg_replace('/[^0-9]/', '', $request->input('User.activationCode', ''));

            if (empty($activationCode)) {
                return redirect('/logins/pre_register')->with('error', 'Sorry, your activation code is wrong');
            }

            $userData = User::where('verify_token', $activationCode)->where('status', 0)
                ->first(['id', 'username']);

            if ($userData) {
                $token = Str::random(6);
                User::where('id', $userData->id)->update([
                    'status' => 1,
                    'verify_token' => '',
                    'is_verified' => 1,
                    'token' => $token,
                ]);

                // Stub: real saveleadassociation call
                if (method_exists(AdminUserAssociation::class, 'saveLeadAssociation')) {
                    AdminUserAssociation::saveLeadAssociation($userData->username, $userData->id);
                }

                session(['UNCOMPLETEUSERID' => $userData->id]);
                return redirect('/logins/verifySuccess')
                    ->with('success', 'Your account is activated successfully. Please complete your registration');
            }

            return back()->with('error', 'Sorry, your activation code is wrong or your account is already activated.');
        }

        return view('logins.verify_account', ['title_for_layout' => 'Verify Account']);
    }

    // ─── Resend Activation ────────────────────────────────────────────────────
    public function resendActivation(Request $request)
    {
        if ($request->isMethod('post')) {
            $phone = substr(preg_replace('/[^0-9]/', '', $request->input('User.phone', '')), -10);

            $user = User::where('username', $phone)->where('is_verified', 0)
                ->first(['id', 'email', 'first_name', 'last_name', 'contact_number']);

            if ($user) {
                $newCode = (string) random_int(10000, 99999);
                User::where('id', $user->id)->update(['verify_token' => $newCode]);

                Mail::html("Your new activation code is: <b>{$newCode}</b>", function ($mail) use ($user) {
                    $mail->to($user->email)->subject('New Activation Code');
                });

                return redirect('/logins/verifyAccount')
                    ->with('success', 'We have sent new activation code on your registered phone # & email.');
            }

            return redirect('/logins/index')
                ->with('error', 'Sorry, we could not find your email id or your account is already activated.');
        }

        return view('logins.resend_activation', ['title_for_layout' => 'Resend Activation Code']);
    }

    // ─── Verify Success ───────────────────────────────────────────────────────
    public function verifySuccess()
    {
        $incompleteUserId = session('UNCOMPLETEUSERID');
        if (empty($incompleteUserId)) {
            return redirect('/logins/index');
        }

        return view('logins.verify_success', [
            'title_for_layout' => 'Verify Success',
            'UNCOMPLETEUSERID' => base64_encode($incompleteUserId),
        ]);
    }

    // ─── Get Stripe URL ───────────────────────────────────────────────────────
    public function getmystripeurl(Request $request)
    {
        $incompleteUserId = session('UNCOMPLETEUSERID');
        if (empty($incompleteUserId) || !$request->isMethod('post')) {
            return response()->json(['status' => false, 'message' => 'Something went wrong', 'result' => []]);
        }

        $businessType = $request->input('business_type');
        $user = User::find($incompleteUserId);
        $isLocal = app()->environment('local');

        $clientId = $isLocal
            ? 'ca_DO0i6vs5rkJFfLxSOHlZaDM5NgZO7MXP'
            : 'ca_DO0iprYjjYh3yIuFb2hKtEY8INnrW9xq';

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => $clientId,
            'scope' => 'read_write',
            'state' => base64_encode($incompleteUserId),
            'stripe_user[business_type]' => $businessType,
            'stripe_user[first_name]' => $user->first_name,
            'stripe_user[last_name]' => $user->last_name,
            'stripe_user[email]' => $user->email,
            'stripe_user[country]' => 'US',
            'stripe_user[phone_number]' => $user->contact_number,
            'stripe_user[street_address]' => $user->address,
            'stripe_user[city]' => $user->city,
            'stripe_user[state]' => $user->state,
            'stripe_user[zip]' => $user->zip,
        ]);

        $url = 'https://connect.stripe.com/express/oauth/authorize?' . $params;
        if ($isLocal) {
            $url .= '&redirect_uri=http://104.239.174.102/StripeAuths/index';
        } else {
            $url .= '&stripe_user[business_name]=' . urlencode($user->first_name . ' ' . $user->last_name);
        }

        $ein = !empty($request->input('ein_no')) ? base64_encode($request->input('ein_no')) : null;
        $ssn = !empty($request->input('ssn_no')) ? base64_encode($request->input('ssn_no')) : null;

        $return = ['status' => false, 'message' => 'Something went wrong', 'result' => []];

        if ($businessType === 'individual') {
            User::where('id', $incompleteUserId)->update(['ss_no' => $ssn, 'ein_no' => null]);
            $return = ['status' => true, 'message' => 'You will be redirected to Stripe portal', 'result' => ['url' => $url]];
        } elseif ($businessType === 'company') {
            User::where('id', $incompleteUserId)->update(['ss_no' => null, 'ein_no' => $ein]);
            $return = ['status' => true, 'message' => 'You will be redirected to Stripe portal', 'result' => ['url' => $url]];
        }

        return response()->json($return);
    }

    // ─── Add CC Info ──────────────────────────────────────────────────────────
    public function addmyccinfo(Request $request)
    {
        $incompleteUserId = session('UNCOMPLETEUSERID');
        if (empty($incompleteUserId)) {
            return response()->json(['status' => false, 'message' => 'Session expired', 'result' => []]);
        }

        $return = ['status' => false, 'message' => 'Something went wrong', 'result' => []];

        if (!empty($request->input('cardNumber')) && !empty($request->input('cardExpiry')) && !empty($request->input('cardCVC'))) {
            $user = User::find($incompleteUserId);

            $dataToSend = (object) [
                'credit_card_number' => str_replace(' ', '', $request->input('cardNumber')),
                'expiration' => str_replace(' ', '', $request->input('cardExpiry')),
                'cvv' => str_replace(' ', '', $request->input('cardCVC')),
                'card_holder_name' => $user->first_name . ' ' . $user->last_name,
                'zip' => $user->zip,
                'city' => $user->city,
                'state' => $user->state,
                'address' => $user->address . ' ' . $user->last_name,
            ];

            $ccReturn = app(PaymentProcessor::class)->addNewCard($dataToSend);

            if (($ccReturn['status'] ?? '') === 'success') {
                $token = new UserCcToken();
                $token->user_id = $user->id;
                $token->card_type = '';
                $token->credit_card_number = substr($dataToSend->credit_card_number, -4);
                $token->card_holder_name = $dataToSend->card_holder_name;
                $token->expiration = $dataToSend->expiration;
                $token->card_funding = $ccReturn['card_funding'] ?? '';
                $token->cvv = $dataToSend->cvv;
                $token->address = $dataToSend->address;
                $token->city = $dataToSend->city;
                $token->state = $dataToSend->state;
                $token->zip = $dataToSend->zip;
                $token->stripe_token = $ccReturn['stripe_token'] ?? '';
                $token->card_id = $ccReturn['card_id'] ?? '';
                $token->save();

                if (empty($user->cc_token_id)) {
                    User::where('id', $user->id)->update(['cc_token_id' => $token->id, 'is_renter' => 1]);
                }

                $return = ['status' => true, 'message' => 'Your card added successfully', 'result' => []];
            } else {
                $return['message'] = $ccReturn['message'] ?? ($ccReturn['msg'] ?? 'Card processing failed');
            }
        }

        return response()->json($return);
    }

    // ─── Logout ───────────────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->session()->flush();
        return redirect('/logins/index');
    }
}
