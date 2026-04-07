<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\User;
use App\Models\Legacy\UserCcToken;
use Illuminate\Support\Facades\Log;

trait UserCcsTrait {

    protected function _addCardLogic($userid, $dataInputs) {
        $userCcToken = UserCcToken::where('user_id', $userid)->first();
        
        // Placeholder for PaymentProcessor
        Log::info("PaymentProcessor: addNewCard for user $userid");
        
        // Simulated successful return from PaymentProcessor
        $return = [
            'status' => 'success',
            'stripe_token' => 'tok_simulated_' . time(),
            'card_id' => 'card_simulated_' . time(),
            'card_funding' => 'credit'
        ];

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
                Log::info("PaymentProcessor: makeCardDefault for user $userid");
            }

            return ['status' => true, 'message' => "Card has been added successfully."];
        } else {
            return ['status' => false, 'message' => $return['message'] ?? "Failed to add card."];
        }
    }
}
