<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\InsuranceQuote;
use App\Models\Legacy\InsuranceProvider;
use App\Models\Legacy\VehicleReservation;
use App\Services\Legacy\IntercomClient;
use Illuminate\Http\Request;
class InsuranceQuotesController extends LegacyAppController
{
    private array $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

    public function listpopup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $bookingid = $request->input('bookingid');

        $quotes = InsuranceQuote::with('provider')
            ->where('order_id', $bookingid)
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

        $providers = InsuranceProvider::where('status', 1)
            ->pluck('name', 'id')
            ->toArray();

        $record = null;
        if (!empty($id)) {
            $record = InsuranceQuote::find($id);
        }

        return view('admin.insurance_provider.insurance_quotes.popup', compact('bookingid', 'providers', 'record'));
    }
    public function delete(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $id = $request->input('id');
        $quote = InsuranceQuote::select('id', 'selected')->find($id);

        if ($quote && $quote->selected != 1) {
            $quote->delete();
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

        $maxSize = $this->commonService->FileSizeInBytes(ini_get('upload_max_filesize'));

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
            $quote = InsuranceQuote::find($dataToSave['id']);
            if ($quote) {
                $quote->update($dataToSave);
            }
        } else {
            unset($dataToSave['id']);
            $quote = InsuranceQuote::create($dataToSave);
            $dataToSave['id'] = $quote->id;
        }

        if ($notify) {
            $reservation = VehicleReservation::with('renter')->find($dataToSave['order_id']);

            if ($reservation && $reservation->renter) {
                $metadata = [
                    'url' => config('app.url') . '/insurance_provider/insurance_quotes/review/'
                        . base64_encode($reservation->id . '|' . $reservation->renter_id),
                ];

                (new IntercomClient())->sendEvent($reservation->renter->toArray(), $metadata, 'InsuranceOption');
            }
        }

        return response()->json(['status' => true, 'message' => 'Request saved successfully']);
    }
}
