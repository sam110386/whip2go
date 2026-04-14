<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\PlaidUser;
// Assuming a Plaid service or wrapper exists
use App\Libraries\Plaid;

trait PlaidIncomeSummery {

    public function _combinedIncomeSummery($user_id)
    {
        // Returns month-wise income totals (YYYY-MM => total_amount)
        return $this->_monthWiseIncomeSummery($user_id);
    }

    /**
     * Build month-wise income summary from Plaid income history.
     */
    public function _monthWiseIncomeSummery($user_id)
    {
        $plaidObj = PlaidUser::where('user_id', $user_id)->first();
        if (empty($plaidObj) || empty($plaidObj->user_token)) {
            return [];
        }

        $accountCount = PlaidUser::where('user_id', $plaidObj->user_id)->count();
        
        $plaidService = new Plaid();
        if (!method_exists($plaidService, 'getIncomeHistory')) {
            \Log::warning("Plaid service or getIncomeHistory method not found.");
            return [];
        }
        
        $IncomeObj = $plaidService->getIncomeHistory($plaidObj->user_token, $accountCount);
        
        if (empty($IncomeObj) || !isset($IncomeObj['status']) || !$IncomeObj['status']) {
            return [];
        }
        return $this->_normalizeData($IncomeObj);
    }

    public function _normalizeData($IncomeObj){

        $bankAccounts = [];
        $transactionsByMonth = [];
        foreach (($IncomeObj['income']['bank_income'] ?? []) as $bank_income) {
            if (!isset($bank_income['items'])) {
                continue;
            }
            foreach ($bank_income['items'] as $items) {
                if (!isset($items['bank_income_sources'])) {
                    continue;
                }
                foreach ($items['bank_income_accounts'] as $itm) {
                    $bankAccounts[$itm['account_id']] = $itm;
                }
                foreach ($items['bank_income_sources'] as $bank_income_sources) {
                    foreach ($bank_income_sources['historical_summary'] as $historical_summary) {
                        $startDate = isset($historical_summary['start_date']) ? $historical_summary['start_date'] : null;
                        if (empty($startDate)) {
                            continue;
                        }
                        $monthKey = date('Y-m', strtotime($startDate));
                        if (!isset($transactionsByMonth[$monthKey])) {
                            $transactionsByMonth[$monthKey] = [];
                        }
                        $transactionsByMonth[$monthKey][] = [
                            'account_id' => $bank_income_sources['account_id'],
                            'historical_summary' => $historical_summary,
                        ];
                    }
                }
            }
        }

        uksort($transactionsByMonth, function ($a1, $a2) {
            return strtotime($a2) - strtotime($a1);
        });

        // Deduplicate transactions per month using (amount-date-name-mask)
        $monthIncome = [];
        foreach ($transactionsByMonth as $monthKey => $transactionGroups) {
            $uniqueSeen = [];
            $monthTotalAmount = 0.0;
            $monthStartDate = null;
            $monthEndDate = null;
            $currency = null;
            $uniqueTransactionsCount = 0;

            foreach ($transactionGroups as $transact) {
                $hs = $transact['historical_summary'] ?? [];
                if ($currency === null && !empty($hs['iso_currency_code'])) {
                    $currency = $hs['iso_currency_code'];
                }

                if (!empty($hs['start_date'])) {
                    $monthStartDate = empty($monthStartDate) ? $hs['start_date'] : min($monthStartDate, $hs['start_date']);
                }
                if (!empty($hs['end_date'])) {
                    $monthEndDate = empty($monthEndDate) ? $hs['end_date'] : max($monthEndDate, $hs['end_date']);
                }

                if (!empty($hs['transactions']) && is_array($hs['transactions'])) {
                    foreach ($hs['transactions'] as $t) {
                        $amount = isset($t['amount']) ? (float)$t['amount'] : 0.0;
                        $date = $t['date'] ?? '';
                        $name = $t['name'] ?? '';
                        $mask = isset($bankAccounts[$transact['account_id']]) && isset($bankAccounts[$transact['account_id']]['mask'])
                            ? $bankAccounts[$transact['account_id']]['mask']
                            : '';

                        $keyCheck = $amount . '-' . $date . '-' . $name . '-' . $mask;
                        if (!isset($uniqueSeen[$keyCheck])) {
                            $uniqueSeen[$keyCheck] = true;
                            $monthTotalAmount += $amount;
                            $uniqueTransactionsCount++;
                        }
                    }
                } elseif (isset($hs['total_amount'])) {
                    // Fallback if transactions are not present
                    $monthTotalAmount += (float)$hs['total_amount'];
                }
            }

            // Skip months where Plaid returned no income
            if ((float)$monthTotalAmount > 0) {
                $monthIncome[$monthKey] = [
                    'start_date' => $monthStartDate,
                    'end_date' => $monthEndDate,
                    'iso_currency_code' => $currency,
                    'total_amount' => $monthTotalAmount,
                    'unique_transactions_count' => $uniqueTransactionsCount,
                ];
            }
        }

        return $monthIncome;
    }
}
