<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

class PromoService
{
    public array $promoconditions = [
        'rent' => 'Rent Total',
        'emf' => 'EMF',
        'initial' => 'Initial Fee',
        'insurance' => 'Insurance Fee',
        'user' => 'Driver Id',
        'hitch' => 'Hitch Program',
    ];

    public array $rules = [
        '==' => 'is',
        '!=' => 'is not',
        '>=' => 'equals or greater than',
        '<=' => 'equals or less than',
        '>' => 'greater than',
        '<' => 'less than',
        '()' => 'is one of',
        '!()' => 'is not one of',
    ];

    public function applyPromoCode(array $returns, $userid = ''): array
    {
        $returns['rent_discount'] = 0;
        $returns['initial_fee_discount'] = 0;
        $returns['discount_des'] = '';

        $acceptedRule = DB::table('promo_terms')->where('user_id', $userid)->first();
        if (empty($acceptedRule)) {
            return $returns;
        }

        $coupon = DB::table('promotion_rules')
            ->where('status', 1)
            ->where('id', $acceptedRule->promo_rule_id)
            ->first();
        if (empty($coupon)) {
            return $returns;
        }

        $promoCons = !empty($coupon->conditions)
            ? json_decode($coupon->conditions, true)
            : ['con1' => '', 'discount1' => '', 'rule1' => ''];

        if (($promoCons['con1'] ?? '') === 'hitch') {
            $isHitch = DB::table('hitch_leads')->where('user_id', $userid)->first();
            if (empty($isHitch)) {
                return $returns;
            }
        }

        if (($promoCons['con1'] ?? '') === 'user' && ($promoCons['discount1'] ?? '') != $userid) {
            return $returns;
        }

        $RentalDiscountType = $coupon->type;
        $Rentaldiscountval = $coupon->discount;
        $title = $coupon->title;
        $initial_discount = $coupon->initial_discount;
        $initial_discount_type = $coupon->initial_discount_type;

        $conditionsCod1 = !empty($promoCons['con1']) ? $promoCons['con1'] : '';
        $conditionsRule1 = !empty($promoCons['rule1']) ? $promoCons['rule1'] : '==';
        $conditionsDiscount1 = !empty($promoCons['discount1']) ? $promoCons['discount1'] : '0';

        $checkVal = false;
        if ($Rentaldiscountval > 0) {
            $rent = $returns['rent'] ? preg_replace('/[^0-9.]/', '', $returns['rent']) : 0;
            $returns['rent_promo'] = ['type' => $RentalDiscountType, 'discountval' => $Rentaldiscountval];

            if (!empty($conditionsCod1) && $rent) {
                if (!$this->evaluateCondition($rent, $conditionsRule1, $conditionsDiscount1)) {
                    $Rentaldiscountval = 0;
                }
            }
        }

        if ($initial_discount > 0) {
            $initial_fee = $returns['initial_fee'] ? preg_replace('/[^0-9.]/', '', $returns['initial_fee']) : 0;
            if (!empty($conditionsCod1) && $checkVal && $initial_fee) {
                if (!$this->evaluateCondition($initial_fee, $conditionsRule1, $conditionsDiscount1)) {
                    $initial_discount = 0;
                }
            }
        }

        if ($Rentaldiscountval > 0 && $RentalDiscountType === 'flat') {
            $returns['rent_discount'] = ($rent <= $Rentaldiscountval) ? $rent : $Rentaldiscountval;
        } elseif ($Rentaldiscountval > 0 && $RentalDiscountType === 'percent') {
            $returns['rent_discount'] = sprintf('%0.2f', ($rent * $Rentaldiscountval) / 100);
        }

        if ($initial_discount > 0 && $initial_discount_type === 'flat') {
            $returns['initial_fee_discount'] = ($initial_fee <= $initial_discount) ? $initial_fee : $initial_discount;
        } elseif ($initial_discount > 0 && $initial_discount_type === 'percent') {
            $returns['initial_fee_discount'] = sprintf('%0.2f', ($initial_fee * $initial_discount) / 100);
        }

        $returns['discount_des'] = $title;
        return $returns;
    }

