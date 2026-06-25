<?php

namespace App\Http\Controllers\Admin;

use App\Services\Legacy\Emailnotify;
use App\Services\Legacy\Free2MoveService;
use Illuminate\Http\Request;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\VehicleReservationsTrait;
use App\Models\Legacy\CsReservationPayment;
use App\Models\Legacy\CsSetting;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\PlaidUser;
use App\Models\Legacy\PrepaidPlan;
use App\Models\Legacy\User;
use App\Models\Legacy\UserIncome;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\VehicleReservationLog;
use App\Services\Legacy\Passtime;
use App\Services\Legacy\PaymentProcessor;
use App\Services\Legacy\PlaidClient;
use App\Services\Legacy\PromoService;
use Carbon\Carbon;

class VehicleReservationsController extends LegacyAppController
{
    use VehicleReservationsTrait;

    public function index(Request $request)
    {
        $title = 'Pending Booking';
        $sessLimitName = "vehiclereservation_limit";
        $limit = $request->input('Record.limit', session($sessLimitName, $this->recordsPerPage ?? 50));

        session([$sessLimitName => $limit]);
        $request->merge(['Record' => ['limit' => $limit]]);

        $allowedStatus = array_keys($this->commonService->getReservationStatus(true, true));

        $bookings = VehicleReservation::with([
            'vehicle:id,msrp,vin_no,vehicle_name',
            'orderDepositRule:id,vehicle_reservation_id,insurance,insurance_payer,insu_agreed,financing',
            'renter:id,first_name,last_name,state'
        ])
            ->whereIn('status', $allowedStatus)
            ->orderBy('id', 'DESC')
            ->paginate($limit);

        if ($request->ajax()) {
            return response()->view('admin.vehicle_reservations.elements.index', [
                'bookings' => $bookings,
                'checklists' => $this->checklist,
                'readyForDealerStatus' => $this->readyForDealerStatus,
                'title' => $title,
                'limit' => $limit
            ]);
        }

        return view('admin.vehicle_reservations.index', [
            'bookings' => $bookings,
            'checklists' => $this->checklist,
            'readyForDealerStatus' => $this->readyForDealerStatus,
            'title' => $title,
            'limit' => $limit
        ]);
    }
    public function all(Request $request)
    {
        $title = 'All Pending Booking';
        $sessLimitName = "vehiclereservation_limit";
        $limit = $request->input('Record.limit', session($sessLimitName, $this->recordsPerPage ?? 50));

        session([$sessLimitName => $limit]);
        $request->merge(['Record' => ['limit' => $limit]]);

        $bookings = VehicleReservation::with([
            'vehicle:id,msrp,vin_no,vehicle_name',
            'owner:id,first_name,last_name'
        ])
            ->orderBy('id', 'DESC')
            ->paginate($limit);

        if ($request->ajax()) {
            return view('admin.vehicle_reservations.elements.all', compact('bookings', 'title', 'limit'));
        }

        return view('admin.vehicle_reservations.all', compact('bookings', 'title', 'limit'));
    }
    public function markBookingCancel(Request $request)
    {
        $leaseIdRaw = $request->input('lease_id');
        $cancelNote = $request->input('cancel_note', '');
        $leaseId = $this->decodeId($leaseIdRaw);
        $return = [
            'status' => false,
            'message' => "Invalid Request",
            'result' => []
        ];

        if (empty($leaseId)) {
            return response()->json($return);
        }

        $reservation = VehicleReservation::find($leaseId);

        if (!$reservation) {
            $return["message"] = "Sorry, booking not found";
            return response()->json($return);
        }

        $return = $this->_markBookingCancel($reservation, $cancelNote);
        return response()->json($return);
    }
    public function createBooking(Request $request)
    {
        $lease_id = $request->input('lease_id');

        if (!empty($lease_id)) {
            $leaseId = $this->decodeId($lease_id);
            $allowedStatus = array_keys($this->commonService->getReservationStatus(true, true));

            $reservation = VehicleReservation::with('vehicle')
                ->where('id', $leaseId)
                ->where('buy', 0)
                ->whereIn('status', $allowedStatus)
                ->first();

            if (!$reservation || !$reservation->vehicle) {
                return redirect()->to('/admin/vehicle_reservations/index');
            }

            $vehicle = $reservation->vehicle;
            $orderDepositRule = OrderDepositRule::where('vehicle_reservation_id', $reservation->id)->first();
            $csReservationPayments = CsReservationPayment::where('reservation_id', $reservation->id)
                ->whereIn('type', [1, 3])
                ->select('type', 'amount')
                ->get();

            $priceRulesAmt = (new DepositRule())->getPendingBookingFee($reservation, $orderDepositRule);

            $tz = $reservation->timezone ?? 'UTC';
            $startDateObj = Carbon::parse($reservation->start_datetime)->timezone($tz);
            $endDateObj = Carbon::parse($reservation->end_datetime)->timezone($tz);

            $startDate = $startDateObj->format('m/d/Y');
            $endDate = $endDateObj->format('m/d/Y');

            // Parse json settings safely
            $rentalOpt = json_decode($orderDepositRule->rental_opt ?? '[]', true);
            $initialFeeOpt = json_decode($orderDepositRule->initial_fee_opt ?? '[]', true);
            $depositOpt = json_decode($orderDepositRule->deposit_opt ?? '[]', true);
            $durationOpt = json_decode($orderDepositRule->duration_opt ?? '[]', true);
            $durations = json_decode($orderDepositRule->duration ?? '[]', true);

            // Aggregations using Eloquent
            $paidRental = CsReservationPayment::getTotalRentalTax($reservation->id); // or sum logic
            $paidInsurance = CsReservationPayment::getTotalInsurance($reservation->id);

            $notification = "";
            $days = $this->commonService->days_between_dates($reservation->start_datetime, $reservation->end_datetime);

            // Timing Validations
            if (Carbon::parse($reservation->start_datetime)->isPast()) {
                $startDate = now()->format('m/d/Y');
                $endDate = now()->addDays($days)->format('m/d/Y');
                $notification = "*Please note that booking start date is expired, any missed scheduled fees date would be adjusted as per current start date selection";
            } elseif (Carbon::parse($reservation->start_datetime)->isFuture()) {
                $notification = "*Please note that booking start date is future date, if you want to start now then please choose today date. Missed scheduled payments would be adjusted as per start date";
            }

            // Next validation checkpoint
            $nextDate = OrderDepositRule::getFromTierData($orderDepositRule->duration_opt, $startDate, $endDate);

            if ($nextDate > 7) {
                $notification .= "<br>*Please note booking will be created with {$nextDate} days interval as per booking duration setting";
            }

            // Dealer validations lookup
            $csSetting = CsSetting::where('user_id', $vehicle->user_id)->first(['booking_validation']);
            $csSettingObj = $csSetting && !empty($csSetting->booking_validation) ? json_decode($csSetting->booking_validation, true) : [];

            $validateVehicle = true;
            $flagfailed = [];

            // Run Dealer Matrix Checklists
            if (($csSettingObj['registration'] ?? 0) == 1 && empty($vehicle->registration_image)) {
                $validateVehicle = false;
                $flagfailed[] = 'Vehicle Registration Missing';
            }

            if (($csSettingObj['inspection'] ?? 0) == 1 && empty($vehicle->inspection_image)) {
                $validateVehicle = false;
                $flagfailed[] = 'Vehicle Inspection Missing';
            }

            if (($csSettingObj['income_threshold'] ?? 0) == 1 && $reservation->income_threshold == 0) {
                $validateVehicle = false;
                $flagfailed[] = 'Income threshold dont qualify';
            }

            // Checking driver relation (assuming custom driver setup exists on reservation)
            if (($csSettingObj['residency_proof'] ?? 0) == 1 && empty($reservation->driver?->address_doc)) {
                $validateVehicle = false;
                $flagfailed[] = 'Driver residence proof is missing';
            }

            // Explicit hardcoded check rules
            if (!in_array($reservation->gps2, [1, 2])) {
                $validateVehicle = false;
                $flagfailed[] = 'GPS2 dont qualify';
            }

            if (!in_array($reservation->docusign, [1, 2])) {
                $validateVehicle = false;
                $flagfailed[] = 'Docusign dont qualify';
            }

            if ($reservation->gps != 1) {
                $validateVehicle = false;
                $flagfailed[] = 'GPS dont qualify';
            }

            if ($reservation->checkr_status != 1) {
                $validateVehicle = false;
                $flagfailed[] = 'Cheker Status dont qualify';
            }

            if (!in_array($reservation->clue_report, [1, 2])) {
                $validateVehicle = false;
                $flagfailed[] = 'Clue Report dont qualify';
            }

            // Income Requirement Multiplier calculation
            if ($orderDepositRule->insurance > 19) {
                $userIncome = UserIncome::where('user_id', $reservation->renter_id)->first();
                $incomeRequired = number_format((($orderDepositRule->rental + $orderDepositRule->insurance) * 4 * 365 / 12), 2, '.', '');

                if (!$userIncome || $userIncome->provenincome < $incomeRequired) {
                    $validateVehicle = false;
                    $flagfailed[] = 'Sorry, driver proven income is not sufficient';
                }
            }

            // Process internal checklists
            $bookingChecklists = $this->commonService->getMissingChecklist($reservation->checklists, $this->checklist);
            $missingChecklists = [];

            // Pass values to array template structure
            return view('admin.vehicle_reservations.create_booking', compact(
                'lease_id',
                'validateVehicle',
                'reservation',
                'vehicle',
                'orderDepositRule',
                'csReservationPayments',
                'priceRulesAmt',
                'startDate',
                'endDate',
                'rentalOpt',
                'initialFeeOpt',
                'depositOpt',
                'notification',
                'paidRental',
                'paidInsurance',
                'durationOpt',
                'days',
                'flagfailed',
                'missingChecklists',
                'durations'
            ));
        }

        return redirect()->back();
    }
    public function markBookingCompleted(Request $request)
    {
        $leaseId = $this->decodeId($request->input('lease_id'));

        if (!empty($leaseId)) {
            VehicleReservation::withoutEvents(function () use ($leaseId) {
                VehicleReservation::where('id', $leaseId)->update(['status' => 1]);
            });

            return response()->json([
                'status' => true,
                'message' => 'Request processed successfully',
                'result' => ['lease_id' => $leaseId]
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid Request',
            'result' => []
        ], 400);
    }
    public function updatelist(Request $request)
    {
        $pk = $request->input('pk');
        $name = $request->input('name');
        $value = $request->input('value');

        $allowedNames = ['gps2', 'gps', 'income_threshold', 'checkr_status', 'clue_report', 'docusign'];

        if (!empty($pk) && !empty($name) && in_array($name, $allowedNames)) {

            VehicleReservation::withoutEvents(function () use ($pk, $name, $value) {
                VehicleReservation::where('id', $pk)->update([$name => $value]);
            });

            return response()->json([
                'status' => true,
                'message' => 'Request processed successfully',
                'result' => []
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid Request',
            'result' => []
        ], 400);
    }
    public function updatemvr(Request $request)
    {
        $pk = $request->input('pk');
        $name = $request->input('name');
        $value = $request->input('value', []);

        $loggedUserId = session('SESSION_ADMIN.id')
            ?? session('userParentId')
            ?? session('userid')
            ?? 0;

        $response = [
            'status' => false,
            'message' => 'Invalid Request',
            'result' => []
        ];

        if (empty($pk) || empty($name)) {
            return response()->json($response, 400);
        }

        if ($name === 'checkr_status') {
            $status = '';
            $accidents3 = (int) ($value['accidents_3'] ?? 0);
            $accidents5 = (int) ($value['accidents_5'] ?? 0);
            $violations = (int) ($value['violations'] ?? 0);

            if ($accidents3 < 2 && $accidents5 < 3 && $violations < 2) {
                $status = 1;
            } elseif ($accidents3 >= 2 || $accidents5 >= 3 || $violations >= 2) {
                $status = 4;
            }

            if ($status !== '') {
                VehicleReservation::withoutEvents(function () use ($pk, $status) {
                    VehicleReservation::where('id', $pk)->update(['checkr_status' => $status]);
                });

                VehicleReservationLog::create([
                    'user_id' => $loggedUserId,
                    'reservation_id' => $pk,
                    'status' => 10,
                    'note' => "Status changed by MVR review, with following choice: accidents_3:$accidents3,accidents_5:$accidents5,violations:$violations"
                ]);
            } else {
                $reservation = VehicleReservation::find($pk, ['checkr_status']);
                $status = $reservation ? $reservation->checkr_status : 0;
            }

            return response()->json([
                'status' => true,
                'message' => 'Request processed successfully',
                'result' => ['status' => $status]
            ]);
        }

        if ($name === 'clue_report') {
            $status = '';
            $accidents3 = (int) ($value['accidents_3'] ?? 0);
            $accidents5 = (int) ($value['accidents_5'] ?? 0);
            $violations = (int) ($value['violations'] ?? 0);
            $notes = $value['notes'] ?? '';

            if ($accidents3 < 2 && $accidents5 < 3 && $violations < 2) {
                $status = 1;
            } elseif ($accidents3 >= 2 || $accidents5 >= 3 || $violations >= 2) {
                $status = 0;
            }

            if ($status !== '') {
                VehicleReservation::withoutEvents(function () use ($pk, $status) {
                    VehicleReservation::where('id', $pk)->update(['clue_report' => $status]);
                });

                VehicleReservationLog::create([
                    'user_id' => $loggedUserId,
                    'reservation_id' => $pk,
                    'status' => 10,
                    'note' => "Status changed by CLUE review, with following choice: accidents_3:$accidents3,accidents_5:$accidents5,violations:$violations,notes:$notes"
                ]);
            } else {
                $reservation = VehicleReservation::find($pk, ['clue_report']);
                $status = $reservation ? $reservation->clue_report : 0;
            }

            return response()->json([
                'status' => true,
                'message' => 'Request processed successfully',
                'result' => ['status' => $status]
            ]);
        }

        return response()->json($response, 400);
    }
    public function saveVehicleBooking(Request $request)
    {
        $result = $this->_saveVehicleBooking($request->all());
        return response()->json($result);
    }
    public function getuserdetails(Request $request)
    {
        $userId = $this->decodeId(trim($request->input('userid')));
        $owner = $this->decodeId(trim($request->input('owner')));
        $booking = !empty(trim($request->input('booking'))) ? $this->decodeId(trim($request->input('booking'))) : '';

        $user = User::with([
            'userLicenseDetail:id,user_id,givenName,lastName,dateOfBirth,addressStreet,addressCity,addressState,addressPostalCode',
            'income',
            'creditScore',
            'measureOne',
            'report'
        ])->find($userId);

        if (!$user) {
            abort(404, 'User not found');
        }

        list($paystub, $paybank) = PlaidUser::getUserFlags($userId);
        $incomeRequired = $monthlyRent = $monthlyInsurance = 'N/A';

        if (!empty($booking)) {
            $orderDepositRuleObj = OrderDepositRule::where('vehicle_reservation_id', $booking)->first();

            if ($orderDepositRuleObj) {
                $rental = $orderDepositRuleObj->rental;
                $tax = $orderDepositRuleObj->tax;
                $insurance = $orderDepositRuleObj->insurance;

                $calculatedRent = ($rental * 365) / 12;
                $monthlyRent = sprintf('%0.2f', $calculatedRent + ($calculatedRent * $tax / 100));
                $monthlyInsurance = sprintf('%0.2f', ($insurance * 365) / 12);
                $incomeRequired = sprintf('%0.2f', ((float) $monthlyRent + (float) $monthlyInsurance) * 4);
            }
        }

        return view('admin.vehicle_reservations.getuserdetails.', compact(
            'user',
            'owner',
            'paystub',
            'paybank',
            'incomeRequired',
            'monthlyRent',
            'monthlyInsurance',
            'booking'
        ));
    }
    public function renderlog($filename)
    {
        $this->_renderlog($filename);
    }
    public function getplaidrecord(Request $request)
    {
        $userid = $this->decodeId($request->input('userid'));
        $plaid = PlaidUser::where('user_id', $userid)->first();

        $return = [
            "status" => false,
            "message" => "Sorry, User didn't add his bank details yet"
        ];

        if ($plaid && !empty(json_decode($plaid->metadata, true))) {
            $plaidView = view('vehicle_reservations.elements.plaid', ['plaid' => $plaid])->render();

            $return = [
                "status" => true,
                "message" => "",
                "userid" => $userid,
                "plaidtoken" => $plaid->token,
                "view" => $plaidView
            ];
        }

        return response()->json($return);
    }
    public function getplaidbalance(Request $request)
    {
        $token = $request->input('plaid_token');
        $accountid = $request->input('acccountid');
        $balanceObj = (new PlaidClient())->getBalance($token, [], [$accountid]);
        $return = [
            "status" => true,
            "message" => "Sorry, balance not returned",
            "balance" => '$0'
        ];

        if (!empty($balanceObj['status']) && isset($balanceObj['accounts'][0])) {
            $account = $balanceObj['accounts'][0];
            $bal = $account['balances']['iso_currency_code'] . ' ' . $account['balances']['current'];
            $return = [
                "status" => true,
                "message" => "",
                "balance" => $bal
            ];
        }

        return response()->json($return);
    }
    public function bankstatement(Request $request)
    {
        $token = $request->input('plaid_token');
        $accountid = $request->input('acccountid');

        $transactionObj = (new PlaidClient())->getTransactionHistory(
            $token,
            now()->subDays(60)->format('Y-m-d'),
            now()->format('Y-m-d'),
            [],
            [$accountid]
        );

        $return = [
            "status" => true,
            "message" => "Sorry, statement is not returned",
            "transactions" => []
        ];

        if (!empty($transactionObj['status'])) {
            $transactionView = view('vehicle_reservations.elements.statement', ['transactions' => $transactionObj['transactions']])->render();
            $return = [
                "status" => true,
                "message" => "",
                "transactions" => $transactionView
            ];
        } else {
            $return['status'] = false;
            $return['message'] = $transactionObj['message'] ?? 'An error occurred';
        }

        return response()->json($return);
    }
    public function provenincome(Request $request)
    {
        $userid = $request->input('pk');
        $value = $request->input('value');
        $name = $request->input('name');

        if (empty($userid) || empty($value)) {
            return response()->json(["status" => 'error', "message" => "Sorry, something missing"]);
        }

        $userIncome = UserIncome::where('user_id', $userid)->first();

        if ($name == 'statedIncome') {
            UserIncome::updateOrCreate(
                ['user_id' => $userid],
                ['income' => $value]
            );

            return response()->json(["status" => 'success', "message" => "Saved successfully"]);
        }

        UserIncome::updateOrCreate(
            ['user_id' => $userid],
            ['provenincome' => $value]
        );

        if ($userIncome && $userIncome->income <= $value) {
            VehicleReservation::updatePendingBooking($userid, 4);
        }

        return response()->json(["status" => 'success', "message" => "Saved successfully"]);
    }
    public function checkodometer(Request $request)
    {
        $vehicleid = $request->input('vehicleid');

        if (empty($vehicleid)) {
            return response()->json(["status" => 'error', "message" => "Sorry, something missing"]);
        }

        $passtime = (new Passtime())->getPasstimeMiles($vehicleid);

        if (!empty($passtime)) {
            $miles = data_get($passtime, 'miles');

            $return = [
                "status" => 'success',
                "message" => "Processed successfully",
                "html" => "Current odometer reading is " . $miles
            ];
        } else {
            $return = [
                "status" => 'error',
                "message" => "Sorry, GPS seems not providing status update. Please check GPS provider setting & vehicle serial number."
            ];
        }

        return response()->json($return);
    }
    public function checkStarterInterrupt(Request $request)
    {
        $vehicleid = $request->input('vehicleid');
        $orderid = $request->input('orderid');

        if (empty($vehicleid)) {
            return response()->json(["status" => 'error', "message" => "Sorry, something missing"]);
        }

        $vehicleData = Vehicle::select(['id', 'user_id', 'passtime_serialno'])
            ->with('csSetting:user_id,passtime_dealerid,passtime')
            ->find($vehicleid);

        if (!$vehicleData || empty($vehicleData->passtime_serialno)) {
            return response()->json(['status' => 'error', 'message' => 'Vehicle Passtime serial # not set']);
        }

        if (empty($vehicle->csSetting?->passtime)) {
            return response()->json(['status' => 'error', 'message' => "Vehicle Owner's GPS provider setting not set"]);
        }

        $gpsconfirmView = view('vehicle_reservations.elements.starterconfirm', [
            'vehicleid' => $vehicleid,
            'reservationid' => $orderid
        ])->render();

        return response()->json([
            "status" => 'success',
            "message" => "Saved successfully",
            "html" => $gpsconfirmView
        ]);
    }
    public function disableStaterInterrupt(Request $request)
    {
        $vehicleid = $request->input('vehicleid');
        $disable = $request->input('disable');
        $return = ["status" => false, "message" => "Sorry, something missing"];

        if (empty($vehicleid)) {
            return response()->json(["status" => false, "message" => "Sorry, something missing"]);
        }

        $vehicle = Vehicle::select(['id', 'user_id', 'passtime_serialno', 'autopi_unit_id', 'passtime_status'])
            ->with([
                'csSetting',
                'vehicleSetting'
            ])->find($vehicleid);

        if (!$vehicle || empty($vehicle->passtime_serialno)) {
            return response()->json($return);
        }

        if (empty($vehicle->csSetting?->passtime)) {
            $return['message'] = "Vehicle Owner's GPS provider setting not set";
            return response()->json($return);
        }

        $passtimeService = new Passtime();
        $vehiclePayload = $vehicle->toArray();

        if ($disable) {
            $return = $passtimeService->deActivateVehicle($vehiclePayload);
            if (!empty($return['status'])) {
                $vehicle->update(['passtime_status' => 0]);
                $return['status'] = true;
                return response()->json($return);
            }
        } else {
            $return = $passtimeService->ActivateVehicle($vehiclePayload);
            if (!empty($return['status'])) {
                $vehicle->update(['passtime_status' => 1]);
                $return['status'] = true;
                return response()->json($return);
            }
        }

        return response()->json($return);
    }
    public function staterInterruptWorks(Request $request)
    {
        $orderid = $request->input('orderid');

        if (!empty($orderid)) {
            VehicleReservation::where('id', $orderid)->update([
                'gps' => 1,
                'gps2' => 1
            ]);

            return response()->json([
                'status' => 'success',
                'message' => "Request processed successfully",
                'result' => []
            ]);
        }

        return response()->json(["status" => 'error', "message" => "Sorry, something missing"]);
    }
    public function changeVehicle(Request $request)
    {
        $admin = true;
        $orderid = $this->decodeId($request->input('orderid'));
        $booking = VehicleReservation::find($orderid);
        return view('vehicle_reservations.change_vehicle', compact('booking', 'admin'));
    }
    public function updateReservationVehicle(Request $request)
    {
        $return = $this->_updateReservationVehicle($request->all());
        return response()->json($return);
    }
    public function changeDatetime(Request $request)
    {
        $orderid = $this->decodeId($request->input('orderid'));
        $booking = VehicleReservation::find($orderid);
        return view('vehicle_reservations.change_datetime', compact('booking'));
    }
    public function updateDatetime(Request $request)
    {
        $return = $this->_updateDatetime($request->all());
        return response()->json($return);
    }
    public function getfarecalculations(Request $request)
    {
        $return = $this->_getfarecalculations($request->all());
        return response()->json($return);
    }
    public function changeStatus(Request $request)
    {
        $orderid = $this->decodeId($request->input('orderid'));
        $booking = VehicleReservation::find($orderid);
        $status = $this->commonService->getReservationStatus(true, true);
        return view('vehicle_reservations.change_status', compact('booking', 'status'));
    }
    public function changeSaveStatus(Request $request)
    {
        $return = $this->_changeSaveStatus($request->all());
        return response()->json($return);
    }
    public function singleload(Request $request)
    {
        $orderId = $request->input('orderid');
        $booking = VehicleReservation::with([
            'vehicle:id,msrp,vin_no,vehicle_name',
            'orderDepositRule:id,vehicle_reservation_id,downpayment',
            'renter:id,first_name,last_name'
        ])->find($orderId);

        return view('admin.vehicle_reservations.singleload', ['trip' => $booking]);
    }
    public function vehicleReservationLog(Request $request)
    {
        $return = $this->_vehicleReservationLog($request->all());
        return response()->json($return);
    }
    public function capturepayment(Request $request)
    {
        if (!$request->has('lease_id') || empty($request->input('lease_id'))) {
            return response()->json(['error' => 'Lease ID missing'], 400);
        }

        $leaseId = $this->decodeId($request->input('lease_id'));
        $allowedStatus = array_keys($this->commonService->getReservationStatus(true, true));
        $reserveData = VehicleReservation::with('vehicle')
            ->where('id', $leaseId)
            ->where('buy', 0)
            ->whereIn('status', $allowedStatus)
            ->first();

        if (!$reserveData) {
            return redirect()->to('/admin/vehicle_reservations/index');
        }

        $vehicle = $reserveData->vehicle;
        $orderDepositRule = OrderDepositRule::where('vehicle_reservation_id', $reserveData->id)->first();

        $csReservationPayments = CsReservationPayment::select('type', 'amount', 'txntype', 'id')
            ->where('reservation_id', $reserveData->id)
            ->whereIn('type', [1, 3])
            ->where('status', 1)
            ->get();

        $priceRulesAmt = (new DepositRule())->getPendingBookingFee($reserveData, $orderDepositRule);

        $startDate = Carbon::parse($reserveData->start_datetime, $reserveData->timezone)->format('m/d/Y');
        $endDate = Carbon::parse($reserveData->end_datetime, $reserveData->timezone)->format('m/d/Y');

        $rentalOpt = json_decode($orderDepositRule->rental_opt ?? '{}', true);
        $initialFeeOpt = json_decode($orderDepositRule->initial_fee_opt ?? '{}', true);
        $depositOpt = json_decode($orderDepositRule->deposit_opt ?? '{}', true);

        $paidRental = CsReservationPayment::getTotalRentalTax($reserveData->id);
        $paidInsurance = CsReservationPayment::getTotalInsurance($reserveData->id);
        $prepaidPlans = PrepaidPlan::where('reservation_id', $reserveData->id)->get();

        $chargeButton = $prepaidPlans->contains(function ($plan) {
            return $plan->status != 3;
        });

        $promo = (new PromoService())->getUserPromo($reserveData->renter_id);
        $paidDeposit = $csReservationPayments->where('type', 1)->sum('amount');
        $tax = $orderDepositRule->tax ?? 0;

        return view('admin.vehicle_reservations.capture_payment', compact(
            'reserveData',
            'vehicle',
            'orderDepositRule',
            'csReservationPayments',
            'priceRulesAmt',
            'startDate',
            'endDate',
            'rentalOpt',
            'initialFeeOpt',
            'depositOpt',
            'paidRental',
            'paidInsurance',
            'prepaidPlans',
            'chargeButton',
            'tax',
            'promo',
            'paidDeposit'
        ));
    }
    public function processcapturepayment(Request $request)
    {
        $return = ["status" => false, "message" => "Sorry, something went wrong"];

        if (!$request->has('orderid') || empty($request->input('orderid'))) {
            return response()->json($return);
        }

        $type = $request->input('type');
        $amt = $request->input('amt');
        $opt = $request->has('opt') ? json_decode($request->input('opt'), true) : [];
        $orderId = $this->decodeId($request->input('orderid'));
        $allowedStatus = array_keys($this->commonService->getReservationStatus(true, true));

        $reservation = VehicleReservation::with(['owner:id,currency', 'orderDepositRule:vehicle_reservation_id,insurance_payer'])
            ->where('id', $orderId)
            ->where('buy', 0)
            ->whereIn('status', $allowedStatus)
            ->first();

        if (!$reservation) {
            return redirect()->to('/admin/vehicle_reservations/index');
        }

        $paymentProcessor = new PaymentProcessor();

        if ($type == 4 && $amt > 0) {
            $return = $paymentProcessor->chargeInsuranceForVehicleReservation($amt, $reservation);
        }

        if ($type == 2 && $amt > 0) {
            $mergedData = array_merge($reservation->toArray(), $opt);
            $currency = data_get($reservation, 'owner.currency');

            $return = $paymentProcessor->chargeRentalForVehicleReservation($mergedData, $currency);
        }

        if ($type == 1 && $amt > 0) {
            $return = $paymentProcessor->chargeDepositForVehicleReservation($amt, $reservation);
        }

        return response()->json($return);
    }
    public function paymentcapturevehiclereservation(Request $request)
    {
        $return = ["status" => 'error', "message" => "Sorry, something went wrong", 'retrynew' => 0, 'paymentid' => 0];

        if (!$request->isMethod('post') || !$request->has('paymentid')) {
            return response()->json($return);
        }

        $paymentId = $request->input('paymentid');
        $payment = CsReservationPayment::where('id', $paymentId)
            ->where('txntype', 'P')
            ->where('status', 1)
            ->first();

        if (!$payment) {
            $return["message"] = "Sorry, payment transaction is already captured or not found";
            return response()->json($return);
        }

        $paymentProcessor = new PaymentProcessor();
        $result = $paymentProcessor->UberPaymentCaptureOnly($payment->toArray(), 'DIA initial fee ');

        if (data_get($result, 'status') !== 'success') {
            $result['retrynew'] = 1;
            $result['paymentid'] = $paymentId;
            return response()->json($result);
        }

        $payment->txntype = 'C';
        $payment->save();

        return response()->json(['status' => 'success', 'message' => 'Payment released successfully']);
    }
    public function recapturevehiclereservation(Request $request)
    {
        $return = ["status" => 'error', "message" => "Sorry, something went wrong", 'retrynew' => 0, 'paymentid' => 0];

        if (!$request->isMethod('post') || !$request->has('paymentid')) {
            return response()->json($return);
        }

        $paymentId = $request->input('paymentid');
        $payment = CsReservationPayment::with('vehicleReservation:id,renter_id')->find($paymentId);

        if (!$payment) {
            $return["message"] = "Sorry, payment transaction is not found";
            return response()->json($return);
        }

        $paymentProcessor = new PaymentProcessor();
        $statement = $payment->type == 1 ? 'DIA Initial Fee ' : 'DIA deposit Fee ';
        $renterId = data_get($payment, 'vehicleReservation.renter_id');

        $result = $paymentProcessor->PaymentAuthorizeOnly(
            $payment->amount,
            $renterId,
            $payment->type,
            $statement,
            true,
            $payment->currency
        );

        if (data_get($result, 'status') === 'success') {
            $newPayment = $payment->replicate();
            $newPayment->transaction_id = data_get($result, 'transaction_id');
            $newPayment->txntype = 'C';
            $newPayment->created_at = Carbon::now();
            $newPayment->save();

            $payment->status = 2;
            $payment->save();

            $result['message'] = "Amount captured successfully";
        }

        return response()->json($result);
    }
    public function insudoc(Request $request)
    {
        if (!$request->has('id') || empty($request->input('id'))) {
            return response()->json([
                'status' => false,
                'message' => 'Missing ID parameter.',
                'result' => []
            ]);
        }

        $id = $this->decodeId($request->input('id'));
        $return = $this->_insudoc($id);
        return response()->json($return);
    }
    public function changeinsurancepopup(Request $request)
    {
        $return = $this->_changeinsurancepopup($request->all());
        return response()->json($return);
    }
    public function changeinsurancesave(Request $request)
    {
        $return = $this->_changeinsurancesave($request->all());
        return response()->json($return);
    }
    public function generateAgrement(Request $request)
    {
        $id = $request->input('id');
        $returnData = $this->_getPendingBookingAgreement($id);
        return response()->json($returnData);
    }
    public function loadcancelblock(Request $request)
    {
        return $this->_loadcancelblock($request->all());
    }
    public function loadinsurancepopup(Request $request)
    {
        return $this->_loadinsurancepopup($request->all());
    }
    public function changeinsurancetypepopup(Request $request)
    {
        return $this->_changeInsuranceTypePopup($request->all());
    }
    public function saveinsurancepayer(Request $request)
    {
        return $this->_saveinsurancepayer($request->all());
    }
    public function goalrecalculate(Request $request, $id = null)
    {
        $id = $this->decodeId($id);
        $orderDepositRule = OrderDepositRule::findOrFail($id);
        $orderDepositRule->rent_opt = json_decode($orderDepositRule->rent_opt, true) ?? [];
        $orderDepositRule->initial_fee_opt = json_decode($orderDepositRule->initial_fee_opt, true) ?? [];
        $orderDepositRule->deposit_opt = json_decode($orderDepositRule->deposit_opt, true) ?? [];
        $orderDepositRule->duration_opt = json_decode($orderDepositRule->duration_opt, true) ?? [];
        $orderDepositRule->calculation = json_decode($orderDepositRule->calculation, true) ?? [];
        $orderDepositRule->goal = 'custom';
        $orderDepositRule->miles = floor(($orderDepositRule->miles * 365) / 12);

        $vehicleReservation = VehicleReservation::select('id', 'renter_id', 'vehicle_id', 'initial_discount', 'discount_desc')
            ->findOrFail($orderDepositRule->vehicle_reservation_id);

        $vehicleData = Vehicle::select('id', 'msrp', 'allowed_miles')
            ->findOrFail($vehicleReservation->vehicle_id);

        $baseMiles = $vehicleData->allowed_miles ? ceil($vehicleData->allowed_miles * 30) : 1000;
        $milesOptions = [];

        while ($baseMiles <= 15000) {
            $milesOptions[$baseMiles] = $baseMiles;
            $baseMiles += 500;
        }

        $vehicles = [
            'id' => $vehicleData->id,
            'miles_options' => $milesOptions
        ];

        return view('admin.vehicle_reservations.goalrecalculate', compact('orderDepositRule', 'vehicles', 'vehicleReservation'));
    }
    public function getVehicleDynamicFareMatrix(Request $request)
    {
        $offer = $request->input('VehicleOffer', []);
        return $this->_vehicleReservationVehicleDynamicFareMatrix($offer);
    }
    public function saveGoalRecalculation(Request $request)
    {
        $offerInput = $request->input('VehicleOffer', []);
        $jsonData = json_decode(data_get($offerInput, 'json', '{}'), true) ?? [];
        $offer = array_merge($offerInput, $jsonData);
        $id = data_get($offer, 'id');

        if (empty($id)) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, required input data are missing.',
                'result' => []
            ]);
        }

