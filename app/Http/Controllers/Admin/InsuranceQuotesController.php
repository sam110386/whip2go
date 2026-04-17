<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InsuranceQuotesController extends LegacyAppController
{
    private array $allowedExtensions = [
        'jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    public function listpopup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $bookingid = $request->input('bookingid');

        $quotes = DB::table('insurance_quotes as InsuranceQuote')
            ->leftJoin('insurance_providers as InsuranceProvider', 'InsuranceProvider.id', '=', 'InsuranceQuote.provider_id')
            ->where('InsuranceQuote.order_id', $bookingid)
            ->select('InsuranceQuote.*', 'InsuranceProvider.id as provider_table_id', 'InsuranceProvider.name as provider_name')
            ->get();

        return view('admin.insurance_provider.insurance_quotes.listpopup', compact('bookingid', 'quotes'));
    }

    public function popup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $request->input('id');
        $bookingid = $request->input('bookingid');

        $providers = DB::table('insurance_providers')
            ->where('status', 1)
            ->pluck('name', 'id')
            ->toArray();

        $record = [];
        if (!empty($id)) {
            $record = DB::table('insurance_quotes')->where('id', $id)->first();
            if ($record) {
                $record = (array) $record;
            }
        }

        return view('admin.insurance_provider.insurance_quotes.popup', compact('bookingid', 'providers', 'record'));
    }

    public function delete(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $id = $request->input('id');
        $bookingid = $request->input('bookingid');

        $quote = DB::table('insurance_quotes')->where('id', $id)->first(['id', 'selected']);

        if (!empty($id) && !empty($quote) && $quote->selected != 1) {
            DB::table('insurance_quotes')->where('id', $id)->delete();
            return response()->json(['status' => true, 'message' => 'Record deleted successfully']);
        }

        return response()->json(['status' => false, 'message' => 'Sorry, this record cant be deleted']);
    }

    public function save(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $return = ['status' => false, 'message' => 'Sorry, not a valid request'];

        if (!$request->isMethod('post') && !$request->isMethod('put')) {
            return response()->json($return);
        }

        $dataToSave = $request->input('InsuranceQuote', []);
        unset($dataToSave['policy_doc']);

        $maxSize = $this->fileSizeInBytes(ini_get('upload_max_filesize'));

        if ($request->hasFile('InsuranceQuote.policy_doc')) {
            $file = $request->file('InsuranceQuote.policy_doc');
            $ext = strtolower($file->getClientOriginalExtension());

            if (in_array($ext, $this->allowedExtensions) && $file->getSize() <= $maxSize) {
                $filename = time() . '.' . $ext;
                $uploadDir = public_path('files/insurancequote');
                if (!file_exists($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $file->move($uploadDir, $filename);
                $dataToSave['policy_doc'] = $filename;
            } elseif ($file->getSize() > $maxSize) {
                $return['message'] = 'Sorry, policy doc could not be uploaded, it must be in proper size';
                return response()->json($return);
            }
        }

        if (empty($dataToSave['provider_id']) || empty($dataToSave['order_id'])) {
            $return['message'] = 'Provider and booking are required';
            return response()->json($return);
        }

        $notify = !empty($dataToSave['notify']);
        unset($dataToSave['notify']);

        if (!empty($dataToSave['id'])) {
            DB::table('insurance_quotes')->where('id', $dataToSave['id'])->update($dataToSave);
        } else {
            unset($dataToSave['id']);
            $dataToSave['id'] = DB::table('insurance_quotes')->insertGetId($dataToSave);
        }

        if ($notify) {
            $reservation = DB::table('vehicle_reservations as VehicleReservation')
                ->leftJoin('users as Renter', 'Renter.id', '=', 'VehicleReservation.renter_id')
                ->where('VehicleReservation.id', $dataToSave['order_id'])
                ->select(
                    'VehicleReservation.id',
                    'Renter.id as renter_id',
                    'Renter.email',
                    'Renter.contact_number',
                    'Renter.first_name',
                    'Renter.last_name'
                )
                ->first();

            if ($reservation) {
                $metadata = [
                    'url' => config('app.url') . '/insurance_provider/insurance_quotes/review/'
                        . base64_encode($reservation->id . '|' . $reservation->renter_id),
                ];
                // TODO: Send Intercom event – (new Intercom())->sendEvent($reservation, $metadata, 'InsuranceOption')
            }
        }

        return response()->json(['status' => true, 'message' => 'Request saved successfully']);
    }

    private function fileSizeInBytes(string $size): int
    {
        $unit = strtolower(substr($size, -1));
        $value = (int) $size;
        switch ($unit) {
            case 'g': return $value * 1073741824;
            case 'm': return $value * 1048576;
            case 'k': return $value * 1024;
            default:  return $value;
        }
    }
}
