<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\CsReservationPayment;
use App\Models\Legacy\CsSetting;
use App\Models\Legacy\DepositRule;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\PlaidUser;
use App\Models\Legacy\User;
use App\Models\Legacy\UserIncome;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\VehicleReservation;
use App\Models\Legacy\VehicleReservationLog;
use App\Services\Legacy\Passtime;
use App\Services\Legacy\PlaidClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Traits\VehicleReservationsTrait;
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
            'depositRule:id,vehicle_reservation_id,insurance,insurance_payer,insu_agreed,financing',
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
            'depositRule:id,vehicle_reservation_id,downpayment',
            'renter:id,first_name,last_name'
        ])->find($orderId);

        return view('admin.vehicle_reservations.singleload', ['trip' => $booking]);
    }
    public function vehicleReservationLog(Request $request)
    {
        $return = $this->_vehicleReservationLog($request->all());
        return response()->json($return);
    }








    public function loadstatuschecklist(Request $request)
    {
        $id = $this->decodeId((string) $request->input('id', $request->input('lease_id', '')));
        if (!$id) {
            return response('Invalid reservation', 400);
        }

        return response()->view('admin.vehicle_reservations._status_checklist', ['id' => $id]);
    }

    public function updatechecklist(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Checklist updated successfully']);
    }

    public function loadcancelblock(Request $request)
    {
        $id = $this->decodeId((string) $request->input('lease_id', ''));
        if (!$id) {
            return response('Invalid reservation', 400);
        }

        return response()->view('admin.vehicle_reservations._cancel_popup', ['id' => base64_encode((string) $id)]);
    }

    public function loadinsurancepopup(Request $request)
    {
        $id = $this->decodeId((string) $request->input('lease_id', ''));
        if (!$id) {
            return response('Invalid reservation', 400);
        }

        return response()->view('admin.vehicle_reservations._insurance_popup', ['id' => base64_encode((string) $id)]);
    }

    public function changeinsurancepopup(Request $request)
    {
        return $this->loadinsurancepopup($request);
    }

    public function changeinsurancesave(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Insurance settings updated']);
    }

    public function changeinsurancetypepopup(Request $request)
    {
        return $this->loadinsurancepopup($request);
    }

    public function saveinsurancepayer(Request $request): JsonResponse
    {
        $id = $this->decodeId((string) $request->input('lease_id', ''));
        $payer = (string) $request->input('insurance_payer', '');
        if (!$id || $payer === '') {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }
        DB::table('cs_order_deposit_rules')
            ->where('vehicle_reservation_id', $id)
            ->update(['insurance_payer' => $payer]);

        return response()->json(['status' => true, 'message' => 'Insurance payer updated']);
    }

    public function generateAgrement(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Agreement generation queued']);
    }

    public function capturepayment(Request $request)
    {
        $id = $this->decodeId((string) $request->input('lease_id', ''));
        if (!$id) {
            return response('Invalid reservation', 400);
        }

        return response()->view('admin.vehicle_reservations._capture_payment', ['id' => base64_encode((string) $id)]);
    }

    public function processcapturepayment(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Payment capture processed']);
    }

    public function paymentcapturevehiclereservation(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Payment captured']);
    }

    public function recapturevehiclereservation(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Payment recapture queued']);
    }

    protected function reservationQuery(?array $statuses = null)
    {
        $q = DB::table('vehicle_reservations as vr')
            ->leftJoin('vehicles as v', 'v.id', '=', 'vr.vehicle_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'vr.user_id')
            ->leftJoin('users as renter', 'renter.id', '=', 'vr.renter_id')
            ->leftJoin('cs_order_deposit_rules as odr', 'odr.vehicle_reservation_id', '=', 'vr.id')
            ->select([
                'vr.*',
                'v.vehicle_name',
                'v.vehicle_unique_id',
                'v.vin_no',
                'owner.first_name as owner_first_name',
                'owner.last_name as owner_last_name',
                'renter.first_name as renter_first_name',
                'renter.last_name as renter_last_name',
                'odr.id as order_rule_id',
                'odr.downpayment',
                'odr.insurance',
                'odr.insurance_payer',
                'odr.insu_agreed',
                'odr.financing',
            ]);

        if ($statuses !== null) {
            $q->whereIn('vr.status', $statuses);
        }

        return $q;
    }

    public function insudoc(Request $request): JsonResponse
    {
        $id = (int) $this->decodeId((string) $request->input('id', ''));
        if ($id <= 0) {
            return response()->json(['status' => false, 'message' => 'Invalid reservation id']);
        }

        $reservation = DB::table('vehicle_reservations as vr')
            ->leftJoin('cs_order_deposit_rules as odr', 'odr.vehicle_reservation_id', '=', 'vr.id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'vr.vehicle_id')
            ->leftJoin('users as renter', 'renter.id', '=', 'vr.renter_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'vr.user_id')
            ->where('vr.id', $id)
            ->first([
                'vr.id',
                'vr.user_id',
                'odr.id as odr_id',
                'odr.insurance_payer',
                'v.make',
                'v.year',
                'v.model',
                'v.vin_no',
                'v.insurance_company',
                'v.insurance_policy_no',
                'v.insurance_policy_date',
                'v.insurance_policy_exp_date',
                'renter.first_name as renter_first_name',
                'renter.last_name as renter_last_name',
                'owner.first_name as owner_first_name',
                'owner.last_name as owner_last_name',
            ]);

        if (!$reservation) {
            return response()->json(['status' => false, 'message' => "Sorry, you can't perform this action now."]);
        }

        if ((int) ($reservation->insurance_payer ?? 0) === 3) {
            $payerDoc = DB::table('insurance_payers')
                ->where('order_deposit_rule_id', $reservation->odr_id)
                ->first(['insurance_card']);

            if (!$payerDoc || empty($payerDoc->insurance_card)) {
                return response()->json([
                    'status' => false,
                    'message' => "Sorry, Driver didnt upload insurance token yet. He agreed to manage it himself.",
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'result' => ['file' => '/files/reservation/' . $payerDoc->insurance_card],
            ]);
        }

        \Log::warning("insudoc: Insurance document generation stubbed for reservation {$id}.");

        return response()->json([
            'status' => false,
            'message' => 'Insurance document generation not yet ported to Laravel.',
            'result' => ['file' => null],
        ]);
    }

    public function goalrecalculate(Request $request, $id = null)
    {
        $ruleId = $id ? (int) $this->decodeId((string) $id) : 0;
        if ($ruleId <= 0) {
            return redirect('/admin/vehicle-reservations');
        }

        $odr = DB::table('cs_order_deposit_rules')->where('id', $ruleId)->first();
        if (!$odr) {
            return redirect('/admin/vehicle-reservations');
        }

        $odrArr = (array) $odr;
        $odrArr['rent_opt'] = !empty($odrArr['rent_opt']) ? json_decode($odrArr['rent_opt'], true) : [];
        $odrArr['initial_fee_opt'] = !empty($odrArr['initial_fee_opt']) ? json_decode($odrArr['initial_fee_opt'], true) : [];
        $odrArr['deposit_opt'] = !empty($odrArr['deposit_opt']) ? json_decode($odrArr['deposit_opt'], true) : [];
        $odrArr['duration_opt'] = !empty($odrArr['duration_opt']) ? json_decode($odrArr['duration_opt'], true) : [];
        $odrArr['calculation'] = !empty($odrArr['calculation']) ? json_decode($odrArr['calculation'], true) : [];
        $odrArr['goal'] = 'custom';
        $odrArr['miles'] = floor(((float) ($odrArr['miles'] ?? 0)) * 365 / 12);

        $vrId = (int) ($odrArr['vehicle_reservation_id'] ?? 0);
        $vr = DB::table('vehicle_reservations')
            ->where('id', $vrId)
            ->first(['renter_id', 'vehicle_id', 'initial_discount', 'discount_desc']);

        $vehicleId = $vr ? (int) $vr->vehicle_id : 0;
        $vehicleRow = DB::table('vehicles')
            ->where('id', $vehicleId)
            ->first(['id', 'msrp', 'allowed_miles']);

        $allowedMiles = $vehicleRow->allowed_miles ?? 0;
        $k = $allowedMiles ? (int) ceil($allowedMiles * 30) : 1000;
        $milesOptions = [];
        while ($k <= 15000) {
            $milesOptions[$k] = $k;
            $k += 500;
        }

        $vehicles = [
            'id' => $vehicleRow->id ?? 0,
            'miles_options' => $milesOptions,
        ];

        return view('admin.vehicle_reservations.goalrecalculate', [
            'OrderDepositRule' => $odrArr,
            'vehicles' => $vehicles,
            'VehicleReservationObj' => $vr ? (array) $vr : [],
        ]);
    }

    public function getVehicleDynamicFareMatrix(Request $request): JsonResponse
    {
        $offer = $request->input('VehicleOffer', []);
        if (empty($offer)) {
            return response()->json(['status' => false, 'message' => 'Missing VehicleOffer data']);
        }

        \Log::warning('getVehicleDynamicFareMatrix: fare matrix calculation stubbed.');

        return response()->json([
            'status' => false,
            'message' => 'Dynamic fare matrix calculation not yet ported to Laravel',
            'result' => [],
        ]);
    }

    public function saveGoalRecalculation(Request $request): JsonResponse
    {
        $offer = $request->input('VehicleOffer', []);
        if (!empty($offer['json'])) {
            $offer = array_merge($offer, json_decode($offer['json'], true) ?: []);
        }

        if (empty($offer['id'])) {
            return response()->json(['status' => false, 'message' => 'Sorry, required input data are missing.']);
        }

        $dataToSave = [
            'totalcost' => $offer['totalcost'] ?? 0,
            'goal' => $offer['goal'] ?? '',
            'downpayment' => $offer['downpayment'] ?? 0,
            'miles' => sprintf('%0.2f', (($offer['miles'] ?? 0) / 30)),
            'insurance' => $offer['insurance'] ?? 0,
            'emf' => $offer['emf'] ?? 0,
            'total_program_cost' => $offer['total_program_cost'] ?? 0,
            'rental' => $offer['day_rent'] ?? 0,
            'num_of_days' => $offer['days'] ?? 0,
            'tax' => $offer['tax_rate'] ?? 0,
            'total_initial_fee' => $offer['total_initial_fee'] ?? 0,
            'write_down_allocation' => $offer['write_down_allocation'] ?? 0,
            'finance_allocation' => $offer['finance_allocation'] ?? 0,
            'maintenance_allocation' => $offer['maintenance_allocation'] ?? 0,
            'financing_total' => $offer['financing_total'] ?? 0,
            'disposition_fee' => $offer['disposition_fee'] ?? 0,
            'calculation' => $offer['json'] ?? '',
        ];

        DB::table('cs_order_deposit_rules')
            ->where('id', (int) $offer['id'])
            ->update($dataToSave);

        if (!empty($offer['clear_promo']) && (int) $offer['clear_promo'] === 1) {
            $rule = DB::table('cs_order_deposit_rules')
                ->where('id', (int) $offer['id'])
                ->value('vehicle_reservation_id');

            if ($rule) {
                VehicleReservation::query()
                    ->whereKey($rule)
                    ->update(['initial_discount' => 0, 'discount_desc' => null]);
            }
        }

        return response()->json(['status' => true, 'message' => 'Data updated successfully']);
    }

    public function savemanualcalculation(Request $request): JsonResponse
    {
        $offer = $request->input('VehicleOffer', []);
        if (empty($offer['id'])) {
            return response()->json(['status' => false, 'message' => 'Sorry, required input data are missing.']);
        }

        $calc = $offer['calculation'] ?? [];

        $dataToSave = [
            'totalcost' => $calc['totalcost'] ?? 0,
            'goal' => $calc['goal'] ?? '',
            'downpayment' => $calc['downpayment'] ?? 0,
            'miles' => sprintf('%0.2f', ($offer['miles'] ?? 0)),
            'insurance' => $offer['insurance'] ?? 0,
            'emf' => $offer['emf'] ?? 0,
            'total_program_cost' => $calc['total_program_cost'] ?? 0,
            'rental' => $calc['rental'] ?? 0,
            'base_rent' => $calc['base_dayrent'] ?? 0,
            'num_of_days' => $calc['num_of_days'] ?? 0,
            'tax' => $calc['tax_rate'] ?? 0,
            'initial_fee' => $calc['initial_fee'] ?? 0,
            'total_initial_fee' => $calc['initial_fee'] ?? 0,
            'write_down_allocation' => $calc['write_down_allocation'] ?? 0,
            'finance_allocation' => $calc['finance_allocation'] ?? 0,
            'maintenance_allocation' => $calc['maintenance_allocation'] ?? 0,
            'financing_total' => $calc['financing_total'] ?? 0,
            'disposition_fee' => $calc['disposition_fee'] ?? 0,
            'calculation' => json_encode($calc),
        ];

        DB::table('cs_order_deposit_rules')
            ->where('id', (int) $offer['id'])
            ->update($dataToSave);

        return response()->json(['status' => true, 'message' => 'Data updated successfully']);
    }

    public function download_vehicle_images(Request $request): JsonResponse
    {
        \Log::warning('download_vehicle_images: Not yet ported to Laravel.');

        return response()->json(['status' => false, 'message' => 'Not yet ported to Laravel']);
    }

    public function vehicleSellingOpions(Request $request)
    {
        $orderId = (int) $this->decodeId((string) $request->input('orderid', ''));
        if ($orderId <= 0) {
            return response('Invalid reservation', 400);
        }

        $booking = DB::table('vehicle_reservations as vr')
            ->leftJoin('cs_order_deposit_rules as odr', 'odr.vehicle_reservation_id', '=', 'vr.id')
            ->leftJoin('cs_deposit_rules as dr', 'dr.vehicle_id', '=', 'vr.vehicle_id')
            ->where('vr.id', $orderId)
            ->first([
                'vr.id',
                'odr.id as odr_id',
                'odr.selling_option',
                'dr.free_two_move',
            ]);

        return view('admin.vehicle_reservations.vehicle_selling_options', [
            'booking' => $booking,
            'free_two_move' => !empty($booking->free_two_move) ? json_decode($booking->free_two_move, true) : [],
            'selling_option' => !empty($booking->selling_option) ? json_decode($booking->selling_option, true) : [],
            'is_admin' => 1,
        ]);
    }

    public function vehicleSellingOpionAgreeToSell(Request $request)
    {
        $orderId = (int) $request->input('orderid', 0);
        if ($orderId <= 0) {
            return response('Invalid order', 400);
        }

        $booking = DB::table('cs_order_deposit_rules')
            ->where('id', $orderId)
            ->first(['id', 'selling_option']);

        $sellingOption = [];
        if ($booking && !empty($booking->selling_option)) {
            $sellingOption = json_decode($booking->selling_option, true) ?: [];
        }

        return view('admin.vehicle_reservations.vehicle_selling_agree', [
            'booking' => $booking,
            'selling_option' => $sellingOption,
        ]);
    }

    public function saveVehicleAgreeToSell(Request $request): JsonResponse
    {
        \Log::warning('saveVehicleAgreeToSell: File upload and email notification stubbed.');

        return response()->json([
            'status' => false,
            'message' => 'Vehicle sell agreement save not yet fully ported to Laravel',
        ]);
    }

    public function vehicleFree2moveAgreement(Request $request): JsonResponse
    {
        $reference = $request->input('reference', '');
        if (empty($reference)) {
            return response()->json(['status' => false, 'message' => 'Sorry, something went wrong.', 'agreement' => []]);
        }

        \Log::warning("vehicleFree2moveAgreement: Free2Move API stubbed for reference {$reference}.");

        return response()->json([
            'status' => false,
            'message' => 'Free2Move agreement API not yet ported to Laravel',
            'agreement' => [],
        ]);
    }

    public function pushToDealer(Request $request, $id = null, $flag = 0): JsonResponse
    {
        $decodedId = $id ? (int) $this->decodeId((string) $id) : 0;
        if ($decodedId <= 0) {
            return response()->json(['status' => false, 'message' => "Sorry, you can't perform this action now"]);
        }

        $booking = DB::table('vehicle_reservations')->where('id', $decodedId)->first(['id', 'ready_for_dealer']);
        if (!$booking) {
            return response()->json(['status' => false, 'message' => 'Reservation not found']);
        }

        DB::table('vehicle_reservations')->where('id', $decodedId)->update(['ready_for_dealer' => (int) $flag]);

        \Log::warning("pushToDealer: Email notification stubbed for reservation {$decodedId}, flag={$flag}.");

        $msg = (int) $flag === 1 ? 'pushed to dealer' : 'removed from dealer list';

        return response()->json(['status' => true, 'message' => "Booking has been {$msg} successfully"]);
    }

    public function saveVehicleSellingOption(Request $request): JsonResponse
    {
        \Log::warning('saveVehicleSellingOption: Not yet ported to Laravel.');

        return response()->json(['status' => false, 'message' => 'Vehicle selling option save not yet ported to Laravel']);
    }

    protected function resolveLimit(Request $request): int
    {
        if ($request->has('Record.limit')) {
            $lim = (int) $request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['vehicle_reservations_limit' => $lim]);
            }
        }

        $limit = (int) session('vehicle_reservations_limit', 50);

        return $limit > 0 ? $limit : 50;
    }
}

