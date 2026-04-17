<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DiaFleetBackupQuotesController extends LegacyAppController
{
    private array $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx'];

    public function popup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $recordid = $request->input('recordid', '');
        $id = $request->input('id', '');
        $myModal = $request->input('model', 'myModal');
        $data = [];

        if (empty($recordid) && !empty($id)) {
            $data = DB::table('driver_financed_insurance_quotes')->where('id', $id)->first();
            $recordid = $data->order_id ?? '';
        }
        if (empty($id) && !empty($recordid)) {
            $data = DB::table('driver_financed_insurance_quotes')->where('order_id', $recordid)->first();
        }

        $quoteData = $data ? (array) $data : [];
        $creditCard = !empty($quoteData['credit_card']) ? json_decode($quoteData['credit_card'], true) : [];
        $providerAccount = !empty($quoteData['provider_account']) ? json_decode($quoteData['provider_account'], true) : [];
        $quotes = !empty($quoteData['quote']) ? json_decode($quoteData['quote'], true) : [];
        $providers = DB::table('insurance_providers')->where('status', 1)->get()->toArray();

        $orderDepositRuleObj = DB::table('order_deposit_rules')
            ->leftJoin('vehicle_reservations', 'vehicle_reservations.id', '=', 'order_deposit_rules.vehicle_reservation_id')
            ->leftJoin('axle_statuses', 'axle_statuses.order_id', '=', 'order_deposit_rules.id')
            ->where('order_deposit_rules.vehicle_reservation_id', $recordid)
            ->select(
                'order_deposit_rules.id',
                'order_deposit_rules.insurance_payer',
                'vehicle_reservations.renter_id',
                'vehicle_reservations.id as reservation_id',
                'axle_statuses.axle_status'
            )
            ->first();

        $orderandusers = '';
        if ($orderDepositRuleObj) {
            $orderandusers = base64_encode($orderDepositRuleObj->reservation_id . '|' . $orderDepositRuleObj->renter_id);
        }

        $viewData = compact('recordid', 'myModal', 'providers', 'quotes', 'orderDepositRuleObj', 'orderandusers', 'quoteData', 'creditCard', 'providerAccount');

        if (!empty($id)) {
            $html = view('admin.insurance.dia_fleet_backup_quotes.popup', $viewData)->render();
            return response()->json(["status" => true, "message" => "", 'recordid' => $recordid, "html" => $html]);
        }

        return view('admin.insurance.dia_fleet_backup_quotes.popup', $viewData);
    }

    public function save(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $return = ['status' => false, "message" => "Sorry, your request is not valid"];
        if ($request->ajax() && $request->isMethod('post')) {
            $dataToSave = $request->all();
            $isApproved = $request->input('approve', false);
            $isPolicy = $request->input('policy', false);

            if (!$isApproved && !$isPolicy) {
                $dataToSave['DriverFinancedInsuranceQuote']['quote_approved'] = null;
            }
            $recordid = $dataToSave['DriverFinancedInsuranceQuote']['order_id'];

            $quoteRow = $dataToSave['DriverFinancedInsuranceQuote'];
            if (!empty($quoteRow['id'])) {
                DB::table('driver_financed_insurance_quotes')->where('id', $quoteRow['id'])->update(array_filter($quoteRow, fn($v) => $v !== null));
            } else {
                DB::table('driver_financed_insurance_quotes')->insert($quoteRow);
            }

            if ($isApproved) {
                $vhicleReservationObj = DB::table('vehicle_reservations')
                    ->leftJoin('users', 'users.id', '=', 'vehicle_reservations.renter_id')
                    ->where('vehicle_reservations.id', $recordid)
                    ->select('vehicle_reservations.id', 'users.*')
                    ->first();

                if ($vhicleReservationObj) {
                    $url = config('app.url') . '/insurance/dia_fleet_backup_docusign/signDocument/' . base64_encode($vhicleReservationObj->id . '|' . $vhicleReservationObj->renter_id);
                    try {
                        (new \Intercom())->createEvents([
                            "event_name" => "insurance_quote_approved",
                            "created_at" => time(),
                            "external_id" => $vhicleReservationObj->id,
                            "user_id" => $vhicleReservationObj->id,
                            "metadata" => [
                                'docusign_url' => $url,
                                'booking' => $recordid,
                                'user' => $vhicleReservationObj->first_name . ' ' . $vhicleReservationObj->first_name,
                            ],
                        ]);
                    } catch (\Exception $e) {
                        // Intercom event failed silently
                    }
                }
            }
            $return = ['status' => true, "message" => "Record has been updated successfully"];
        }
        return response()->json($return);
    }

    public function saveImage(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $type = $request->input('type');
        $id = $request->input('id');
        $return = $this->handleUpload($request->file($type), $id, $type);
        return response()->json($return);
    }

    private function handleUpload($file, $id, string $filetype): array
    {
        if (!$file || !$file->isValid()) {
            return ['error' => 'No files were uploaded.'];
        }
        if ($file->getSize() == 0) {
            return ['error' => 'File is empty.'];
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $this->allowedExtensions)) {
            return ['error' => 'File has an invalid extension.'];
        }

        $filename = $filetype . '_' . $id . '.' . $ext;
        $file->move(public_path('files/reservation'), $filename);

        $exits = DB::table('driver_financed_insurance_quotes')->where('order_id', $id)->first();
        if (!empty($exits)) {
            DB::table('driver_financed_insurance_quotes')->where('id', $exits->id)->update([$filetype => $filename]);
        } else {
            DB::table('driver_financed_insurance_quotes')->insert(['order_id' => $id, $filetype => $filename]);
        }

        return ['success' => true];
    }
}
