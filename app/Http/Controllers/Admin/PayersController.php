<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\StripeProcessor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PayersController extends LegacyAppController
{
    private array $allowedExtensions = ['jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx'];

    public function list(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $recordid = $request->input('order');
        $myModal = $request->input('model', 'myModal');
        $records = DB::table('insurance_payers')->where('order_deposit_rule_id', $recordid)->get()->toArray();

        return view('admin.insurance.payers._list', compact('records', 'recordid', 'myModal'));
    }

    public function popup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $recordid = $request->input('order');
        $isNew = $request->input('isNew', false);
        $myModal = $request->input('model', 'myModal');
        $data = null;

        if (!$isNew) {
            $data = DB::table('insurance_payers')->where('order_deposit_rule_id', $recordid)->first();
        }

        return view('admin.insurance.payers.popup', compact('recordid', 'myModal', 'data'));
    }

    public function save(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        if ($request->ajax()) {
            $payerData = $request->input('InsurancePayer', []);
            if (!empty($payerData['id'])) {
                DB::table('insurance_payers')->where('id', $payerData['id'])->update($payerData);
            } else {
                unset($payerData['id']);
                DB::table('insurance_payers')->insert($payerData);
            }
        }
        return response()->json(['status' => true, "message" => "Vehicle has been updated successfully"]);
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

        $OrderDepositRuleObj = DB::table('order_deposit_rules')->where('id', $id)->first(['id', 'insurance_payer', 'vehicle_reservation_id']);
        $insurance_payer = $OrderDepositRuleObj->insurance_payer ?? null;

        if (in_array($insurance_payer, [5, 6, 7])) {
            $vehicle_reservation_id = $OrderDepositRuleObj->vehicle_reservation_id;
            $filename = $filetype . '_' . $vehicle_reservation_id . '.' . $ext;
            $file->move(public_path('files/reservation'), $filename);

            $exits = DB::table('driver_financed_insurance_quotes')->where('order_id', $vehicle_reservation_id)->first();
            if (!empty($exits)) {
                DB::table('driver_financed_insurance_quotes')->where('id', $exits->id)->update([$filetype => $filename]);
            } else {
                DB::table('driver_financed_insurance_quotes')->insert(['order_id' => $vehicle_reservation_id, $filetype => $filename]);
            }
            return ['success' => true];
        }

        $filename = $filetype . '_' . $id . '.' . $ext;
        $file->move(public_path('files/reservation'), $filename);

        $exits = DB::table('insurance_payers')->where('order_deposit_rule_id', $id)->first();
        if (!empty($exits)) {
            DB::table('insurance_payers')->where('id', $exits->id)->update([$filetype => $filename]);
        } else {
            DB::table('insurance_payers')->insert(['order_deposit_rule_id' => $id, $filetype => $filename]);
        }

        return ['success' => true];
    }

    public function chargeAdvance(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $orderruleid = base64_decode($request->input('orderruleid'));
        $data = DB::table('insurance_payers')->where('order_deposit_rule_id', $orderruleid)->first();

        return view('admin.insurance.payers.charge_advance', ['data' => $data, 'orderruleid' => $orderruleid]);
    }

    public function processChargeAdvance(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $return = ["status" => false, "message" => "Sorry, wrong attempt"];
        if (!$request->ajax() || !$request->isMethod('put')) {
            return response()->json($return);
        }

        $data = $request->all();
        $return["message"] = "Sorry, please enter correct value for days";
        $days = (int) ($data['InsurancePayer']['days'] ?? 0);
        if (!$days) {
            return response()->json($return);
        }

        $InsurancePayerObj = DB::table('insurance_payers')->where('order_deposit_rule_id', $data['InsurancePayer']['order_deposit_rule_id'])->first();
        if (empty($InsurancePayerObj) || empty($InsurancePayerObj->stripe_key)) {
            $return["message"] = "Sorry, ROI vendor stripe account is not configured yet";
            return response()->json($return);
        }

        $InsurancePayerTokenObj = DB::table('insurance_payer_tokens')
            ->where('order_rule_id', $data['InsurancePayer']['order_deposit_rule_id'])
            ->where('is_default', 1)
            ->first();
        if (empty($InsurancePayerTokenObj)) {
            $return["message"] = "Sorry, Driver didnt add his CC info yet";
            return response()->json($return);
        }

        $calculatedAmount = sprintf('%0.2f', ($days * $InsurancePayerObj->daily_rate));
        if ($calculatedAmount <= 0) {
            $return["message"] = "Sorry, calculated insurance is not valid value.";
            return response()->json($return);
        }

        $StripeProcessorObj = new StripeProcessor();
        $paymentResult = $StripeProcessorObj->chargeInsurance(
            $calculatedAmount,
            $InsurancePayerTokenObj->stripe_token,
            $InsurancePayerObj->stripe_key,
            $InsurancePayerObj->order_deposit_rule_id
        );
        if ($paymentResult['status'] != 'success') {
            $return["message"] = $paymentResult['message'];
            DB::table('insurance_payers')->where('id', $InsurancePayerObj->id)->update(['last_attempt' => now()]);
            return response()->json($return);
        }

        DB::table('insurance_payer_payments')->insert([
            "order_rule_id" => $InsurancePayerObj->order_deposit_rule_id,
            "amount" => $calculatedAmount,
            "transaction_id" => $paymentResult['transaction_id'],
            "created" => now(),
        ]);

        $nextDate = empty($InsurancePayerObj->next)
            ? Carbon::today()->addDays($days)->format('Y-m-d')
            : Carbon::parse($InsurancePayerObj->next)->addDays($days)->format('Y-m-d');

        DB::table('insurance_payers')->where('id', $InsurancePayerObj->id)->update([
            "last_attempt" => now(),
            "amount" => $calculatedAmount,
            'attepmt' => 1,
            "next" => $nextDate,
        ]);

        return response()->json(["status" => true, "message" => "Amount : \${$calculatedAmount} is charged successfully"]);
    }

    public function pendinginsurancepopup(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $ruleid = $request->input('ruleid');
        $orderid = $request->input('order');
        $OrderDepositRuleObj = DB::table('order_deposit_rules')->where('id', $ruleid)->first(['id', 'insurance_payer', 'start_datetime']);

        $data = DB::table('insurance_payers')->where('order_deposit_rule_id', $OrderDepositRuleObj->id)->first();
        $last_date = empty($data->next)
            ? Carbon::parse($OrderDepositRuleObj->start_datetime)->format('Y-m-d')
            : $data->next;

        $days = (int) Carbon::parse($last_date)->diffInDays(Carbon::today());
        if (!$days) {
            $days = 1;
        }
        $calculatedAmount = sprintf('%0.2f', ($days * ($data->daily_rate ?? 0)));

        return view('admin.insurance.payers._pendinginsurancepopup', compact('data', 'orderid', 'calculatedAmount'));
    }

    public function usertransactions(Request $request, $order_rule_id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (empty($order_rule_id)) {
            $order_rule_id = $request->input('order_rule_id');
        }

        $limit = $request->input('Record.limit', session('payers_limit', 20));
        session(['payers_limit' => $limit]);

        $records = DB::table('insurance_payer_payments')
            ->where('order_rule_id', $order_rule_id)
            ->orderBy('id', 'DESC')
            ->paginate($limit);

        if ($order_rule_id && $request->ajax()) {
            return view('admin.insurance.elements._usertransactions', compact('records', 'order_rule_id'));
        }

        return view('admin.insurance.payers.usertransactions', compact('records', 'order_rule_id'));
    }

    public function processBoyiInsurance(Request $request): JsonResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $return = $this->processBoyiInsuranceInternal($request);
        return response()->json($return);
    }

    private function processBoyiInsuranceInternal(Request $request): array
    {
        $return = ["status" => false, "message" => "Sorry, wrong attempt"];
        if (!$request->ajax() || !$request->isMethod('post')) {
            return $return;
        }

        $data = $request->all();
        $InsurancePayerObj = DB::table('insurance_payers')
            ->where('order_deposit_rule_id', $data['InsurancePayer']['order_deposit_rule_id'])
            ->first();
        if (empty($InsurancePayerObj) || empty($InsurancePayerObj->stripe_key)) {
            $return["message"] = "Sorry, ROI vendor stripe account is not configured yet";
            return $return;
        }

        $return["message"] = "Sorry, please enter correct value for days";
        $days = ceil($data['InsurancePayer']['insurance'] / $InsurancePayerObj->daily_rate);
        if (!$days) {
            return $return;
        }

        $InsurancePayerTokenObj = DB::table('insurance_payer_tokens')
            ->where('order_rule_id', $data['InsurancePayer']['order_deposit_rule_id'])
            ->where('is_default', 1)
            ->first();
        if (empty($InsurancePayerTokenObj)) {
            $return["message"] = "Sorry, Driver didnt add his CC info yet";
            return $return;
        }

        $calculatedAmount = sprintf('%0.2f', ($days * $InsurancePayerObj->daily_rate));
        if ($calculatedAmount <= 0) {
            $return["message"] = "Sorry, calculated insurance is not valid value.";
            return $return;
        }

        $StripeProcessorObj = new StripeProcessor();
        $paymentResult = $StripeProcessorObj->chargeInsurance(
            $calculatedAmount,
            $InsurancePayerTokenObj->stripe_token,
            $InsurancePayerObj->stripe_key,
            $InsurancePayerObj->order_deposit_rule_id
        );
        if ($paymentResult['status'] != 'success') {
            $return["message"] = $paymentResult['message'];
            DB::table('insurance_payers')->where('id', $InsurancePayerObj->id)->update(['last_attempt' => now()]);
            return $return;
        }

        DB::table('insurance_payer_payments')->insert([
            "order_rule_id" => $InsurancePayerObj->order_deposit_rule_id,
            "amount" => $calculatedAmount,
            "transaction_id" => $paymentResult['transaction_id'],
            "created" => now(),
        ]);

        $OrderDepositRuleObj = DB::table('order_deposit_rules')
            ->where('id', $InsurancePayerObj->order_deposit_rule_id)
            ->first(['id', 'start_datetime']);
        $last_date = $InsurancePayerObj->next
            ? Carbon::parse($OrderDepositRuleObj->start_datetime)->format('Y-m-d')
            : $InsurancePayerObj->next;

        DB::table('insurance_payers')->where('id', $InsurancePayerObj->id)->update([
            "last_attempt" => now(),
            "amount" => $calculatedAmount,
            'attepmt' => 1,
            "next" => Carbon::parse($last_date)->addDays($days)->format('Y-m-d'),
        ]);

        if (empty($InsurancePayerObj->next)) {
            return ["status" => true, "message" => "Amount : \${$calculatedAmount} is charged successfully"];
        }

        $daysTillDate = (int) Carbon::parse($last_date)->diffInDays(Carbon::today());
        if (!$daysTillDate) {
            return ["status" => true, "message" => "Amount : \${$calculatedAmount} is charged successfully"];
        }

        $expectedAmount = sprintf('%0.2f', ($daysTillDate * $InsurancePayerObj->daily_rate));
        if ($expectedAmount > $calculatedAmount) {
            DB::table('cs_orders')
                ->where('id', $data['InsurancePayer']['orderid'])
                ->update(['pending_insu' => ($expectedAmount - $calculatedAmount)]);
        }

        return ["status" => true, "message" => "Amount : \${$calculatedAmount} is charged successfully"];
    }
}
