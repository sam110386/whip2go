<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\DriverFinancedInsuranceQuote;
use App\Models\Legacy\InsuranceProvider;
use App\Models\Legacy\OrderDepositRule;
use App\Models\Legacy\VehicleReservation;
use App\Services\Legacy\IntercomClient;
use Illuminate\Http\Request;

class DiaFleetBackupQuotesController extends LegacyAppController
{
    private array $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    private int $imageSize = 2097152;

    public function popup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $recordid = $request->input('recordid', '');
        $id = $request->input('id', '');
        $myModal = $request->input('model', 'myModal');

        $quote = null;

        if (empty($recordid) && !empty($id)) {
            $quote = DriverFinancedInsuranceQuote::find($id);
            $recordid = $quote ? $quote->order_id : '';
        }

        if (empty($id) && !empty($recordid)) {
            $quote = DriverFinancedInsuranceQuote::where('order_id', $recordid)->first();
        }

        $creditCard = !empty($quote->credit_card) ? json_decode($quote->credit_card, true) : [];
        $providerAccount = !empty($quote->provider_account) ? json_decode($quote->provider_account, true) : [];
        $quotes = !empty($quote->quote) ? json_decode($quote->quote, true) : [];
        $providers = InsuranceProvider::where('status', 1)->get();

        $orderDepositRuleObj = OrderDepositRule::select('id', 'insurance_payer', 'vehicle_reservation_id')
            ->with([
                'reservation:id,renter_id',
                'axleStatus'
            ])
            ->where('vehicle_reservation_id', $recordid)
            ->first();

        $orderandusers = '';

        if ($orderDepositRuleObj) {
            $orderandusers = $this->decodeId($orderDepositRuleObj?->reservation?->id . '|' . $orderDepositRuleObj?->reservation?->renter_id);
        }

        $viewData = compact('recordid', 'myModal', 'providers', 'quotes', 'orderDepositRuleObj', 'orderandusers', 'quote', 'creditCard', 'providerAccount');

        if (!empty($id)) {
            $html = view('admin.insurance.dia_fleet_backup_quotes.popup', $viewData)->render();
            return response()->json([
                "status" => true,
                "message" => "",
                "recordid" => $recordid,
                "html" => $html
            ]);
        }

        return view('admin.insurance.dia_fleet_backup_quotes.popup', $viewData);
    }
    public function save(Request $request)
    {
        if ($this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        if (!$request->isMethod('post') && !$request->isMethod('put')) {
            return response()->json(['status' => false, "message" => "Sorry, your request is not valid"], 400);
        }

        $quoteData = $request->input('DriverFinancedInsuranceQuote', []);

        $isApproved = $request->input('approve', false);
        $isPolicy = $request->input('policy', false);

        if (!$isApproved && !$isPolicy) {
            $quoteData['quote_approved'] = null;
        }

        $recordid = $quoteData['order_id'];

        DriverFinancedInsuranceQuote::updateOrCreate(
            ['order_id' => $recordid],
            $quoteData
        );

        if ($isApproved) {
            $vehicleReservationObj = VehicleReservation::with('renter')->find($recordid);

            if ($vehicleReservationObj && $vehicleReservationObj->renter) {
                $url = url('insurance/dia_fleet_backup_docusign/signDocument/' . base64_encode($vehicleReservationObj->id . '|' . $vehicleReservationObj->renter_id));

                (new IntercomClient())->createEvents([
                    "event_name" => "insurance_quote_approved",
                    "created_at" => time(),
                    "external_id" => $vehicleReservationObj->renter_id,
                    "user_id" => $vehicleReservationObj->renter_id,
                    "metadata" => [
                        'docusign_url' => $url,
                        'booking' => $recordid,
                        'user' => $vehicleReservationObj->renter->first_name . ' ' . $vehicleReservationObj->renter->last_name
                    ]
                ]);
            }
        }

        return response()->json(['status' => true, "message" => "Record has been updated successfully"]);
    }
    public function saveImage(Request $request)
    {
        if ($this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $fileType = $request->input('type');
        $id = $request->input('id');

        if (!$request->hasFile($fileType)) {
            return response()->json(['error' => 'No files were uploaded.'], 400);
        }

        $file = $request->file($fileType);
        $return = $this->handleUpload($file, $id, $fileType);
        return response()->json($return);
    }
    private function handleUpload($file, $id, string $filetype)
    {
        if ($file->getSize() == 0) {
            return ['error' => 'File is empty.'];
        }

        if ($file->getSize() > $this->imageSize) {
            return ['error' => 'File is too large.', 'preventRetry' => true];
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $this->allowedExtensions)) {
            $these = implode(', ', $this->allowedExtensions);
            return ['error' => 'File has an invalid extension, it should be one of ' . $these . '.'];
        }

        $filename = $filetype . '_' . $id . '.' . $extension;

        try {
            $file->move(public_path('files/reservation'), $filename);

            $exists = DriverFinancedInsuranceQuote::where('order_id', $id)->first();

            DriverFinancedInsuranceQuote::updateOrCreate(
                ['order_id' => $id],
                [
                    $filetype => $filename,
                    'id' => $exists ? $exists->id : null
                ]
            );

            return ['success' => true];
        } catch (\Exception $e) {
            return ['error' => 'Could not save uploaded file. The upload was cancelled, or server error encountered'];
        }
    }
}
