<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\Common;
use App\Services\Legacy\PaymentProcessor;
use App\Services\Legacy\TelematicsSubscriptionPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TelematicsSubscriptionsController extends LegacyAppController
{
    protected int $recordsPerPage = 25;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userid = session('userParentId', 0);
        if ($userid == 0) {
            $userid = session('userid');
        }

        $conditions = [
            ['TelematicsSubscription.user_id', '=', $userid],
        ];

        $sessLimitKey = 'telematics_subscriptions_user_limit';
        $limit = $request->input('Record.limit')
            ?: session($sessLimitKey, $this->recordsPerPage);
        session([$sessLimitKey => $limit]);

        $records = DB::table('telematics_subscriptions as TelematicsSubscription')
            ->where($conditions)
            ->orderByDesc('TelematicsSubscription.id')
            ->paginate($limit)
            ->withQueryString();

        $viewData = [
            'records' => $records,
            'title_for_layout' => 'Telematics Subscriptions',
        ];

        if ($request->ajax()) {
            return response()->view('cloud.telematics._index_table', $viewData);
        }

        return view('cloud.telematics.index', $viewData);
    }

    public function buy(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userid = session('userParentId', 0);
        if ($userid == 0) {
            $userid = session('userid');
        }

        $viewData = ['listTitle' => 'Buy Telematics'];

        if ($request->isMethod('post') && !empty($request->input('TelematicsSubscription.units'))) {
            $unit = (int) $request->input('TelematicsSubscription.units');
            if ($unit <= 0) {
                session()->flash('error', 'Please select a valid number of units');
                return redirect('/telematics/subscriptions/buy');
            }

            $shipping = sprintf('%0.2f', config('legacy.TELEMATICSHIPPING', 0));
            $subcriptionamt = sprintf('%0.2f', $unit * config('legacy.TELEMATICUNITPRICE', 0));
            $monthlyServices = sprintf('%0.2f', $unit * config('legacy.TELEMATICUNITMONTHSERVICE', 0));
            $tax = sprintf('%0.2f', ($subcriptionamt + $monthlyServices + $monthlyServices) * config('legacy.TELEMATICTAX', 0) / 100);
            $subtotal = sprintf('%0.2f', $subcriptionamt + $monthlyServices + $monthlyServices);
            $total = sprintf('%0.2f', $subcriptionamt + $monthlyServices + $monthlyServices + $shipping + $tax);

            $viewData = array_merge($viewData, compact(
                'unit', 'shipping', 'subcriptionamt', 'monthlyServices', 'tax', 'subtotal', 'total'
            ));
        }

        return view('cloud.telematics.buy', $viewData);
    }

    public function buypayment(Request $request): JsonResponse
    {
        $userid = session('userParentId', 0);
        if ($userid == 0) {
            $userid = session('userid');
        }

        if (empty($request->all())) {
            return response()->json(['status' => 'error', 'message' => 'invalid request']);
        }

        $cardData = $request->input('UserCcToken', []);
        $cardData['user_id'] = $userid;
        $cardData['status'] = 1;

        $PaymentProcessorObj = new PaymentProcessor();
        $return = $PaymentProcessorObj->addNewCard((object) $cardData);

        if (($return['status'] ?? '') !== 'success') {
            return response()->json($return);
        }

        $units = (int) $request->input('TelematicsSubscription.units', 0);
        $shipping = sprintf('%0.2f', config('legacy.TELEMATICSHIPPING', 0));
        $subcriptionamt = sprintf('%0.2f', $units * config('legacy.TELEMATICUNITPRICE', 0));
        $monthlyServices = sprintf('%0.2f', $units * config('legacy.TELEMATICUNITMONTHSERVICE', 0));
        $tax = sprintf('%0.2f', ($subcriptionamt + $monthlyServices + $monthlyServices) * config('legacy.TELEMATICTAX', 0) / 100);
        $subtotal = sprintf('%0.2f', $subcriptionamt + $monthlyServices + $monthlyServices);
        $total = sprintf('%0.2f', $subcriptionamt + $monthlyServices + $monthlyServices + $shipping + $tax);

        $chargereturn = $PaymentProcessorObj->chargeAmt($total, $return['stripe_token'], 'DIA Telematics', 'USD', 34);

        if (($chargereturn['status'] ?? '') !== 'success') {
            $PaymentProcessorObj->customerDelete($return['stripe_token']);
            return response()->json($chargereturn);
        }

        $dataTosave = [
            'user_id' => $userid,
            'card_type' => $cardData['card_type'] ?? '',
            'credit_card_number' => substr($cardData['credit_card_number'] ?? '', -4),
            'card_holder_name' => $cardData['card_holder_name'] ?? '',
            'expiration' => $cardData['expiration'] ?? '',
            'card_funding' => $return['card_funding'] ?? '',
            'cvv' => $cardData['cvv'] ?? '',
            'address' => $cardData['address'] ?? '',
            'city' => $cardData['city'] ?? '',
            'state' => $cardData['state'] ?? '',
            'zip' => $cardData['zip'] ?? '',
            'stripe_token' => $return['stripe_token'] ?? '',
            'status' => 1,
        ];

        $ccid = DB::table('user_cc_tokens')->insertGetId($dataTosave);

        $user = DB::table('users')->where('id', $userid)->select('id', 'cc_token_id')->first();
        if ($user && empty($user->cc_token_id)) {
            DB::table('users')->where('id', $userid)->update(['cc_token_id' => $ccid]);
        }

        $CommonObj = new Common();
        $nextOn = $CommonObj->getExactDateAfterMonths(time(), 1);

        $subId = DB::table('telematics_subscriptions')->insertGetId([
            'user_id' => $userid,
            'units' => $units,
            'upfront_amt' => $subtotal,
            'amt' => $total,
            'status' => 1,
            'next_on' => date('Y-m-d', $nextOn),
            'created' => now(),
            'updated' => now(),
        ]);

        DB::table('telematics_payments')->insert([
            'telematics_id' => $subId,
            'amt' => $total,
            'txn_id' => $chargereturn['transaction_id'] ?? null,
            'status' => 1,
            'last_processed' => date('Y-m-d'),
        ]);

        $this->notifySaleToAdmin();
        $this->notifyToDealer($userid, $subcriptionamt, $monthlyServices, $shipping, $tax, $subtotal, $total, $units);

        return response()->json(['status' => 'success', 'message' => 'Your subscription is created successfully']);
    }

    private function notifySaleToAdmin(): void
    {
        $dealername = session('userfullname', '');
        $msg = "Dealer {$dealername} ordered new telematics devices. Please check admin end for more details";

        try {
            Mail::send('emails.custom', ['MESSAGE' => $msg, 'logourl' => config('app.url') . '/img/DriveitawayBluelogo.png'], function ($message) {
                $message->from('support@driveitaway.com', 'DriveItAway Team')
                    ->replyTo('no-reply@driveitaway.com')
                    ->to('adam@driveitaway.com')
                    ->subject('New Telematics Sale');
            });
        } catch (\Exception $e) {
            // Silently fail on email errors
        }
    }

    private function notifyToDealer($userid, $subcriptionamt, $monthlyServices, $shipping, $tax, $subtotal, $total, $units): void
    {
        $unitPrice = config('legacy.TELEMATICUNITPRICE', 0);
        $monthService = config('legacy.TELEMATICUNITMONTHSERVICE', 0);

        $payment = "<tr><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>Equipment (One Time) :</span><em>(\$ {$unitPrice} X {$units})</em></div></div></td><td>&nbsp;</td><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>\${$subcriptionamt}</span></div></div></td></tr>";
        $payment .= "<tr><td colspan='3'>&nbsp;</td></tr>";
        $payment .= "<tr><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>First Month Service :</span><em>(\$ {$monthService} X {$units})</em></div></div></td><td>&nbsp;</td><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>\${$monthlyServices}</span></div></div></td></tr>";
        $payment .= "<tr><td colspan='3'>&nbsp;</td></tr>";
        $payment .= "<tr><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>Last Month Service :</span><em>(\$ {$monthService} X {$units})</em></div></div></td><td>&nbsp;</td><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>\${$monthlyServices}</span></div></div></td></tr>";
        $payment .= "<tr><td colspan='3'>&nbsp;</td></tr>";
        $payment .= "<tr><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>Subtotal :</span></div></div></td><td>&nbsp;</td><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>\${$subtotal}</span></div></div></td></tr>";
        $payment .= "<tr><td colspan='3'>&nbsp;</td></tr>";
        $payment .= "<tr><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>Shipping :</span></div></div></td><td>&nbsp;</td><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>\${$shipping}</span></div></div></td></tr>";
        $payment .= "<tr><td colspan='3'>&nbsp;</td></tr>";
        $payment .= "<tr><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>TAX :</span></div></div></td><td>&nbsp;</td><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>\${$tax}</span></div></div></td></tr>";
        $payment .= "<tr><td colspan='3'>&nbsp;</td></tr>";
        $payment .= "<tr><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>Total Paid :</span></div></div></td><td>&nbsp;</td><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>\${$total}</span></div></div></td></tr>";

        $msg = 'Thank you for your order. Please find the transaction details below';
        $service = new TelematicsSubscriptionPayment();
        $service->notifySaleToDealer($userid, $payment, $msg);
    }
}
