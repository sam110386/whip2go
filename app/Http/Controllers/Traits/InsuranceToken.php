<?php
namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\DriverFinancedInsuranceQuote;
use App\Models\Legacy\InsurancePayer;
use App\Models\Legacy\OrderDepositRule;

trait InsuranceToken
{
    protected function _getInsuranceToken(array $conditions): array
    {
        $defaultReturn = [
            'status' => false,
            'message' => 'Invalid Booking ID',
            'result' => []
        ];

        if (empty($conditions)) {
            return $defaultReturn;
        }

        $lease = CsOrder::select('id', 'parent_id', 'user_id')
            ->where($conditions)
            ->first();

        if (!$lease) {
            return [
                'status' => false,
                'message' => "Sorry, respected booking couldn't be found, so you can't perform this action now.",
                'result' => []
            ];
        }

        $targetOrderId = !empty($lease->parent_id) ? $lease->parent_id : $lease->id;
        $orderDepositRuleObj = OrderDepositRule::select('id', 'insurance_payer', 'vehicle_reservation_id')
            ->where('cs_order_id', $targetOrderId)
            ->first();

        if (!$orderDepositRuleObj) {
            return $defaultReturn;
        }

        if (in_array($orderDepositRuleObj->insurance_payer, [6, 5, 7])) {
            $driverQuote = DriverFinancedInsuranceQuote::where('order_id', $orderDepositRuleObj->vehicle_reservation_id)->first();

            if (!$driverQuote || empty($driverQuote->insurance_card)) {
                return [
                    'status' => false,
                    'message' => "Sorry, Driver didn't upload insurance token yet. He agreed to manage it himself.",
                    'result' => []
                ];
            }

            return [
                'status' => true,
                'message' => "Success",
                'result' => [
                    'file' => legacy_asset('files/reservation/' . $driverQuote->insurance_card)
                ]
            ];
        }

        $insurancePayer = InsurancePayer::where('order_deposit_rule_id', $orderDepositRuleObj->id)->first();
        if (!$insurancePayer || empty($insurancePayer->insurance_card)) {
            return [
                'status' => false,
                'message' => "Sorry, Driver didn't upload insurance token yet. He agreed to manage it himself.",
                'result' => []
            ];
        }

        return [
            'status' => true,
            'message' => "Success",
            'result' => [
                'file' => legacy_asset('files/reservation/' . $insurancePayer->insurance_card)
            ]
        ];
    }
    protected function _getDeclarationDoc(array $conditions): array
    {
        $defaultReturn = [
            'status' => false,
            'message' => 'Invalid Booking ID',
            'result' => []
        ];

        if (empty($conditions)) {
            return $defaultReturn;
        }

        $lease = CsOrder::select('id', 'parent_id', 'user_id')
            ->where($conditions)
            ->first();

        if (!$lease) {
            return [
                'status' => false,
                'message' => "Sorry, respected booking couldn't be found, so you can't perform this action now.",
                'result' => []
            ];
        }

        $targetOrderId = !empty($lease->parent_id) ? $lease->parent_id : $lease->id;
        $orderDepositRuleObj = OrderDepositRule::select('id', 'insurance_payer', 'vehicle_reservation_id')
            ->where('cs_order_id', $targetOrderId)
            ->first();

        if (!$orderDepositRuleObj) {
            return $defaultReturn;
        }

        if (in_array($orderDepositRuleObj->insurance_payer, [6, 5, 7])) {
            $driverQuote = DriverFinancedInsuranceQuote::where('order_id', $orderDepositRuleObj->vehicle_reservation_id)->first();

            if (!$driverQuote || empty($driverQuote->declaration_doc)) {
                return [
                    'status' => false,
                    'message' => "Sorry, Driver didn't upload insurance token yet. He agreed to manage it himself.",
                    'result' => []
                ];
            }

            return [
                'status' => true,
                'message' => "Success",
                'result' => [
                    'file' => legacy_asset('files/reservation/' . $driverQuote->declaration_doc)
                ]
            ];
        }

        $insurancePayer = InsurancePayer::where('order_deposit_rule_id', $orderDepositRuleObj->id)->first();
        if (!$insurancePayer || empty($insurancePayer->declaration_doc)) {
            return [
                'status' => false,
                'message' => "sorry, Dealer didn't upload declaration doc yet.",
                'result' => []
            ];
        }

        return [
            'status' => true,
            'message' => "Success",
            'result' => [
                'file' => legacy_asset('files/reservation/' . $insurancePayer->declaration_doc)
            ]
        ];
    }
}
