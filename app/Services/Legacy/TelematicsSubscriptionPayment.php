<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TelematicsSubscriptionPayment
{
    protected Common $common;
    protected PaymentProcessor $paymentProcessor;

    public function __construct()
    {
        $this->common = new Common();
        $this->paymentProcessor = new PaymentProcessor();
    }

    public function process(): void
    {
        $subscriptions = DB::table('telematics_subscriptions')
            ->where('status', 1)
            ->where('next_on', date('Y-m-d'))
            ->get();

        foreach ($subscriptions as $subscription) {
            $this->createSubscriptionPayment($subscription);
        }
    }

    public function createSubscriptionPayment($subscription): void
    {
        $units = $subscription->units;
        $amtToCharge = sprintf('%0.2f', $units * config('legacy.TELEMATICUNITMONTHSERVICE', 0));
        $nextOn = $this->common->getExactDateAfterMonths(strtotime($subscription->next_on), 1);

        DB::table('telematics_subscriptions')
            ->where('id', $subscription->id)
            ->update([
                'next_on' => date('Y-m-d', $nextOn),
                'updated' => now(),
            ]);

        DB::table('telematics_payments')->insert([
            'status' => 0,
            'telematics_id' => $subscription->id,
            'amt' => $amtToCharge,
            'txn_id' => null,
        ]);
    }

    public function chargeSubscriptionPayment(): void
    {
        $payments = DB::table('telematics_payments')
            ->where('status', 0)
            ->where(function ($q) {
                $q->whereNull('last_processed')
                    ->orWhere('last_processed', '<', date('Y-m-d'));
            })
            ->limit(5)
            ->get();

        foreach ($payments as $payment) {
            DB::table('telematics_payments')
                ->where('id', $payment->id)
                ->update(['last_processed' => date('Y-m-d')]);

            $this->chargePayment((array) $payment);
        }
    }

    public function chargePayment(array $payment): array
    {
        $subscription = DB::table('telematics_subscriptions')
            ->where('id', $payment['telematics_id'])
            ->first();

        if (empty($subscription)) {
            return ['status' => false, 'message' => 'Subscription not found'];
        }

        $dealerid = $subscription->user_id;
        $amtToCharge = $payment['amt'];

        $dealerObj = DB::table('users as User')
            ->leftJoin('user_cc_tokens as UserCcToken', function ($join) use ($dealerid) {
                $join->on('UserCcToken.id', '=', 'User.cc_token_id')
                    ->where('UserCcToken.user_id', '=', $dealerid);
            })
            ->where('User.id', $dealerid)
            ->select('User.id', 'User.first_name', 'User.last_name', 'UserCcToken.stripe_token', 'User.currency')
            ->first();

        if (empty($dealerObj)) {
            return ['status' => false, 'message' => 'Dealer not found'];
        }

        $stripe_token = $dealerObj->stripe_token ?? '';

        if (empty($stripe_token)) {
            DB::table('telematics_subscriptions')
                ->where('id', $subscription->id)
                ->update(['status' => 0]);

            $msg = "Dealer {$dealerObj->first_name} {$dealerObj->last_name} telematics subscription #{$subscription->id} has been disabled due to the payment failed. Respective dealer card details not found.";
            $this->notify($msg, 'Telematics Subscription Payment Failed');
            return ['status' => false, 'message' => $msg];
        }

        $chargereturn = $this->paymentProcessor->chargeAmt(
            $amtToCharge,
            $stripe_token,
            'DIA Telematics',
            $dealerObj->currency ?? 'USD',
            34
        );

        if (($chargereturn['status'] ?? '') !== 'success') {
            DB::table('telematics_subscriptions')
                ->where('id', $subscription->id)
                ->update(['status' => 0]);

            $msg = "Dealer {$dealerObj->first_name} {$dealerObj->last_name} telematics subscription #{$subscription->id} has been disabled due to the payment failed.";
            $this->notify($msg, 'Telematics Subscription Payment Failed');
            return ['status' => false, 'message' => $chargereturn['message'] ?? 'Payment failed'];
        }

        DB::table('telematics_payments')
            ->where('id', $payment['id'])
            ->update([
                'txn_id' => $chargereturn['transaction_id'] ?? null,
                'status' => 1,
                'last_processed' => date('Y-m-d'),
            ]);

        $allFailed = DB::table('telematics_payments')
            ->where('telematics_id', $subscription->id)
            ->where('status', 0)
            ->count();

        DB::table('telematics_subscriptions')
            ->where('id', $subscription->id)
            ->update([
                'status' => $allFailed ? 0 : 1,
                'updated' => now(),
            ]);

        $this->notifyToDealerRenewal($dealerid, $amtToCharge, $subscription->units);

        $msg = "Dealer {$dealerObj->first_name} {$dealerObj->last_name} telematics subscription #{$subscription->id} renewed successfully";
        $this->notify($msg, 'Telematics Subscription Renewed');

        return ['status' => true, 'message' => 'Payment captured successfully and telematics subscription renewed.'];
    }

    private function notify(string $msg, string $subject): void
    {
        try {
            Mail::send('emails.custom', [
                'MESSAGE' => $msg,
                'logourl' => config('app.url') . '/img/DriveitawayBluelogo.png',
            ], function ($message) use ($subject) {
                $message->from('support@driveitaway.com', 'DriveItAway Team')
                    ->replyTo('no-reply@driveitaway.com')
                    ->to('adam@driveitaway.com')
                    ->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('TelematicsSubscriptionPayment notify failed: ' . $e->getMessage());
        }
    }

    public function notifySaleToDealer(int $dealer, string $payment, string $msg): void
    {
        $dealerObj = DB::table('users')
            ->where('id', $dealer)
            ->select('email', 'notify_email', 'first_name', 'last_name')
            ->first();

        if (empty($dealerObj)) {
            return;
        }

        $email = !empty($dealerObj->notify_email) ? $dealerObj->notify_email : $dealerObj->email;
        $name = $dealerObj->first_name . ' ' . $dealerObj->last_name;

        try {
            Mail::send('emails.telematics_payment', [
                'logourl' => config('app.url') . '/img/DriveitawayBluelogo.png',
                'NAME' => $name,
                'MESSAGE' => $msg,
                'TEXT' => $payment,
            ], function ($message) use ($email) {
                $message->from('support@driveitaway.com', 'DriveItAway Team')
                    ->replyTo('no-reply@driveitaway.com')
                    ->to($email)
                    ->cc('adam@driveitaway.com')
                    ->subject('Telematics Payments');
            });
        } catch (\Exception $e) {
            Log::error('TelematicsSubscriptionPayment notifySaleToDealer failed: ' . $e->getMessage());
        }
    }

    private function notifyToDealerRenewal(int $userid, string $total, int $units): void
    {
        $unitPrice = config('legacy.TELEMATICUNITMONTHSERVICE', 0);

        $payment = "<tr><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>Monthly Service :</span><em>(\$ {$unitPrice} X {$units})</em></div></div></td><td>&nbsp;</td><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>\${$total}</span></div></div></td></tr>";
        $payment .= "<tr><td colspan='3'>&nbsp;</td></tr>";
        $payment .= "<tr><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>Subtotal :</span></div></div></td><td>&nbsp;</td><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>\${$total}</span></div></div></td></tr>";
        $payment .= "<tr><td colspan='3'>&nbsp;</td></tr>";
        $payment .= "<tr><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>Total Paid :</span></div></div></td><td>&nbsp;</td><td align='center' valign='top'><div><div style='color:#555555;font-size:12px;font-weight:bold;'><span>\${$total}</span></div></div></td></tr>";

        $msg = 'Telematics monthly payment captured successfully and subscription renewed.';
        $this->notifySaleToDealer($userid, $payment, $msg);
    }
}
