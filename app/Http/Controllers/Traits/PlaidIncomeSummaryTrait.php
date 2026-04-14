<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Ported from CakePHP app/Controller/Traits/PlaidIncomeSummery.php
 *
 * Builds month-wise income summaries from Plaid bank income history.
 */
trait PlaidIncomeSummaryTrait
{
    protected function _combinedIncomeSummery($user_id)
    {
        return $this->_monthWiseIncomeSummery($user_id);
    }

    /**
     * Build month-wise income summary from Plaid income history.
     *
     * Output:
     * [
     *   "2025-10" => [
     *      "start_date" => "2025-10-07",
     *      "end_date" => "2025-10-31",
     *      "iso_currency_code" => "USD",
     *      "total_amount" => 11290.12,
     *      "unique_transactions_count" => 7
     *   ],
     *   ...
     * ]
     */
    protected function _monthWiseIncomeSummery($user_id)
    {
        $plaidUser = DB::table('plaid_users')->where('user_id', $user_id)->first();
        if (empty($plaidUser) || empty($plaidUser->user_token)) {
            return [];
        }

        $accountCount = DB::table('plaid_users')
            ->where('user_id', $plaidUser->user_id)
            ->count();

        // TODO: Replace with injected PlaidClient service when migrated
        // Legacy: $this->Plaid->getIncomeHistory($plaidUser->user_token, $accountCount)
        $IncomeObj = $this->Plaid->getIncomeHistory($plaidUser->user_token, $accountCount);

        if (empty($IncomeObj) || !isset($IncomeObj['status']) || !$IncomeObj['status']) {
            return [];
        }
        return $this->_normalizeData($IncomeObj);
    }

    public function _normalizeData($IncomeObj)
    {
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
                        $amount = isset($t['amount']) ? (float) $t['amount'] : 0.0;
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
                    $monthTotalAmount += (float) $hs['total_amount'];
                }
            }

            if ((float) $monthTotalAmount > 0) {
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
