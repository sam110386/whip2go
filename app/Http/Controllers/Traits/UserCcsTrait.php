<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\User;
use App\Models\Legacy\UserCcToken;
use App\Services\Legacy\PaymentProcessor;

trait UserCcsTrait {

    protected function _addCardLogic($userid, $dataInputs) {
        $userCcToken = UserCcToken::where('user_id', $userid)->first();

        /** @var PaymentProcessor $paymentProcessor */
        $paymentProcessor = app(PaymentProcessor::class);
        $return = $paymentProcessor->addNewCard((object) $dataInputs, $userCcToken->stripe_token ?? '');

        if ($return['status'] == 'success') {
            $dataToSave = [
                'user_id' => $userid,
                'card_type' => $dataInputs['card_type'] ?? '',
                'credit_card_number' => substr($dataInputs['credit_card_number'] ?? '', -4),
                'card_holder_name' => $dataInputs['card_holder_name'] ?? '',
                'expiration' => $dataInputs['expiration'] ?? '',
                'card_funding' => $return['card_funding'] ?? '',
                'cvv' => $dataInputs['cvv'] ?? '',
                'address' => $dataInputs['address'] ?? '',
                'city' => $dataInputs['city'] ?? '',
                'state' => $dataInputs['state'] ?? '',
                'zip' => $dataInputs['zip'] ?? '',
                'stripe_token' => $return['stripe_token'] ?? '',
                'card_id' => $return['card_id'] ?? '',
            ];

            $newCcToken = UserCcToken::create($dataToSave);
            
            $user = User::find($userid);
            if ($user) {
                if (empty($user->cc_token_id) || ($dataInputs['default'] ?? 0) == 1) {
                    $user->cc_token_id = $newCcToken->id;
                    $user->is_renter = 1;
                    $user->save();
                }
            }

            if ($userCcToken && ($dataInputs['default'] ?? 0) == 1 && !empty($dataToSave['card_id'])) {
                $paymentProcessor->makeCardDefault($userCcToken->stripe_token, $dataToSave['card_id']);
            }

            return ['status' => true, 'message' => "Card has been added successfully."];
        } else {
            return ['status' => false, 'message' => $return['message'] ?? ($return['msg'] ?? "Failed to add card.")];
        }
    }
}
