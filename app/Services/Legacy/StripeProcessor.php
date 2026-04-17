<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

class StripeProcessor
{
    private string $_secret;
    private string $_mode;
    private $Stripe;

    public function __construct()
    {
        $this->_secret = config('legacy.Stripe.secret', '');
        $this->_mode = config('legacy.Stripe.mode', '');
    }

    public function addNewCard($dataValues, string $cust_id = '', string $stripekey = ''): array
    {
        $return = ['status' => 'error', 'authcode' => '', 'message' => 'Required inputs are missing'];
        $dataValues->credit_card_number = preg_replace("/[^0-9]/", "", $dataValues->credit_card_number);
        if (empty($dataValues->credit_card_number) || empty($dataValues->cvv) || empty($dataValues->expiration)) {
            return $return;
        }
        $ccexpdate = explode("/", $dataValues->expiration);
        if (!empty($stripekey)) {
            $this->_secret = $stripekey;
        }
        $this->Stripe = new \Stripe($this->_secret, $this->_mode);
        $result = $this->Stripe->createCardToken([
            "card" => [
                "number" => $dataValues->credit_card_number,
                "exp_month" => $ccexpdate[0],
                "exp_year" => $ccexpdate[1],
                "cvc" => $dataValues->cvv,
                "name" => $dataValues->card_holder_name,
                "address_zip" => $dataValues->zip,
                "address_city" => $dataValues->city,
                "address_state" => $dataValues->state,
                "address_country" => ($dataValues->country != '' ? $dataValues->country : 'US'),
                "address_line1" => $dataValues->address,
            ],
        ]);

        if (!isset($result['status']) || $result['status'] != 'success') {
            $return['message'] = $result['msg'];
            return $return;
        }
        if (!empty($cust_id)) {
            $result2 = $this->Stripe->addCardToCustomer($cust_id, $result['token'], ["name" => $dataValues->card_holder_name]);
            if (isset($result2['status']) && $result2['status'] == 'success') {
                $return['status'] = 'success';
                $return['stripe_token'] = $cust_id;
                $return['card_id'] = $result2['stripe_id'];
                $return['card_funding'] = $result['card_funding'];
            } else {
                $return['message'] = $result2;
            }
            return $return;
        }
        $result1 = $this->Stripe->customerCreate(["stripeToken" => $result['token']]);

        if (isset($result1['status']) && $result1['status'] == 'success') {
            $return['status'] = 'success';
            $return['stripe_token'] = $result1['stripe_id'];
            $return['card_id'] = $result['card_id'];
            $return['card_funding'] = $result['card_funding'];
        } else {
            $return['message'] = $result1;
        }
        return $return;
    }

    public function addCardToCustomer(string $cust_id, string $card_id, array $opt = []): array
    {
        $this->Stripe = new \Stripe($this->_secret, $this->_mode);
        return $this->Stripe->addCardToCustomer($cust_id, $card_id, $opt);
    }

    public function makeCardDefault(string $cust_id, string $card_id): mixed
    {
        $this->Stripe = new \Stripe($this->_secret, $this->_mode);
        return $this->Stripe->makeCardDefault($cust_id, ["default_source" => $card_id]);
    }

    public function customerDelete(string $cust_id): array
    {
        $return = ['status' => 'error', 'message' => 'Required inputs are missing'];
        if (empty($cust_id)) {
            return $return;
        }
        $this->Stripe = new \Stripe($this->_secret, $this->_mode);
        return $this->Stripe->customerDelete(['cust_id' => $cust_id]);
    }

    public function chargeInsurance(float $amount, string $cc_token_id, string $stripekey = '', $order_rule_id = ''): array
    {
        if (!empty($stripekey)) {
            $this->_secret = $stripekey;
        }
        $this->Stripe = new \Stripe($this->_secret, $this->_mode);
        $return = ['status' => 'error', 'message' => 'Sorry, one of payment get failed'];

        $InsuObj = [
            "amount" => $amount,
            "currency" => "usd",
            "stripeCustomer" => $cc_token_id,
            "capture" => true,
            "description" => "Insu. Charge",
            "statement_descriptor" => "ROI Insu. Charge",
            "metadata" => ["order_rule_id" => $order_rule_id],
        ];
        $rentresult = $this->Stripe->charge($InsuObj);
        if (isset($rentresult['status']) && $rentresult['status'] == 'success') {
            $return['status'] = 'success';
            $return['transaction_id'] = $rentresult['stripe_id'];
            $return['message'] = 'Your request processed successfully';
        } else {
            $return['message'] = $rentresult;
        }

        return $return;
    }
}