    public function usePromoCode(array $returns, $userid = ''): array
    {
        $returns['rent_discount'] = 0;
        $returns['initial_fee_discount'] = 0;
        $returns['original_initial_fee'] = $returns['initial_fee'];
        $returns['discount_des'] = '';

        $acceptedRule = DB::table('promo_terms')->where('user_id', $userid)->first();
        if (empty($acceptedRule)) {
            return $returns;
        }

        $coupon = DB::table('promotion_rules')
            ->where('status', 1)
            ->where('id', $acceptedRule->promo_rule_id)
            ->first();
        if (empty($coupon)) {
            return $returns;
        }

        $promoCons = !empty($coupon->conditions)
            ? json_decode($coupon->conditions, true)
            : ['con1' => '', 'discount1' => '', 'rule1' => ''];

        if (($promoCons['con1'] ?? '') === 'hitch') {
            $isHitch = DB::table('hitch_leads')->where('user_id', $userid)->first();
            if (empty($isHitch)) {
                return $returns;
            }
        }

        if (($promoCons['con1'] ?? '') === 'user' && ($promoCons['discount1'] ?? '') != $userid) {
            return $returns;
        }

        $RentalDiscountType = $coupon->type;
        $Rentaldiscountval = $coupon->discount;
        $title = $coupon->title;
        $initial_discount = $coupon->initial_discount;
        $initial_discount_type = $coupon->initial_discount_type;

        $conditionsCod1 = !empty($promoCons['con1']) ? $promoCons['con1'] : '';
        $conditionsRule1 = !empty($promoCons['rule1']) ? $promoCons['rule1'] : '==';
        $conditionsDiscount1 = !empty($promoCons['discount1']) ? $promoCons['discount1'] : '0';

        if ($Rentaldiscountval) {
            $rent = $returns['rent'] ? preg_replace('/[^0-9.]/', '', $returns['rent']) : 0;
            $returns['rent_promo'] = ['type' => $RentalDiscountType, 'discountval' => $Rentaldiscountval];

            if (!empty($conditionsCod1) && $rent) {
                if (!$this->evaluateCondition($rent, $conditionsRule1, $conditionsDiscount1)) {
                    $Rentaldiscountval = 0;
                }
            }
        }

        if ($initial_discount) {
            $initial_fee = $returns['initial_fee'] ? preg_replace('/[^0-9.]/', '', $returns['initial_fee']) : 0;
            if (!empty($conditionsCod1) && $initial_fee) {
                if (!$this->evaluateCondition($initial_fee, $conditionsRule1, $conditionsDiscount1)) {
                    $initial_discount = 0;
                }
            }
        }

        $original_initial_fee = $returns['initial_fee'];
        if ($Rentaldiscountval && $RentalDiscountType === 'flat') {
            $returns['rent_discount'] = ($rent <= $Rentaldiscountval) ? $rent : $Rentaldiscountval;
        } elseif ($Rentaldiscountval && $RentalDiscountType === 'percent') {
            $returns['rent_discount'] = sprintf('%0.2f', ($rent * $Rentaldiscountval) / 100);
        }

        if ($initial_discount && $initial_discount_type === 'flat') {
            $original_initial_fee = $returns['initial_fee'] + $initial_discount;
        } elseif ($initial_discount && $initial_discount_type === 'percent') {
            $original_initial_fee = sprintf('%0.2f', ($returns['initial_fee'] * 100) / (100 - $initial_discount));
        }

        $returns['original_initial_fee'] = $original_initial_fee;
        $returns['initial_fee_discount'] = ($original_initial_fee - $returns['initial_fee']);
        $returns['discount_des'] = $title;
        return $returns;
    }

    public function useRentalPromoCode(array $returns, $userid = ''): array
    {
        $returns['rent_discount'] = 0;
        $returns['discount_des'] = '';

        $acceptedRule = DB::table('promo_terms')->where('user_id', $userid)->first();
        if (empty($acceptedRule)) {
            return $returns;
        }

        $coupon = DB::table('promotion_rules')
            ->where('status', 1)
            ->where('id', $acceptedRule->promo_rule_id)
            ->first();
        if (empty($coupon)) {
            return $returns;
        }

        $promoCons = !empty($coupon->conditions)
            ? json_decode($coupon->conditions, true)
            : ['con1' => '', 'discount1' => '', 'rule1' => ''];

        if (($promoCons['con1'] ?? '') === 'hitch') {
            $isHitch = DB::table('hitch_leads')->where('user_id', $userid)->first();
            if (empty($isHitch)) {
                return $returns;
            }
        }

        if (($promoCons['con1'] ?? '') === 'user' && ($promoCons['discount1'] ?? '') != $userid) {
            return $returns;
        }

        $RentalDiscountType = $coupon->type;
        $Rentaldiscountval = $coupon->discount;
        $title = $coupon->title;

        $conditionsCod1 = !empty($promoCons['con1']) ? $promoCons['con1'] : '';
        $conditionsRule1 = !empty($promoCons['rule1']) ? $promoCons['rule1'] : '==';
        $conditionsDiscount1 = !empty($promoCons['discount1']) ? $promoCons['discount1'] : '0';

        if ($Rentaldiscountval) {
            $rent = $returns['rent'] ? preg_replace('/[^0-9.]/', '', $returns['rent']) : 0;
            $returns['rent_promo'] = ['type' => $RentalDiscountType, 'discountval' => $Rentaldiscountval];

            if (!empty($conditionsCod1) && $rent) {
                if (!$this->evaluateCondition($rent, $conditionsRule1, $conditionsDiscount1)) {
                    $Rentaldiscountval = 0;
                }
            }
        }

        if ($Rentaldiscountval && $RentalDiscountType === 'flat') {
            $returns['rent_discount'] = ($rent <= $Rentaldiscountval) ? $rent : $Rentaldiscountval;
        } elseif ($Rentaldiscountval && $RentalDiscountType === 'percent') {
            $returns['rent_discount'] = sprintf('%0.2f', ($rent * $Rentaldiscountval) / 100);
        }

        $returns['discount_des'] = $title;
        return $returns;
    }

