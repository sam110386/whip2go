<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\UsersTrait;
use App\Models\Legacy\AdminUserAssociation;
use App\Models\Legacy\ArgyleUser;
use App\Models\Legacy\ArgyleUserRecord;
use App\Models\Legacy\User;
use App\Models\Legacy\UserReport;
use App\Services\Legacy\PaymentProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use App\Helpers\Legacy\Security;
use App\Helpers\Legacy\Number;

class UsersController extends LegacyAppController
{
    use UsersTrait;

    public function admin_index(Request $request)
    {
        $adminUser = $this->getAdminUserid();

        if (!$adminUser['administrator']) {
            session()->flash('error', 'Sorry, you are not authorized user for this action');
            return redirect('admin/linked_users/index');
        }

        $cookies = json_decode(Cookie::get('user_list_search', '[]'), true) ?: [];

        if ($request->has('ClearFilter')) {
            Cookie::queue(Cookie::forget('user_list_search'));
            $cookies = [];
            if (!$request->ajax()) {
                return redirect('admin/users/index');
            }
            $request->merge(['keyword' => '', 'show' => '', 'type' => '']);
        }

        $keyword = $request->input('keyword') ?: ($cookies['keyword'] ?? '');
        $show = $request->input('show') ?: ($cookies['show'] ?? '');
        $type = $request->input('type') ?: ($cookies['type'] ?? '');

        $request->merge([
            'keyword' => $keyword,
            'show' => $show,
            'type' => $type
        ]);

        if (!$request->ajax()) {
            Cookie::queue('user_list_search', json_encode(['keyword' => $keyword, 'show' => $show, 'type' => $type]), 1440);
        }

        $query = $this->_getUsersQuery($request);

        $sess_limit_name = "Users_limit";
        $sess_limit_value = Session::get($sess_limit_name);

        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            Session::put($sess_limit_name, $limit);
        } elseif (!empty($sess_limit_value)) {
            $limit = $sess_limit_value;
        } else {
            $limit = 50;
        }

        $users = $query->paginate($limit);
        $users->appends($request->query());

        if ($request->ajax()) {
            return view('admin.elements.users.admin_index', compact('users', 'keyword', 'show', 'type', 'limit'));
        }

