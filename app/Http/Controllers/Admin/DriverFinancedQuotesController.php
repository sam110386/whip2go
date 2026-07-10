<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\DriverFinancedInsuranceQuote;
use App\Models\Legacy\InsuranceProvider;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\VehicleReservation;
use App\Services\Legacy\IntercomClient;
use Illuminate\Http\Request;

class DriverFinancedQuotesController extends LegacyAppController
{
    private array $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

    public function popup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $recordid = $request->input('recordid', '');
        $id = $request->input('id', '');
        $myModal = $request->input('model', 'myModal');
        $quoteData = [];

        if (empty($recordid) && !empty($id)) {
            $quoteData = DriverFinancedInsuranceQuote::find($id);
            $recordid = $quoteData->order_id ?? '';
        }

        if (empty($id) && !empty($recordid)) {
            $quoteData = DriverFinancedInsuranceQuote::where('order_id', $recordid)->first();
        }

        $creditCard = !empty($quoteData->credit_card) ? json_decode($quoteData->credit_card, true) : [];
        $providerAccount = !empty($quoteData->provider_account) ? json_decode($quoteData->provider_account, true) : [];
        $quotes = !empty($quoteData->quote) ? json_decode($quoteData->quote, true) : [];
        $providers = InsuranceProvider::where('status', 1)->get()->toArray();

        $orderDepositRuleObj = OrderDepositRule::select('id', 'insurance_payer', 'vehicle_reservation_id')
            ->where('vehicle_reservation_id', $recordid)
            ->with('reservation:id,renter_id')
            ->first();

        $orderandusers = '';
        if ($orderDepositRuleObj && $orderDepositRuleObj->reservation) {
            $orderandusers = base64_encode($orderDepositRuleObj->reservation->id . '|' . $orderDepositRuleObj->reservation->renter_id);
        }

        $viewData = compact('recordid', 'myModal', 'providers', 'quotes', 'orderDepositRuleObj', 'orderandusers', 'quoteData', 'creditCard', 'providerAccount');

        if (!empty($id)) {
            $html = view('admin.insurance.driver_financed_quotes.popup', $viewData)->render();
            return response()->json(["status" => true, "message" => "", 'recordid' => $recordid, "html" => $html]);
        }

        return view('admin.insurance.driver_financed_quotes.popup', $viewData);
    }
    public function save(Request $request)
    {
        if ($this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $return = [
            'status' => false,
            "message" => "Sorry, your request is not valid"
        ];

        if ($request->ajax() && $request->isMethod('post')) {
            $dataToSave = $request->all();
            $isApproved = $request->input('approve', false);
            $isPolicy = $request->input('policy', false);

            if (!$isApproved && !$isPolicy) {
                $dataToSave['DriverFinancedInsuranceQuote']['quote_approved'] = null;
            }
            $recordid = $dataToSave['DriverFinancedInsuranceQuote']['order_id'];

            $quoteRow = $dataToSave['DriverFinancedInsuranceQuote'];
            DriverFinancedInsuranceQuote::updateOrCreate(
                ['id' => $quoteRow['id'] ?? null],
                array_filter($quoteRow, fn($v) => $v !== null)
            );

            if ($isApproved) {
                $vhicleReservationObj = VehicleReservation::with('renter')->find($recordid);

                if ($vhicleReservationObj && $vhicleReservationObj->renter) {
                    $url = config('app.url') . '/insurance/driver_financed_docusign/signDocument/' . base64_encode($vhicleReservationObj->id . '|' . $vhicleReservationObj->renter_id);
                    try {
                        (new IntercomClient())->sendEvent($vhicleReservationObj->renter->toArray(), [
                            'docusign_url' => $url,
                            'booking' => $recordid,
                            'user' => $vhicleReservationObj->renter->first_name . ' ' . $vhicleReservationObj->renter->last_name,
                        ], "insurance_quote_approved");
                    } catch (\Exception $e) {
                        // Intercom event failed silently
                    }
                }
            }

            $return = [
                'status' => true,
                "message" => "Record has been updated successfully"
            ];
        }

        return response()->json($return);
    }
    public function virtaulcard(Request $request)
    {
        if ($this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $return = ['status' => false, "message" => "Sorry, your request is not valid"];

        if ($request->ajax() && $request->isMethod('post')) {
            $dataToSave = $request->input('DriverFinancedCreditCard');
            $toSave = ['id' => $dataToSave['id'], 'order_id' => $dataToSave['order_id']];
            unset($dataToSave['id'], $dataToSave['order_id']);
            $creditCardJson = json_encode($dataToSave);

            DriverFinancedInsuranceQuote::where('id', $toSave['id'])
                ->update(['credit_card' => $creditCardJson]);

            $return = ['status' => true, "message" => "CC details saved successfully"];

            if (!empty($creditCardJson)) {
                $vhicleReservationObj = VehicleReservation::with('renter')->find($dataToSave['order_id'] ?? $toSave['order_id']);

                if ($vhicleReservationObj && $vhicleReservationObj->renter) {
                    try {
                        (new IntercomClient())->sendEvent($vhicleReservationObj->renter->toArray(), [
                            'booking' => $toSave['order_id']
                        ], "insurance_quote_virtualcard_added");
                    } catch (\Exception $e) {
                        // Intercom event failed silently
                    }
                }
            }
        }
        return response()->json($return);
    }
    public function deletevirtaulcard(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $return = ['status' => false, "message" => "Sorry, your request is not valid"];

        if ($request->ajax() && $request->isMethod('post')) {
            $orderid = $request->input('orderid');
            DriverFinancedInsuranceQuote::where('order_id', $orderid)
                ->update(['credit_card' => null]);
            $return = ['status' => true, "message" => "CC details cleaned successfully"];
        }

        return response()->json($return);
    }
    public function saveImage(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $type = $request->input('type');
        $id = $request->input('id');
        $return = $this->handleUpload($request->file($type), $id, $type);
        return response()->json($return);
    }
    private function handleUpload($file, $id, string $filetype)
    {
        if (!$file || !$file->isValid()) {
            return ['error' => 'No files were uploaded.'];
        }
        if ($file->getSize() == 0) {
            return ['error' => 'File is empty.'];
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx'])) {
            return ['error' => 'File has an invalid extension, it should be one of jpeg, jpg, png, pdf, doc, docx.'];
        }

        $filename = $filetype . '_' . $id . '.' . $ext;
        $destination = public_path('files/reservation');
        $file->move($destination, $filename);

        DriverFinancedInsuranceQuote::updateOrCreate(
            ['order_id' => $id],
            [$filetype => $filename]
        );

        return ['success' => true];
    }
}