        $orderDepositRule = OrderDepositRule::findOrFail($id);
        $orderDepositRule->fill([
            'totalcost' => data_get($offer, 'totalcost'),
            'goal' => data_get($offer, 'goal'),
            'downpayment' => data_get($offer, 'downpayment'),
            'miles' => sprintf('%0.2f', (data_get($offer, 'miles', 0) / 30)),
            'insurance' => data_get($offer, 'insurance'),
            'emf' => data_get($offer, 'emf'),
            'total_program_cost' => data_get($offer, 'total_program_cost'),
            'rental' => data_get($offer, 'day_rent'),
            'num_of_days' => data_get($offer, 'days'),
            'tax' => data_get($offer, 'tax_rate'),
            'total_initial_fee' => data_get($offer, 'total_initial_fee'),
            'write_down_allocation' => data_get($offer, 'write_down_allocation'),
            'finance_allocation' => data_get($offer, 'finance_allocation'),
            'maintenance_allocation' => data_get($offer, 'maintenance_allocation'),
            'financing_total' => data_get($offer, 'financing_total'),
            'disposition_fee' => data_get($offer, 'disposition_fee'),
            'calculation' => data_get($offer, 'json'),
        ]);
        $orderDepositRule->save();

        if (data_get($offer, 'clear_promo') == 1) {
            if ($orderDepositRule->vehicle_reservation_id) {
                VehicleReservation::where('id', $orderDepositRule->vehicle_reservation_id)
                    ->update([
                        'initial_discount' => 0,
                        'discount_desc' => null
                    ]);
            }

            (new PromoService())->removePromoIdToUser();
        }