    public function validatePromo(string $code, $userid): array
    {
        $returns = ['status' => false, 'message' => 'Sorry, coupon code is not valid'];

        $promo = DB::table('promotion_rules')
            ->where('status', 1)
            ->where('promo', strtoupper($code))
            ->first();

        if (!empty($promo) && !empty($userid)) {
            $promoTerm = DB::table('promo_terms')->where('user_id', $userid)->first();
            $termId = $promoTerm->id ?? null;

            DB::table('promo_terms')->updateOrInsert(
                ['id' => $termId ?? 0],
                ['user_id' => $userid, 'promo_rule_id' => $promo->id, 'created' => now(), 'modified' => now()]
            );
            if (empty($termId)) {
                DB::table('promo_terms')->insert([
                    'user_id' => $userid,
                    'promo_rule_id' => $promo->id,
                    'created' => now(),
                    'modified' => now(),
                ]);
            } else {
                DB::table('promo_terms')->where('id', $termId)->update([
                    'promo_rule_id' => $promo->id,
                    'modified' => now(),
                ]);
            }

            $returns = ['status' => true, 'message' => 'Promo code is applied successfully'];
        }

        return $returns;
    }

    public function getUserPromo($userid)
    {
        if (!empty($userid)) {
            $promoTerm = DB::table('promo_terms')
                ->join('promotion_rules', 'promotion_rules.id', '=', 'promo_terms.promo_rule_id')
                ->where('promo_terms.user_id', $userid)
                ->where('promotion_rules.status', 1)
                ->select('promotion_rules.*', 'promo_terms.id as promo_term_id')
                ->first();

            return $promoTerm ?: false;
        }

        return false;
    }

    public function applyPromoIdToUser($promoid, $userid): array
    {
        $returns = ['status' => false, 'message' => 'Sorry, coupon code is not valid'];

        $promo = DB::table('promotion_rules')
            ->where('status', 1)
            ->where('id', $promoid)
            ->first();

        if (!empty($promo) && !empty($userid)) {
            $promoTerm = DB::table('promo_terms')->where('user_id', $userid)->first();
            $termId = $promoTerm->id ?? null;

            if (empty($termId)) {
                DB::table('promo_terms')->insert([
                    'user_id' => $userid,
                    'promo_rule_id' => $promo->id,
                    'created' => now(),
                    'modified' => now(),
                ]);
            } else {
                DB::table('promo_terms')->where('id', $termId)->update([
                    'promo_rule_id' => $promo->id,
                    'modified' => now(),
                ]);
            }

            $returns = ['status' => true, 'message' => 'The discount has been applied to your account. Please continue in booking your vehicle!'];
        }

        return $returns;
    }

    public function removePromoIdToUser($userid): array
    {
        DB::table('promo_terms')->where('user_id', $userid)->delete();
        return ['status' => true, 'message' => 'All discounts are cleared, from your account.'];
    }

    public function getPromoDetails($userid = 0): array
    {
        $returns = [];
        $promos = DB::table('promotion_rules')->where('status', 1)->get();

        foreach ($promos as $promo) {
            if (empty($promo->conditions)) {
                continue;
            }
            $promoCons = json_decode($promo->conditions, true);
            if (empty($promoCons)) {
                continue;
            }

            if (($promoCons['con1'] ?? '') === 'hitch') {
                $isHitch = DB::table('hitch_leads')->where('user_id', $userid)->first();
                if (empty($isHitch)) {
                    continue;
                }
            }

            if (($promoCons['con1'] ?? '') === 'user' && ($promoCons['discount1'] ?? '') != $userid) {
                continue;
            }

            $returns[] = ['id' => $promo->id, 'title' => $promo->title, 'terms' => $promo->terms];
        }

        return $returns;
    }

    /**
     * Safe condition evaluation replacing legacy eval() calls.
     */
    protected function evaluateCondition($value, string $operator, $threshold): bool
    {
        $value = (float) $value;
        $threshold = (float) $threshold;

        return match ($operator) {
            '==' => $value == $threshold,
            '!=' => $value != $threshold,
            '>=' => $value >= $threshold,
            '<=' => $value <= $threshold,
            '>' => $value > $threshold,
            '<' => $value < $threshold,
            default => false,
        };
    }
}