        return view('admin.users.admin_index', compact('users', 'keyword', 'show', 'type', 'limit'));
    }

    public function admin_add(Request $request, $id = null)
    {
        $id = base64_decode($id);
        $listTitle = !empty($id) ? 'Update User' : 'Add User';
        $user = null;

        if ($request->isMethod('post')) {

            $result = $this->_saveUser($request, $id);

            Session::flash($result['status'] ? 'success' : 'error', $result['message']);

            if ($result['status']) {
                return redirect('admin/users/index');
            } else {
                return redirect()->back();
            }
        }

        if (!empty($id) && $request->isMethod('get')) {
            $user = User::with('userLicenseDetail')->findOrFail($id);

            if (!empty($user->userLicenseDetail->documentNumber)) {
                $user->userLicenseDetail->documentNumber = Security::decrypt($user->userLicenseDetail->documentNumber);
            }

            if (!empty($user->licence_number)) {
                $user->licence_number = Security::decrypt($user->licence_number);
            }
        }

        $currencies = Number::getCurrencies();

        return view('admin.users.admin_add', compact('user', 'listTitle', 'currencies', 'id'));
    }

    public function admin_view($id)
    {
        $listTitle = 'View User';
        $id = base64_decode($id);
        $user = User::findOrFail($id);
        return view('admin.users.admin_view', compact('user', 'listTitle'));
    }

    public function admin_status($id, $status)
    {
        if ($redirect = $this->ensureAdminSession())
            return response()->json(['error' => 'Unauthorized'], 403);

        $id = base64_decode($id);
        $result = $this->_toggleStatus($id, 'status', $status);

        Session::flash($result['status'] ? 'success' : 'error', $result['message']);
        return back();
    }

    /**
     * admin_verify: Toggle user verification status
     */
    public function admin_verify($id)
    {
        if ($redirect = $this->ensureAdminSession())
            return response()->json(['error' => 'Unauthorized'], 403);

        $id = base64_decode($id);
        $user = User::find($id);
        if (!$user) {
            Session::flash('error', 'User not found.');
            return back();
        }

        $user->is_verified = 1;
        $user->verify_token = '';
        $user->save();

        AdminUserAssociation::saveLeadAssociation($user->username, $user->id);
        Session::flash('success', 'User status has been changed.');
        return back();
    }


    /**
     * admin_edit: Profile edit screen and save
     */
    public function admin_edit(Request $request, $id)
    {
        if ($redirect = $this->ensureAdminSession())
            return $redirect;

        $id = base64_decode($id);
        if ($request->isMethod('post')) {
            $result = $this->_saveUser($request, $id);
            Session::flash($result['status'] ? 'success' : 'error', $result['message']);
            if ($result['status'])
                return redirect()->route('admin.users.index');
        }

        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    /**
     * admin_trash: Soft delete/Trash a user
     */
    public function admin_trash($id, $status = null)
    {
        if ($redirect = $this->ensureAdminSession())
            return response()->json(['error' => 'Unauthorized'], 403);

        $id = base64_decode($id);
        $result = $this->_toggleStatus($id, 'trash', $status);

        Session::flash($result['status'] ? 'success' : 'error', $result['message']);
        return back();
    }


    public function admin_address_proof_popup(Request $request)
    {
        $userid = $request->input('userid');
        return view('legacy.users._addresspopup', compact('userid'));
    }

    public function admin_saveaddressproof(Request $request)
    {
        $userid = $request->input('userid');

        if (!$request->hasFile('proofimage')) {
            return response()->json(['error' => 'No files were uploaded.']);
        }

        $file = $request->file('proofimage');

        if (!$file->isValid()) {
            return response()->json(['error' => 'Upload Error.']);
        }

        $size = $file->getSize();
        if ($size == 0) {
            return response()->json(['error' => 'File is empty.']);
        }

        $fileformat = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['gif', 'jpg', 'jpeg', 'png'];

        if (!in_array($fileformat, $allowedExtensions)) {
            return response()->json(['error' => "File has an invalid extension, it should be one of " . implode(', ', $allowedExtensions) . "."]);
        }

        $filename = 'address_doc_' . $userid . '_' . rand(1, 100) . '.' . $fileformat;
        $destinationPath = public_path('files/userdocs');

        if (!file_exists($destinationPath)) {
            @mkdir($destinationPath, 0777, true);
        }

        if ($file->move($destinationPath, $filename)) {
            $user = clone User::find($userid);
            if ($user) {
                $addressDoc = !empty($user->address_doc) ? json_decode($user->address_doc, true) : [];
                $addressDoc[] = $filename;

                // Use a non-event firing update to avoid triggers, or just save normally
                DB::table('users')->where('id', $userid)->update([
                    'address_doc' => json_encode($addressDoc)
                ]);

                return response()->json(['success' => true, "key" => $userid]);
            }
        }

        return response()->json(['error' => 'Could not save uploaded file. The upload was cancelled, or server error encountered']);
    }
    public function admin_bankdetails(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $id = base64_decode($id);
        if (empty($id)) {
            return redirect('admin/users/index');
        }

        $listTitle = 'Connect with Stripe';
        $user = User::findOrFail($id);

        if ($request->isMethod('post')) {
            $data = $request->input('User', []);
            if (empty($data)) {
                $data = $request->only(['business_type', 'ss_no', 'ein_no']);
            }

            if (!empty($data['business_type'])) {
                $user->business_type = $data['business_type'];
            }
            $user->is_owner = 1;

            $ssNo = $data['ss_no'] ?? $request->input('ss_no');
            if (!empty($ssNo)) {
                $user->ss_no = Security::encrypt($ssNo);
            }
            $einNo = $data['ein_no'] ?? $request->input('ein_no');
            if (!empty($einNo)) {
                $user->ein_no = Security::encrypt($einNo);
            }

            $user->save();
            Session::flash('success', "Bank Account Details updated successfully.");
            return redirect('admin/users/index');
        }

        if (!$user->is_owner && $request->isMethod('get')) {
            // The CakePHP redirects if not an owner when doing a GET. We'll mirror it.
            // return redirect('admin/users/index');
        }

        return view('admin.users.admin_bankdetails', [
            'id' => $user->id,
            'listTitle' => $listTitle,
            'user' => $user
        ]);
    }
    public function admin_change_phone(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = base64_decode($id);
        if (empty($id)) {
            Session::flash('error', "Sorry, you are not authorize user for this action.");
            return redirect('admin/users/index');
        }

        $listTitle = 'Change Phone#';

        if ($request->isMethod('post')) {
            $dataValues = $request->all();
            $contactNumber = preg_replace("/[^0-9]/", "", $dataValues['contact_number'] ?? '');
            $username = substr($contactNumber, -10);

            $user = User::findOrFail($id);
            if ($username != $request->input('old_username')) {
                $user->is_verified = 0;
                $user->status = 0;
                $user->verify_token = rand(10000, 99999); // Generate a token
                $user->username = $username;
                $user->contact_number = $username;
            }

            if ($user->save()) {
                if (!empty($user->verify_token)) {
                    // Logic for Twilio notification
                    if (class_exists('App\Models\Legacy\TwilioSetting')) {
                        try {
                            $twilio = new \App\Models\Legacy\TwilioSetting();
                            if (method_exists($twilio, 'notifyActivationByTwilio')) {
                                $twilio->notifyActivationByTwilio([
                                    'phone_number' => $user->contact_number,
                                    'activation_code' => $user->verify_token
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error("Twilio notification failed: " . $e->getMessage());
                        }
                    }
                }
                Session::flash('success', "Phone number updated successfully.");
                return redirect('admin/users/index');
            } else {
                Session::flash('error', "Validation failed.");
            }
        }

        $user = User::findOrFail($id);
        return view('admin.users.admin_change_phone', compact('id', 'listTitle', 'user'));
    }
    public function admin_checkr_status(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $encodedId = (string) ($id ?? $request->route('id', $request->input('id')));
        $id = base64_decode($encodedId);

        if (empty($id)) {
            Session::flash('error', 'Invalid user id.');
            return back();
        }

        $userReport = UserReport::where('user_id', $id)->first();

        if (empty($userReport)) {
            $checkrStatus = $this->addCandidateToDriverBackgroundReport($id);
            Session::flash($checkrStatus['status'] ? 'success' : 'error', $checkrStatus['status'] ? 'User is added to Checkr API for processing' : ($checkrStatus['message'] ?? 'Checkr request failed.'));
            return back();
        }

        if ((int) $userReport->status === 1 && !empty($userReport->checkr_reportid)) {
            $report = $this->pullBackgroundReport($id);
            Session::flash($report['status'] ? 'success' : 'error', $report['status'] ? 'User Report is Ready' : ($report['message'] ?? 'Unable to pull report.'));
            return back();
        }

        if ((int) $userReport->status === 0) {
            $checkrReport = $this->createBackgroundReport($id);
            Session::flash($checkrReport['status'] ? 'success' : 'error', $checkrReport['status'] ? 'User Report is requested' : ($checkrReport['message'] ?? 'Unable to request report.'));
        }

        return back();
    }

    public function admin_checkrreport(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json(["status" => false, "message" => "Something went wrong"]);
        }

        $id = (int) $request->input('userid');
        $ownerid = (int) $request->input('ownerid');
        $return = ["status" => false, "message" => "Something went wrong"];

        if (empty($id)) {
            return response()->json($return);
        }

        $userReport = UserReport::where('user_id', $id)->first();
        if (empty($userReport)) {
            return response()->json($this->addCandidateToDriverBackgroundReport($id, $ownerid));
        }

        if ((int) $userReport->status === 1 && !empty($userReport->checkr_reportid)) {
            return response()->json($this->pullBackgroundReport($id));
        }

        if ((int) $userReport->status === 0) {
            return response()->json($this->createBackgroundReport($id, $ownerid));
        }

        return response()->json($return);
    }
    public function admin_dealer_approve($id, $status = null)
    {
        if ($redirect = $this->ensureAdminSession())
            return response()->json(['error' => 'Unauthorized'], 403);

        $id = base64_decode($id);
        $result = $this->_toggleStatus($id, 'is_dealer', $status);

        Session::flash($result['status'] ? 'success' : 'error', $result['message']);
        return back();
    }

    public function admin_driverstatus($id, $status = null)
    {
        if ($redirect = $this->ensureAdminSession())
            return response()->json(['error' => 'Unauthorized'], 403);

        $id = base64_decode($id);
        $result = $this->_toggleStatus($id, 'is_driver', $status);

        Session::flash($result['status'] ? 'success' : 'error', $result['message']);
        return back();
    }
    public function admin_getDriverLicense(Request $request)
    {
        $return = ['status' => false, 'message' => "Invalid User ID", 'result' => []];
        $userid = base64_decode($request->input('userid'));
        $pick = $request->input('pick', 1);

        if (empty($userid)) {
            return response()->json($return);
        }

        $user = User::select('license_doc_1', 'license_doc_2')->find($userid);

        if ($pick == 1) {
            if ($user && !empty($user->license_doc_1) && file_exists(public_path('files/userdocs/' . $user->license_doc_1))) {
                return response()->json([
                    'status' => true,
                    'message' => "Success",
                    'result' => ['file' => url('files/userdocs/' . $user->license_doc_1)]
                ]);
            }
            return response()->json(['status' => false, 'message' => "sorry, document not exists", 'result' => []]);
        }

        if ($pick == 2 && $user && !empty($user->license_doc_2) && file_exists(public_path('files/userdocs/' . $user->license_doc_2))) {
            return response()->json([
                'status' => true,
                'message' => "Success",
                'result' => ['file' => url('files/userdocs/' . $user->license_doc_2)]
            ]);
        }

        return response()->json(['status' => false, 'message' => "sorry, document not exists", 'result' => []]);
    }
    public function admin_getmystripeurl(Request $request)
    {
        $return = ['status' => false, 'message' => "Something went wrong", 'result' => []];
        $data = $request->input('User', []);
        if (empty($data['id'])) {
            return response()->json($return);
        }

        $user = User::find($data['id']);
        if (!$user) {
            return response()->json(['status' => false, 'message' => "User not found", 'result' => []]);
        }

        $businessType = $data['business_type'] ?? 'individual';
        $clientId = app()->environment('local')
            ? 'ca_DO0i6vs5rkJFfLxSOHlZaDM5NgZO7MXP'
            : 'ca_DO0iprYjjYh3yIuFb2hKtEY8INnrW9xq';

        $params = [
            'response_type' => 'code',
            'client_id' => $clientId,
            'scope' => 'read_write',
            'state' => base64_encode((string) $user->id),
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
        ];

        if (!app()->environment('local')) {
            $params['stripe_user[business_name]'] = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        } else {
            $params['redirect_uri'] = url('StripeAuths/index');
        }

        $url = 'https://connect.stripe.com/express/oauth/authorize?' . http_build_query($params);
        $return = ['status' => true, 'message' => "You will be redirected to Stripe portal", 'result' => ['url' => $url]];
        return response()->json($return);
    }

    public function admin_getstripeloginurl(Request $request)
    {
        $stripekey = $request->input('stripekey');
        if (empty($stripekey)) {
            return response()->json(['status' => false, 'message' => "Something went wrong", 'result' => []]);
        }

        $paymentProcessor = $this->getPaymentProcessor();
        if (!$paymentProcessor || !method_exists($paymentProcessor, 'createLoginLink')) {
            return response()->json(['status' => false, 'message' => "Stripe processor is not available.", 'result' => []]);
        }

        return response()->json($paymentProcessor->createLoginLink($stripekey));
    }

    public function admin_loadPayoutSchedule(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $loadedData = [];
        $stripekey = $request->input('stripekey');
        if (!empty($stripekey)) {
            $paymentProcessor = $this->getPaymentProcessor();
            if ($paymentProcessor && method_exists($paymentProcessor, 'accountRetrieve')) {
                $loadedData = $paymentProcessor->accountRetrieve($stripekey);
                if (!isset($loadedData['id'])) {
                    Session::flash('error', 'Sorry, Connected Account could not found on stripe end.');
                    $loadedData = [];
                }
            }
        }

        return view('admin.users.admin_load_payout_schedule', ['token' => $stripekey, 'Loadeddata' => $loadedData]);
    }
    public function admin_revsetting(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userId = base64_decode($id);
        if (empty($userId)) {
            return redirect('admin/users/index');
        }

        $listTitle = 'Revenue Setting';
        $revSetting = \App\Models\Legacy\RevSetting::firstOrNew(['user_id' => $userId]);

        if ($request->isMethod('post')) {
            $data = $request->except(['_token', 'id']);
            $revSetting->fill($data);

            if ($revSetting->save()) {
                Session::flash('success', "Revenue setting updated successfully.");
                return redirect('admin/users/index');
            } else {
                Session::flash('error', "Validation failed.");
            }
        }

        return view('admin.users.admin_revsetting', [
            'user_id' => $userId,
            'revSetting' => $revSetting,
            'listTitle' => $listTitle
        ]);
    }

    public function admin_showargyldetails(Request $request)
    {
        $encodedUserId = $request->input('userid', $request->input('uid'));
        $userid = base64_decode((string) $encodedUserId);
        $argyleUser = [];

        if (!empty($userid)) {
            $argyle = ArgyleUser::where('user_id', $userid)->first();
            if ($argyle) {
                $argyleUser = $argyle->toArray();
                $argyleUser['ArgyleUserRecord'] = ArgyleUserRecord::where('argyle_user_id', $argyle->id)->get()->toArray();
            }
        }
        return view('admin.users.admin_showargyldetails', ['ArgyleUser' => $argyleUser]);
    }
    public function admin_updatePayoutSchedule(Request $request)
    {
        $return = ["status" => false, "message" => "Sorry, Something went wrong, please try again"];
        $data = $request->input('User', []);
        if (empty($data['token'])) {
            $data = $request->only(['token', 'frequency', 'delay', 'weekly_anchor', 'monthly_anchor']);
        }

        $stripekey = $data['token'] ?? null;
        if (empty($stripekey)) {
            return response()->json($return);
        }

        $paymentProcessor = $this->getPaymentProcessor();
        if (!$paymentProcessor || !method_exists($paymentProcessor, 'updateConnectedAccount')) {
            return response()->json(["status" => false, "message" => "Stripe processor is not available."]);
        }

        $frequency = $data['frequency'] ?? '';
        if ($frequency === 'daily' && !empty($data['delay'])) {
            $updateTo = ["payout_schedule" => ["delay_days" => (int) $data['delay'], "interval" => 'daily']];
            return response()->json($paymentProcessor->updateConnectedAccount($stripekey, $updateTo));
        }
        if ($frequency === 'weekly' && !empty($data['weekly_anchor'])) {
            $updateTo = ["payout_schedule" => ["delay_days" => 7, "interval" => 'weekly', "weekly_anchor" => $data['weekly_anchor']]];
            return response()->json($paymentProcessor->updateConnectedAccount($stripekey, $updateTo));
        }
        if ($frequency === 'monthly' && !empty($data['monthly_anchor'])) {
            $day = (int) $data['monthly_anchor'];
            $updateTo = ["payout_schedule" => ["delay_days" => $day, "interval" => 'monthly', "monthly_anchor" => $day]];
            return response()->json($paymentProcessor->updateConnectedAccount($stripekey, $updateTo));
        }
        return response()->json($return);
    }

    public function admin_delete($id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = base64_decode((string) $id);
        if (!empty($id)) {
            User::where('id', $id)->delete();
            Session::flash('success', 'User has been deleted, successfully');
        }
        return redirect('admin/users/index');
    }

    private function getPaymentProcessor()
    {
        return app(PaymentProcessor::class);
    }
}