        return response()->json([
            'status' => true,
            'message' => 'Data updated successfully',
            'result' => []
        ]);
    }
    public function savemanualcalculation(Request $request)
    {
        $offer = $request->input('VehicleOffer', []);
        $id = data_get($offer, 'id');

        if (empty($id)) {
            return response()->json([
                'status' => false,
                'message' => 'Sorry, required input data are missing.',
                'result' => []
            ]);
        }

        $orderDepositRule = OrderDepositRule::findOrFail($id);
        $orderDepositRule->fill([
            'totalcost' => data_get($offer, 'calculation.totalcost'),
            'goal' => data_get($offer, 'calculation.goal'),
            'downpayment' => data_get($offer, 'calculation.downpayment'),
            'miles' => sprintf('%0.2f', data_get($offer, 'miles', 0)),
            'insurance' => data_get($offer, 'insurance'),
            'emf' => data_get($offer, 'emf'),
            'total_program_cost' => data_get($offer, 'calculation.total_program_cost'),
            'rental' => data_get($offer, 'calculation.rental'),
            'base_rent' => data_get($offer, 'calculation.base_dayrent'),
            'num_of_days' => data_get($offer, 'calculation.num_of_days'),
            'tax' => data_get($offer, 'calculation.tax_rate'),
            'initial_fee' => data_get($offer, 'calculation.initial_fee'),
            'total_initial_fee' => data_get($offer, 'calculation.initial_fee'),
            'write_down_allocation' => data_get($offer, 'calculation.write_down_allocation'),
            'finance_allocation' => data_get($offer, 'calculation.finance_allocation'),
            'maintenance_allocation' => data_get($offer, 'calculation.maintenance_allocation'),
            'financing_total' => data_get($offer, 'calculation.financing_total'),
            'disposition_fee' => data_get($offer, 'calculation.disposition_fee'),
            'calculation' => json_encode(data_get($offer, 'calculation', [])),
        ]);
        $orderDepositRule->save();

        return redirect()->back()->with('status', 'Data updated successfully');
    }
    public function loadstatuschecklist(Request $request)
    {
        $orderid = $this->decodeId($request->input('orderid'));
        $allowedStatus = array_keys($this->commonService->getReservationStatus(true, true));


        if (empty($orderid)) {
            return response()->json(["status" => false, "message" => "Sorry, something missing"]);
        }

        $bookingData = VehicleReservation::where('id', $orderid)
            ->whereIn('status', $allowedStatus)
            ->select('checklists')
            ->first();

        if (empty($bookingData)) {
            return response()->json(["status" => false, "message" => "Sorry, booking not found"]);
        }

        $bookingChecks = !empty($bookingData->checklists) ? json_decode($bookingData->checklists, true) : [];

        $loadstatuschecklist = view('vehicle_reservations._loadstatuschecklist', [
            'bookingchecks' => $bookingChecks,
            'orderid' => $orderid,
            'checklist' => $this->checklist
        ])->render();

        return response()->json([
            "status" => true,
            "message" => "loaded successfully",
            "html" => $loadstatuschecklist
        ]);
    }
    public function updatechecklist(Request $request)
    {
        if (!$request->isMethod('post')) {
            return response()->json(["status" => false, "message" => "Sorry, something missing"]);
        }

        $orderid = $request->input('pk');
        $key = $request->input('name');
        $value = $request->input('value');
        $allowedStatus = array_keys($this->commonService->getReservationStatus(true, true));

        if (empty($orderid)) {
            return response()->json(["status" => false, "message" => "Sorry, something missing"]);
        }

        $bookingData = VehicleReservation::where('id', $orderid)
            ->whereIn('status', $allowedStatus)
            ->select('id', 'checklists')
            ->first();

        if (empty($bookingData)) {
            return response()->json(["status" => false, "message" => "Sorry, booking not found"]);
        }

        $checklists = !empty($bookingData->checklists) ? json_decode($bookingData->checklists, true) : [];
        $checklists = array_merge($checklists, [$key => $value]);

        $bookingData->update([
            'checklists' => json_encode($checklists)
        ]);

        return response()->json(["status" => true, "message" => "saved successfully"]);
    }
    public function download_vehicle_images(Request $request)
    {
        if (!$request->isMethod('post')) {
            return response()->json(["status" => false, "message" => "Sorry, something missing"]);
        }

        $vehicleid = $this->decodeId($request->input('vehicleid'));

        if (empty($vehicleid)) {
            return response()->json(["status" => false, "message" => "Sorry, something missing"]);
        }

        $this->_CopyVehicleImageFromRemote($vehicleid);

        return response()->json(["status" => true, "message" => "saved successfully"]);
    }
    public function vehicleSellingOpions(Request $request)
    {
        $orderid = $this->decodeId($request->input('orderid'));
        $booking = VehicleReservation::select('id', 'vehicle_id')
            ->with([
                'orderDepositRule:id,selling_option,vehicle_reservation_id',
                'depositRule:vehicle_id,free_two_move'
            ])->find($orderid);

        $free_two_move = [];
        $selling_option = [];

        if ($booking) {
            if (!empty($booking->depositRule->free_two_move)) {
                $free_two_move = json_decode($booking->depositRule->free_two_move, true);
            }
            if (!empty($booking->orderDepositRule->selling_option)) {
                $selling_option = json_decode($booking->orderDepositRule->selling_option, true);
            }
        }

        $is_admin = 1;

        return view('vehicle_reservations._vehicle_selling_opions', compact('booking', 'free_two_move', 'is_admin', 'selling_option'));
    }
    public function vehicleSellingOpionAgreeToSell(Request $request)
    {
        $orderid = $request->input('orderid');
        $booking = OrderDepositRule::select('id', 'selling_option')->find($orderid);

        if ($booking && !empty($booking->selling_option)) {
            $booking->selling_option = json_decode($booking->selling_option, true);
        }

        return view('vehicle_reservations._vehicle_selling_opion_agree_to_sell', compact('booking'));
    }
    public function saveVehicleAgreeToSell(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json(["status" => false, "message" => "Invalid Request Type"]);
        }

        $return = ["status" => false, "message" => "Sorry, something went wrong."];
        $orderid = $request->input('OrderDepositRule.id');
        $booking = OrderDepositRule::select('id', 'selling_option', 'vehicle_reservation_id')->find($orderid);

        if (!$booking) {
            return response()->json(["status" => false, "message" => "Order details not found."]);
        }

        $sellingOptionData = !empty($booking->selling_option) ? json_decode($booking->selling_option, true) : [];
        $allowedSize = $this->commonService->FileSizeInBytes(ini_get('upload_max_filesize'));

        if ($request->hasFile('OrderDepositRule.selling_option.invoice')) {
            $invoiceFile = $request->file('OrderDepositRule.selling_option.invoice');

            if ($invoiceFile->getSize() > $allowedSize) {
                $return['message'] = 'Sorry invoice image could not be uploaded, it must be in proper size';
                return response()->json($return);
            }

            if (in_array(strtolower($invoiceFile->getClientOriginalExtension()), $this->allowedExtensions)) {
                $filename = 'vehibooking_' . $orderid . '_invoice.' . $invoiceFile->getClientOriginalExtension();
                $invoiceFile->move(public_path('img/custom/vehicle_photo'), $filename);
                $sellingOptionData['invoice'] = $filename;
            }
        }

        if ($request->hasFile('OrderDepositRule.selling_option.buyer_order')) {
            $buyerOrderFile = $request->file('OrderDepositRule.selling_option.buyer_order');

            if ($buyerOrderFile->getSize() > $allowedSize) {
                $return['message'] = 'Sorry buyer order image could not be uploaded, it must be in proper size';
                return response()->json($return);
            }

            if (in_array(strtolower($buyerOrderFile->getClientOriginalExtension()), $this->allowedExtensions)) {
                $filename = 'vehibooking_' . $orderid . '_buyer_order.' . $buyerOrderFile->getClientOriginalExtension();
                $buyerOrderFile->move(public_path('img/custom/vehicle_photo'), $filename);
                $sellingOptionData['buyer_order'] = $filename;
            }
        }

        $return['status'] = true;
        VehicleReservation::where('id', $booking->vehicle_reservation_id)->update(['ready_for_dealer' => 2]);

        $booking->update([
            'selling_option' => json_encode($sellingOptionData)
        ]);

        (new Emailnotify())->sendEmailToDealerForVehicleSellRequest($booking->vehicle_reservation_id);

        return response()->json($return);
    }
    public function vehicleFree2moveAgreement(Request $request)
    {
        $reference = $request->input('reference');

        $response = [
            "status" => false,
            "message" => "Sorry, something went wrong.",
            "agreement" => []
        ];

        if (!empty($reference)) {
            $response = Free2MoveService::_callAgreementApi(['reference' => $reference]);
        }

        return response()->json($response);
    }
    public function pushToDealer($id = null, $flag = 0)
    {
        $decodedId = $this->decodeId($id);

        if (!empty($decodedId)) {
            $booking = VehicleReservation::select('id', 'ready_for_dealer')
                ->where('id', $decodedId)
                ->first();

            if ($booking) {
                $booking->update(['ready_for_dealer' => $flag]);
            }

            $message = "Booking has been " . ($flag == 1 ? "pushed to dealer" : "removed from dealer list") . " successfully";
            session()->flash('success', $message);

            $emailNotify = new Emailnotify();
            $emailNotify->sendEmailToPushToDealer($booking->id, $flag);

        } else {
            session()->flash('error', "Sorry, you can't perform this action now");
        }

        return redirect()->back();
    }
    public function saveVehicleSellingOption(Request $request)
    {
        return $this->_saveVehicleSellingOption($request->all());
    }
}

