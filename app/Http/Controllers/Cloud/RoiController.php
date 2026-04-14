<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\AgreementTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class RoiController extends LegacyAppController
{
    use AgreementTrait;

    private array $allowedExtensions = [
        'jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    private array $userFields = [
        'id', 'first_name', 'middle_name', 'last_name', 'email', 'photo',
        'contact_number', 'address', 'ss_no', 'dob', 'city', 'state', 'zip',
        'is_driver', 'is_passenger', 'currency', 'timezone',
    ];

    public function initiate(Request $request)
    {
        $authorization = $request->header('Authorization', '');

        $userObj = session('userObj');
        if (empty($authorization) && !empty($userObj)) {
            return response()->json([
                'status'   => true,
                'message'  => 'you are logged in userObj',
                'view_url' => url('/insurance/roi/display'),
            ]);
        }

        $jwtToken = trim(str_replace('Basic', '', $authorization));

        try {
            $user = DB::table('users')
                ->where('status', 1)
                ->where('is_driver', 1)
                ->where('is_verified', 1)
                ->where('is_admin', 0)
                ->where('token', $jwtToken)
                ->select($this->userFields)
                ->first();

            if (!$user) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Sorry, seems you are also logged in on another device/browser. Please login back',
                ], 402);
            }

            session(['userObj' => (array) $user]);

            return response()->json([
                'status'   => true,
                'message'  => 'you are logged in',
                'view_url' => url('/insurance/roi/display'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Please log back in',
            ], 400);
        }
    }

    public function generateUrl()
    {
        $userObj = session('userObj');
        if (empty($userObj)) {
            return redirect()->action([self::class, 'initiate']);
        }

        $booking = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'VehicleReservation.vehicle_id')
            ->leftJoin('users as User', 'User.id', '=', 'VehicleReservation.renter_id')
            ->where('VehicleReservation.renter_id', $userObj['id'])
            ->whereIn('VehicleReservation.status', [0, 1])
            ->whereNotNull('User.id')
            ->select('VehicleReservation.id', 'Vehicle.*', 'User.*')
            ->first();

        if (empty($booking)) {
            return 'https://roi-insurance.com/driveitaway/';
        }

        $licenceNumber = '';
        try {
            $licenceNumber = Crypt::decrypt($booking->licence_number);
        } catch (\Exception $e) {
            $licenceNumber = $booking->licence_number ?? '';
        }

        return 'https://roi-insurance.com/driveitaway/'
            . '?first=' . $booking->first_name
            . '&last=' . $booking->last_name
            . '&phone=' . $booking->contact_number
            . '&email=' . $booking->email
            . '&dob=' . $booking->dob
            . '&license=' . $licenceNumber
            . '&licensestate=' . $booking->licence_state
            . '&vin1=' . $booking->vin_no
            . '&year1=' . $booking->year
            . '&make1=' . $booking->make
            . '&model1=' . $booking->model
            . '&deductible1=%241000'
            . '&street=' . $booking->address
            . '&street2='
            . '&city=' . $booking->city
            . '&state=' . $booking->state
            . '&zip=' . $booking->zip
            . '&country=United+State'
            . '&start=' . date('m/d/Y');
    }

    public function index()
    {
        $userObj = session('userObj');
        if (empty($userObj)) {
            return redirect()->action([self::class, 'initiate']);
        }

        $booking = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'VehicleReservation.vehicle_id')
            ->leftJoin('users as User', 'User.id', '=', 'VehicleReservation.renter_id')
            ->where('VehicleReservation.renter_id', $userObj['id'])
            ->whereIn('VehicleReservation.status', [0, 1])
            ->whereNotNull('User.id')
            ->select('VehicleReservation.id', 'Vehicle.*', 'User.*')
            ->first();

        if (empty($booking)) {
            abort(403, 'Sorry, you dont have any pending booking available');
        }

        $licenceNumber = '';
        try {
            $licenceNumber = Crypt::decrypt($booking->licence_number);
        } catch (\Exception $e) {
            $licenceNumber = $booking->licence_number ?? '';
        }

        $url = 'https://roi-insurance.com/driveitaway/'
            . '?first=' . $booking->first_name
            . '&last=' . $booking->last_name
            . '&phone=' . $booking->contact_number
            . '&email=' . $booking->email
            . '&dob=' . $booking->dob
            . '&license=' . $licenceNumber
            . '&licensestate=' . $booking->licence_state
            . '&vin1=' . $booking->vin_no
            . '&year1=' . $booking->year
            . '&make1=' . $booking->make
            . '&model1=' . $booking->model
            . '&deductible1=%241000'
            . '&street=' . $booking->address
            . '&street2='
            . '&city=' . $booking->city
            . '&state=' . $booking->state
            . '&zip=' . $booking->zip
            . '&country=United+State'
            . '&start=' . date('m/d/Y');

        return redirect($url);
    }

    public function display()
    {
        $userObj = session('userObj');
        if (empty($userObj)) {
            return redirect()->action([self::class, 'initiate']);
        }

        $booking = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'VehicleReservation.id')
            ->where('VehicleReservation.renter_id', $userObj['id'])
            ->where('VehicleReservation.status', 0)
            ->whereNotNull('OrderDepositRule.id')
            ->select('VehicleReservation.id', 'VehicleReservation.renter_id', 'OrderDepositRule.insurance_payer')
            ->orderBy('VehicleReservation.id', 'DESC')
            ->first();

        if (empty($booking)) {
            return redirect('https://www.driveitaway.com')
                ->with('error', 'Sorry, you dont have any active booking. Please contact to support');
        }

        if ($booking->insurance_payer == 4) {
            return redirect('/insurance_provider/insurance_quotes/review/' . base64_encode($booking->id . '|' . $booking->renter_id));
        }
        if ($booking->insurance_payer == 6) {
            return redirect('/insurance_provider/insurance_quotes/review/' . base64_encode($booking->id . '|' . $booking->renter_id));
        }
        if ($booking->insurance_payer == 5) {
            return redirect('/insurance/roi/diafinancedreview/' . base64_encode($booking->id . '|' . $booking->renter_id));
        }
        if ($booking->insurance_payer == 7) {
            return redirect('/insurance/roi/diafleetbackupreview/' . base64_encode($booking->id . '|' . $booking->renter_id));
        }

        return view('cloud.insurance.roi.display', [
            'title_for_layout' => 'Manage Insurance',
            'insurance_payer'  => $booking->insurance_payer,
            'quoteurl'         => $this->generateUrl(),
        ]);
    }

    public function popup()
    {
        $userObj = session('userObj');
        if (empty($userObj)) {
            return redirect()->action([self::class, 'initiate']);
        }

        $booking = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'VehicleReservation.id')
            ->where('VehicleReservation.renter_id', $userObj['id'])
            ->where('VehicleReservation.status', 0)
            ->whereNotNull('OrderDepositRule.id')
            ->select('VehicleReservation.id', 'OrderDepositRule.id as order_deposit_rule_id')
            ->orderBy('VehicleReservation.id', 'DESC')
            ->first();

        if (empty($booking)) {
            return redirect()->action([self::class, 'display'])
                ->with('error', 'Sorry, you dont have any active booking. Please contact to support');
        }

        $recordid = $booking->order_deposit_rule_id;
        $insurancePayer = DB::table('insurance_payers')
            ->where('order_deposit_rule_id', $recordid)
            ->first();

        return view('cloud.insurance.roi.popup', [
            'recordid'       => $recordid,
            'insurancePayer' => $insurancePayer ? (array) $insurancePayer : [],
        ]);
    }

    public function save(Request $request)
    {
        if ($request->ajax()) {
            $data = $request->all();
            if (!empty($data['id'])) {
                DB::table('insurance_payers')->where('id', $data['id'])->update($data);
            } else {
                DB::table('insurance_payers')->insert($data);
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Vehicle has been updated successfully',
        ]);
    }

    public function saveImage(Request $request)
    {
        $data = $request->all();
        $type = $data['type'] ?? '';
        $file = $request->file($type);
        $return = $this->handleUpload($file, $data);

        return response()->json($return);
    }

    private function handleUpload($file, array $data): array
    {
        $id = $data['id'] ?? null;
        $filetype = $data['type'] ?? '';
        $providerid = $data['providerid'] ?? null;

        if (!$file || !$file->isValid()) {
            return ['error' => 'No files were uploaded.'];
        }

        if ($file->getSize() === 0) {
            return ['error' => 'File is empty.'];
        }

        $mimeType = $file->getMimeType();
        preg_match('/^(image|application)\/(gif|jpe?g|png|pdf|msword)/', $mimeType, $matches);
        $fileformat = $matches[2] ?? ($matches[1] ?? '');

        if (!empty($this->allowedExtensions) && !in_array(strtolower($fileformat), array_map('strtolower', $this->allowedExtensions))) {
            return ['error' => 'File has an invalid extension, it should be one of ' . implode(', ', $this->allowedExtensions) . '.'];
        }

        if ($providerid !== null) {
            $filename = $filetype . '_' . $providerid . '_' . $id . '.' . $fileformat;
        } else {
            $filename = $filetype . '_' . $id . '.' . $fileformat;
        }

        $filepath = public_path('files/reservation/' . $filename);

        if ($file->move(public_path('files/reservation'), $filename)) {
            if ($filetype === 'quote_doc') {
                $existing = DB::table('driver_financed_insurance_quotes')
                    ->where('order_id', $id)
                    ->first();

                $oldQuote = [];
                if (!empty($existing->quote)) {
                    $oldQuote = json_decode($existing->quote, true) ?: [];
                }
                $oldQuote[$providerid][$filetype] = $filename;

                $dataTosave = [
                    'order_id' => $id,
                    'quote'    => json_encode($oldQuote),
                ];

                if (!empty($existing)) {
                    DB::table('driver_financed_insurance_quotes')
                        ->where('id', $existing->id)
                        ->update($dataTosave);
                } else {
                    DB::table('driver_financed_insurance_quotes')->insert($dataTosave);
                }

                return ['success' => true];
            }

            $existing = DB::table('insurance_payers')
                ->where('order_deposit_rule_id', $id)
                ->first();

            $dataTosave = [
                $filetype               => $filename,
                'order_deposit_rule_id' => $id,
            ];

            if (!empty($existing)) {
                DB::table('insurance_payers')
                    ->where('id', $existing->id)
                    ->update($dataTosave);
            } else {
                DB::table('insurance_payers')->insert($dataTosave);
            }

            return ['success' => true];
        }

        return ['error' => 'Could not save uploaded file. The upload was cancelled, or server error encountered'];
    }

    public function agreement()
    {
        $userObj = session('userObj');
        if (empty($userObj)) {
            return redirect()->action([self::class, 'initiate']);
        }

        $booking = DB::table('vehicle_reservations')
            ->where('renter_id', $userObj['id'])
            ->where('status', 0)
            ->select('id')
            ->orderBy('id', 'DESC')
            ->first();

        if (empty($booking)) {
            return redirect()->action([self::class, 'display'])
                ->with('error', 'Sorry, you dont have any active booking. Please contact to support');
        }

        $resp = $this->_getPendingBookingAgreement($booking->id);
        if (!$resp['status']) {
            return redirect()->action([self::class, 'display'])
                ->with('error', $resp['message']);
        }

        return response()->download($resp['result']['filepath']);
    }

    public function downloadinsurance()
    {
        $userObj = session('userObj');
        if (empty($userObj)) {
            return redirect()->action([self::class, 'initiate']);
        }

        $filepath = public_path('files/Proof-Of-Insurance-Conformation-Revised-2023.pdf');

        return response()->download($filepath);
    }

    public function diafinancedreview($orderandusers, $force = false)
    {
        list($order, $user) = explode('|', base64_decode($orderandusers));
        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        $booking = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'VehicleReservation.id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'VehicleReservation.vehicle_id')
            ->where('VehicleReservation.id', $order)
            ->where('VehicleReservation.renter_id', $user)
            ->where('VehicleReservation.status', 0)
            ->whereNotNull('OrderDepositRule.id')
            ->select(
                'VehicleReservation.id', 'VehicleReservation.renter_id',
                'OrderDepositRule.insurance_payer',
                'Vehicle.make', 'Vehicle.model', 'Vehicle.year', 'Vehicle.vin_no'
            )
            ->orderBy('VehicleReservation.id', 'DESC')
            ->first();

        if (empty($booking)) {
            abort(403, 'sorry wrong attempt');
        }

        $quoteObj = DB::table('driver_financed_insurance_quotes')
            ->where('order_id', $order)
            ->first();

        if (!$force && !empty($quoteObj) && $quoteObj->quote_approved !== null && $quoteObj->docusign_status == 0) {
            $url = '/insurance/driver_financed_docusign/signDocument/' . $orderandusers;
            return view('cloud.insurance.roi._sign_document', [
                'orderandusers'    => $orderandusers,
                'url'              => $url,
                'title_for_layout' => 'The insurance quote has been reviewed and it appears affordable',
            ]);
        }

        $cardObj = (!empty($quoteObj) && !empty($quoteObj->credit_card))
            ? json_decode($quoteObj->credit_card, true) : [];

        if (!$force && !empty($quoteObj) && $quoteObj->quote_approved !== null && $quoteObj->docusign_status == 1 && empty($cardObj)) {
            return view('cloud.insurance.roi._showccpending', [
                'orderandusers'    => $orderandusers,
                'title_for_layout' => 'Use following Credit Card, to purchase insurance',
            ]);
        }

        if (!$force && !empty($quoteObj) && $quoteObj->quote_approved !== null && !empty($cardObj)) {
            return view('cloud.insurance.roi._showcreditcard', [
                'orderandusers'    => $orderandusers,
                'cardObj'          => $cardObj,
                'title_for_layout' => 'Use following Credit Card, to purchase insurance',
            ]);
        }

        $providers = DB::table('insurance_providers')
            ->where('status', 1)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return view('cloud.insurance.roi.diafinancedreview', [
            'orderandusers'    => $orderandusers,
            'title_for_layout' => 'Choose An Authorized Insurance Provider and Get A Quote!',
            'booking'          => (array) $booking,
            'providers'        => $providers,
        ]);
    }

    public function diafinancednothank($orderandusers)
    {
        list($order, $user) = explode('|', base64_decode($orderandusers));
        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        $booking = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'VehicleReservation.id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'VehicleReservation.vehicle_id')
            ->where('VehicleReservation.id', $order)
            ->where('VehicleReservation.renter_id', $user)
            ->where('VehicleReservation.status', 0)
            ->whereNotNull('OrderDepositRule.id')
            ->select(
                'VehicleReservation.id', 'VehicleReservation.renter_id',
                'OrderDepositRule.insurance_payer',
                'Vehicle.make', 'Vehicle.model', 'Vehicle.year', 'Vehicle.vin_no'
            )
            ->orderBy('VehicleReservation.id', 'DESC')
            ->first();

        if (empty($booking)) {
            abort(403, 'sorry wrong attempt');
        }

        $quoteObj = DB::table('driver_financed_insurance_quotes')
            ->where('order_id', $order)
            ->first();

        if (empty($quoteObj)) {
            abort(403, 'sorry wrong attempt');
        }

        DB::table('driver_financed_insurance_quotes')
            ->where('id', $quoteObj->id)
            ->update(['apply_with_credee' => 2]);

        return redirect('/insurance/roi/diafinancedreview/' . $orderandusers);
    }

    public function diafinancedpopup($orderandusers, $providerid)
    {
        list($order, $user) = explode('|', base64_decode($orderandusers));
        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        $booking = DB::table('vehicle_reservations')
            ->where('id', $order)
            ->where('renter_id', $user)
            ->where('status', 0)
            ->select('id', 'renter_id')
            ->orderBy('id', 'DESC')
            ->first();

        if (empty($booking)) {
            abort(403, 'sorry wrong attempt');
        }

        return view('cloud.insurance.roi.diafinancedpopup', [
            'title_for_layout' => 'Choose An Authorized Insurance Provider and Get A Quote!',
            'orderandusers'    => $orderandusers,
            'recordid'         => $order,
            'providerid'       => $providerid,
        ]);
    }

    public function diafinacedsave(Request $request, $orderandusers)
    {
        list($order, $user) = explode('|', base64_decode($orderandusers));
        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        if (!$request->isMethod('post')) {
            abort(403, 'sorry,wrong attempt');
        }

        $booking = DB::table('vehicle_reservations')
            ->where('id', $order)
            ->where('renter_id', $user)
            ->where('status', 0)
            ->select('id', 'renter_id')
            ->orderBy('id', 'DESC')
            ->first();

        if (empty($booking)) {
            abort(403, 'sorry wrong attempt');
        }

        $quoteNumber = $request->input('quote_number');
        $providerid = $request->input('DriverFinancedInsurance.providerid');

        $existing = DB::table('driver_financed_insurance_quotes')
            ->where('order_id', $order)
            ->first();

        $oldQuote = [];
        if (!empty($existing->quote)) {
            $oldQuote = json_decode($existing->quote, true) ?: [];
        }
        $oldQuote[$providerid]['quote_number'] = $quoteNumber;

        $dataTosave = [
            'order_id' => $order,
            'quote'    => json_encode($oldQuote),
        ];

        if (!empty($existing)) {
            DB::table('driver_financed_insurance_quotes')
                ->where('id', $existing->id)
                ->update($dataTosave);
        } else {
            DB::table('driver_financed_insurance_quotes')->insert($dataTosave);
        }

        return view('cloud.insurance.roi._diafinancedthankyou', [
            'orderandusers'    => $orderandusers,
            'title_for_layout' => 'Thanks for your submission. We will review the details and get back to you.',
            'url'              => '/insurance/roi/diafinancedreview/' . $orderandusers,
        ]);
    }

    public function diafinancedsaveinsuranceaccount(Request $request, $orderandusers)
    {
        list($order, $user) = explode('|', base64_decode($orderandusers));
        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        if ($request->isMethod('post')) {
            $booking = DB::table('vehicle_reservations')
                ->where('id', $order)
                ->where('renter_id', $user)
                ->select('id', 'renter_id')
                ->orderBy('id', 'DESC')
                ->first();

            if (empty($booking)) {
                abort(403, 'sorry wrong attempt');
            }

            $providerAccount = $request->input('DriverFinancedCreditCard');
            if (empty($providerAccount['username']) || empty($providerAccount['password'])) {
                abort(403, 'sorry wrong attempt');
            }

            $existing = DB::table('driver_financed_insurance_quotes')
                ->where('order_id', $order)
                ->first();

            $dataTosave = [
                'order_id'         => $order,
                'provider_account' => json_encode($providerAccount),
            ];

            if (!empty($existing)) {
                DB::table('driver_financed_insurance_quotes')
                    ->where('id', $existing->id)
                    ->update($dataTosave);
            } else {
                DB::table('driver_financed_insurance_quotes')->insert($dataTosave);
            }

            return view('cloud.insurance.roi._thankyou', [
                'title_for_layout' => 'Thanks for your submission. We will review the details and get back to you.',
            ]);
        }

        return view('cloud.insurance.roi._diafinancedsaveinsuranceaccount', [
            'orderandusers' => $orderandusers,
        ]);
    }

    public function diafleetbackupreview($orderandusers, $force = false)
    {
        list($order, $user) = explode('|', base64_decode($orderandusers));
        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        $booking = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'VehicleReservation.id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'VehicleReservation.vehicle_id')
            ->where('VehicleReservation.id', $order)
            ->where('VehicleReservation.renter_id', $user)
            ->where('VehicleReservation.status', 0)
            ->whereNotNull('OrderDepositRule.id')
            ->select(
                'VehicleReservation.id', 'VehicleReservation.renter_id',
                'OrderDepositRule.insurance_payer', 'OrderDepositRule.id as order_deposit_rule_id',
                'Vehicle.make', 'Vehicle.model', 'Vehicle.year', 'Vehicle.vin_no'
            )
            ->orderBy('VehicleReservation.id', 'DESC')
            ->first();

        if (empty($booking)) {
            abort(403, 'sorry wrong attempt');
        }

        $quoteObj = DB::table('driver_financed_insurance_quotes')
            ->where('order_id', $order)
            ->first();

        if (!$force && !empty($quoteObj) && $quoteObj->quote_approved !== null && $quoteObj->docusign_status == 0) {
            $url = '/insurance/dia_fleet_backup_docusign/signDocument/' . $orderandusers;
            return view('cloud.insurance.roi._sign_document_dia_fleet_backup', [
                'orderandusers'    => $orderandusers,
                'url'              => $url,
                'title_for_layout' => 'The insurance quote has been reviewed and it appears affordable',
            ]);
        }

        if (!$force && !empty($quoteObj) && $quoteObj->quote_approved !== null && $quoteObj->docusign_status == 1) {
            $axleurl = '/axle/axledocs/connect/' . base64_encode($booking->order_deposit_rule_id . '|' . $user);
            return redirect($axleurl);
        }

        $providers = DB::table('insurance_providers')
            ->where('status', 1)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();

        return view('cloud.insurance.roi.diafleetbackupreview', [
            'orderandusers'    => $orderandusers,
            'title_for_layout' => 'Choose An Authorized Insurance Provider and Get A Quote!',
            'booking'          => (array) $booking,
            'providers'        => $providers,
        ]);
    }

    public function diafleetbackuppopup($orderandusers, $providerid)
    {
        list($order, $user) = explode('|', base64_decode($orderandusers));
        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        $booking = DB::table('vehicle_reservations')
            ->where('id', $order)
            ->where('renter_id', $user)
            ->where('status', 0)
            ->select('id', 'renter_id')
            ->orderBy('id', 'DESC')
            ->first();

        if (empty($booking)) {
            abort(403, 'sorry wrong attempt');
        }

        return view('cloud.insurance.roi.diafleetbackuppopup', [
            'title_for_layout' => 'Choose An Authorized Insurance Provider and Get A Quote!',
            'orderandusers'    => $orderandusers,
            'recordid'         => $order,
            'providerid'       => $providerid,
        ]);
    }

    public function diafleetbackupsave(Request $request, $orderandusers)
    {
        list($order, $user) = explode('|', base64_decode($orderandusers));
        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        if (!$request->isMethod('post')) {
            abort(403, 'sorry,wrong attempt');
        }

        $booking = DB::table('vehicle_reservations')
            ->where('id', $order)
            ->where('renter_id', $user)
            ->where('status', 0)
            ->select('id', 'renter_id')
            ->orderBy('id', 'DESC')
            ->first();

        if (empty($booking)) {
            abort(403, 'sorry wrong attempt');
        }

        $quoteNumber = $request->input('quote_number');
        $providerid = $request->input('DriverFinancedInsurance.providerid');

        $existing = DB::table('driver_financed_insurance_quotes')
            ->where('order_id', $order)
            ->first();

        $oldQuote = [];
        if (!empty($existing->quote)) {
            $oldQuote = json_decode($existing->quote, true) ?: [];
        }
        $oldQuote[$providerid]['quote_number'] = $quoteNumber;

        $dataTosave = [
            'order_id' => $order,
            'quote'    => json_encode($oldQuote),
        ];

        if (!empty($existing)) {
            DB::table('driver_financed_insurance_quotes')
                ->where('id', $existing->id)
                ->update($dataTosave);
        } else {
            DB::table('driver_financed_insurance_quotes')->insert($dataTosave);
        }

        return view('cloud.insurance.roi._diafinancedthankyou', [
            'orderandusers'    => $orderandusers,
            'title_for_layout' => 'Thanks for your submission. We will review the details and get back to you.',
            'url'              => '/insurance/roi/diafleetbackupreview/' . $orderandusers,
        ]);
    }
}
