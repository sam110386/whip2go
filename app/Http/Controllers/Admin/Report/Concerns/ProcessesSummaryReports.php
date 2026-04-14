<?php

namespace App\Http\Controllers\Admin\Report\Concerns;

use App\Services\Legacy\Common;
use App\Services\Legacy\Report\SummaryReportService;
use Illuminate\Support\Facades\DB;

/**
 * Ports CakePHP SummaryController::admin_processReport loop verbatim (DB + save wiring).
 */
trait ProcessesSummaryReports
{
    protected function runSummaryProcessReportLoop(Common $common, SummaryReportService $summaryReportService): void
    {
        while (true) {
            $rows = DB::table('summary_reports')
                ->leftJoin('rev_settings', 'rev_settings.user_id', '=', 'summary_reports.user_id')
                ->where('summary_reports.processed', 0)
                ->orderByDesc('summary_reports.id')
                ->select(
                    'summary_reports.*',
                    'rev_settings.rev as __rev_setting_rev',
                    'rev_settings.tax_included as __rev_setting_tax_included'
                )
                ->limit(10)
                ->get();

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $dbRow) {
                $flat = (array) $dbRow;
                $revSettingRev = $flat['__rev_setting_rev'] ?? null;
                $revSettingTaxIncluded = $flat['__rev_setting_tax_included'] ?? null;
                unset($flat['__rev_setting_rev'], $flat['__rev_setting_tax_included']);

                $record = [
                    'SummaryReport' => $flat,
                    'RevSetting' => ['rev' => $revSettingRev, 'tax_included' => $revSettingTaxIncluded],
                ];

                foreach (['differ_m_initial_fee', 'past_m_initial_fee'] as $_initKey) {
                    if (! isset($record['SummaryReport'][$_initKey])) {
                        $record['SummaryReport'][$_initKey] = 0;
                    }
                }

                $record['SummaryReport']['processed'] = 1;
                $bookinginterval = $remainingDays = 1;
                $differday = $pastday = $processnext = 0;
                if ($record['SummaryReport']['start_datetime'] < $record['SummaryReport']['date_from'] && $record['SummaryReport']['end_datetime'] < $record['SummaryReport']['date_from']) {
                    $start_datetime = $record['SummaryReport']['start_datetime'];
                    $end_datetime = $record['SummaryReport']['end_datetime'];
                    $bookinginterval = $common->days_between_dates($start_datetime, $end_datetime);
                    $date_from = $record['SummaryReport']['date_from'].' '.date('H:i:s', strtotime($start_datetime));
                    $pastday = $daysinterval = $bookinginterval;
                    $remainingDays = (int) ($bookinginterval - $daysinterval);
                    $processnext = 1;
                }
                if (! $processnext && $record['SummaryReport']['start_datetime'] < $record['SummaryReport']['date_from']) {
                    $start_datetime = $record['SummaryReport']['start_datetime'];
                    $end_datetime = $record['SummaryReport']['end_datetime'];
                    $bookinginterval = $common->days_between_dates($start_datetime, $end_datetime);
                    $date_from = $record['SummaryReport']['date_from'].' '.date('H:i:s', strtotime($start_datetime));
                    $pastday = $daysinterval = $common->days_between_dates($date_from, $start_datetime);
                    $remainingDays = (int) ($bookinginterval - $daysinterval);
                }
                if (! $processnext && $record['SummaryReport']['start_datetime'] > $record['SummaryReport']['date_from'] && $record['SummaryReport']['end_datetime'] > $record['SummaryReport']['date_to']) {
                    $start_datetime = $record['SummaryReport']['start_datetime'];
                    $end_datetime = $record['SummaryReport']['end_datetime'];
                    $bookinginterval = $common->days_between_dates($start_datetime, $end_datetime);
                    $date_to = $record['SummaryReport']['date_to'].' '.date('H:i:s', strtotime($start_datetime));
                    $remainingDays = $common->days_between_dates($start_datetime, $date_to);
                    $differday = $daysinterval = (int) ($bookinginterval - $remainingDays);
                }
                if (! $processnext && $record['SummaryReport']['start_datetime'] > $record['SummaryReport']['date_from'] && $record['SummaryReport']['end_datetime'] < $record['SummaryReport']['date_to']) {
                    $start_datetime = $record['SummaryReport']['start_datetime'];
                    $end_datetime = $record['SummaryReport']['end_datetime'];
                    $bookinginterval = $common->days_between_dates($start_datetime, $end_datetime);
                    $remainingDays = $bookinginterval;
                    $differday = $daysinterval = (int) ($bookinginterval - $remainingDays);
                }
                if ($processnext && $record['SummaryReport']['end_datetime'] > $record['SummaryReport']['date_to']) {
                    $start_datetime = $record['SummaryReport']['start_datetime'];
                    $end_datetime = $record['SummaryReport']['end_datetime'];
                    $bookinginterval = $common->days_between_dates($start_datetime, $end_datetime);
                    $date_to = $record['SummaryReport']['date_to'].' '.date('H:i:s', strtotime($end_datetime));
                    $differday = $daysinterval = $common->days_between_dates($end_datetime, $date_to);
                    $remainingDays = (int) ($bookinginterval - $daysinterval);
                }

                if ($record['SummaryReport']['booking_status'] == 1) {
                    $start_datetime = $record['SummaryReport']['start_datetime'];
                    $end_datetime = $record['SummaryReport']['end_datetime'];
                    $bookinginterval = $common->days_between_dates($start_datetime, $end_datetime);
                    $date_to = $record['SummaryReport']['date_to'].' '.date('H:i:s', strtotime($start_datetime));
                    $daysinterval = $common->days_between_dates($start_datetime, $date_to);

                    $remainingDays = (int) ($daysinterval);
                    $differday = $pastday = 0;
                    if ($record['SummaryReport']['start_datetime'] < $record['SummaryReport']['date_from']) {
                        $pastday = $common->days_between_dates($record['SummaryReport']['date_from'], $start_datetime);
                        $remainingDays = $remainingDays - $pastday;
                    }
                    if ($record['SummaryReport']['end_datetime'] > $record['SummaryReport']['date_to']) {
                        $differday = $common->days_between_dates($record['SummaryReport']['date_to'], $end_datetime);
                        $remainingDays = $remainingDays - $differday;
                    }
                }

                $record['SummaryReport']['past_m_rent'] = sprintf('%0.2f', (($record['SummaryReport']['rent'] / $bookinginterval) * $pastday));
                $record['SummaryReport']['past_m_lateness_fee'] = sprintf('%0.2f', (($record['SummaryReport']['lateness_fee'] / $bookinginterval) * $pastday));
                $record['SummaryReport']['past_m_emf'] = sprintf('%0.2f', (($record['SummaryReport']['extra_mileage_fee'] / $bookinginterval) * $pastday));
                $record['SummaryReport']['past_m_tax'] = sprintf('%0.2f', (($record['SummaryReport']['tax'] / $bookinginterval) * $pastday));
                $record['SummaryReport']['past_m_dia_fee'] = sprintf('%0.2f', (($record['SummaryReport']['dia_fee'] / $bookinginterval) * $pastday));
                $record['SummaryReport']['past_m_insurance_amt'] = sprintf('%0.2f', (($record['SummaryReport']['insurance_amt'] / $bookinginterval) * $pastday));
                $record['SummaryReport']['past_m_dia_insu'] = sprintf('%0.2f', (($record['SummaryReport']['dia_insu'] / $bookinginterval) * $pastday));
                $record['SummaryReport']['past_m_initial_fee'] = sprintf('%0.2f', (($record['SummaryReport']['initial_fee'] / $bookinginterval) * $pastday));

                $record['SummaryReport']['differ_m_rent'] = sprintf('%0.2f', (($record['SummaryReport']['rent'] / $bookinginterval) * $differday));
                $record['SummaryReport']['differ_m_lateness_fee'] = sprintf('%0.2f', (($record['SummaryReport']['lateness_fee'] / $bookinginterval) * $differday));
                $record['SummaryReport']['differ_m_emf'] = sprintf('%0.2f', (($record['SummaryReport']['extra_mileage_fee'] / $bookinginterval) * $differday));
                $record['SummaryReport']['differ_m_tax'] = sprintf('%0.2f', (($record['SummaryReport']['tax'] / $bookinginterval) * $differday));
                $record['SummaryReport']['differ_m_dia_fee'] = sprintf('%0.2f', (($record['SummaryReport']['dia_fee'] / $bookinginterval) * $differday));
                $record['SummaryReport']['differ_m_insurance_amt'] = sprintf('%0.2f', (($record['SummaryReport']['insurance_amt'] / $bookinginterval) * $differday));
                $record['SummaryReport']['differ_m_dia_insu'] = sprintf('%0.2f', (($record['SummaryReport']['dia_insu'] / $bookinginterval) * $differday));
                $record['SummaryReport']['differ_m_initial_fee'] = sprintf('%0.2f', (($record['SummaryReport']['initial_fee'] / $bookinginterval) * $differday));

                if (! $processnext) {
                    $record['SummaryReport']['rent'] = sprintf('%0.2f', (($record['SummaryReport']['rent'] / $bookinginterval) * $remainingDays));
                    $record['SummaryReport']['lateness_fee'] = sprintf('%0.2f', (($record['SummaryReport']['lateness_fee'] / $bookinginterval) * $remainingDays));
                    $record['SummaryReport']['extra_mileage_fee'] = sprintf('%0.2f', (($record['SummaryReport']['extra_mileage_fee'] / $bookinginterval) * $remainingDays));
                    $record['SummaryReport']['tax'] = sprintf('%0.2f', (($record['SummaryReport']['tax'] / $bookinginterval) * $remainingDays));
                    $record['SummaryReport']['dia_fee'] = sprintf('%0.2f', (($record['SummaryReport']['dia_fee'] / $bookinginterval) * $remainingDays));
                    $record['SummaryReport']['insurance_amt'] = sprintf('%0.2f', (($record['SummaryReport']['insurance_amt'] / $bookinginterval) * $remainingDays));
                    $record['SummaryReport']['dia_insu'] = sprintf('%0.2f', (($record['SummaryReport']['dia_insu'] / $bookinginterval) * $remainingDays));
                    $record['SummaryReport']['initial_fee'] = sprintf('%0.2f', (($record['SummaryReport']['initial_fee'] / $bookinginterval) * $remainingDays));
                }
                if ($processnext) {
                    $record['SummaryReport']['past_m_rent'] = $record['SummaryReport']['rent'];
                    $record['SummaryReport']['past_m_lateness_fee'] = $record['SummaryReport']['lateness_fee'];
                    $record['SummaryReport']['past_m_emf'] = $record['SummaryReport']['extra_mileage_fee'];
                    $record['SummaryReport']['past_m_tax'] = $record['SummaryReport']['tax'];
                    $record['SummaryReport']['past_m_dia_fee'] = $record['SummaryReport']['dia_fee'];
                    $record['SummaryReport']['past_m_insurance_amt'] = $record['SummaryReport']['insurance_amt'];
                    $record['SummaryReport']['past_m_dia_insu'] = $record['SummaryReport']['dia_insu'];
                    $record['SummaryReport']['past_m_initial_fee'] = $record['SummaryReport']['initial_fee'];
                    $record['SummaryReport']['rent'] = 0;
                    $record['SummaryReport']['lateness_fee'] = 0;
                    $record['SummaryReport']['extra_mileage_fee'] = 0;
                    $record['SummaryReport']['tax'] = 0;
                    $record['SummaryReport']['dia_fee'] = 0;
                    $record['SummaryReport']['insurance_amt'] = 0;
                    $record['SummaryReport']['dia_insu'] = 0;
                    $record['SummaryReport']['initial_fee'] = 0;
                }

                $revPart = $revPart1 = $revPart2 = $revPart3 = $transfered = $differtransfered = $net_paid_payout = 0;
                $revshare = ! empty($record['RevSetting']['rev']) ? $record['RevSetting']['rev'] : config('legacy.OWNER_PART', 85);
                $taxIncluded = (isset($record['RevSetting']['tax_included']) && $record['RevSetting']['tax_included'] == 0) ? false : true;

                if ($taxIncluded) {
                    $revPart1 = sprintf('%0.2f', (($record['SummaryReport']['initial_fee'] + $record['SummaryReport']['rent'] + $record['SummaryReport']['extra_mileage_fee'] + $record['SummaryReport']['tax'] + $record['SummaryReport']['lateness_fee'] - $record['SummaryReport']['dia_fee']) * $revshare / 100));
                } else {
                    $revPart1 = sprintf('%0.2f', (($record['SummaryReport']['initial_fee'] + $record['SummaryReport']['rent'] + $record['SummaryReport']['extra_mileage_fee'] + $record['SummaryReport']['lateness_fee'] - $record['SummaryReport']['dia_fee']) * $revshare / 100)) + $record['SummaryReport']['tax'];
                }

                if ($taxIncluded) {
                    $revPart2 = sprintf('%0.2f', (($record['SummaryReport']['differ_m_initial_fee'] + $record['SummaryReport']['differ_m_rent'] + $record['SummaryReport']['differ_m_emf'] + $record['SummaryReport']['differ_m_tax'] + $record['SummaryReport']['differ_m_lateness_fee'] - $record['SummaryReport']['differ_m_dia_fee']) * $revshare / 100));
                } else {
                    $revPart2 = sprintf('%0.2f', ((($record['SummaryReport']['differ_m_initial_fee'] + $record['SummaryReport']['differ_m_rent'] + $record['SummaryReport']['differ_m_emf'] + $record['SummaryReport']['differ_m_lateness_fee'] - $record['SummaryReport']['differ_m_dia_fee'])) * $revshare / 100)) + $record['SummaryReport']['differ_m_tax'];
                }
                if ($taxIncluded) {
                    $revPart3 = sprintf('%0.2f', (($record['SummaryReport']['past_m_initial_fee'] + $record['SummaryReport']['past_m_rent'] + $record['SummaryReport']['past_m_emf'] + $record['SummaryReport']['past_m_tax'] + $record['SummaryReport']['past_m_lateness_fee'] - $record['SummaryReport']['past_m_dia_fee']) * $revshare / 100));
                } else {
                    $revPart3 = sprintf('%0.2f', ((($record['SummaryReport']['past_m_initial_fee'] + $record['SummaryReport']['past_m_rent'] + $record['SummaryReport']['past_m_emf'] + $record['SummaryReport']['past_m_lateness_fee'] - $record['SummaryReport']['past_m_dia_fee'])) * $revshare / 100)) + $record['SummaryReport']['past_m_tax'];
                }
                $revPart = sprintf('%0.2f', ($revPart1 + $revPart2 + $revPart3));

                $csrecords = DB::table('payment_reports')
                    ->whereIn('type', [2, 3, 5, 6, 16, 4, 14])
                    ->where('cs_order_id', $record['SummaryReport']['id'])
                    ->get();

                $insurance = $emfinsurance = $past_m_insurance = $past_m_emfinsurance = $differ_m_insurance = $differ_m_emfinsurance = $past_m_total_collected = $differ_m_total_collected = $total_collected = $walletRefund = $stripeRefund = $rentWalletRefund = $insuWalletRefund = $rentStripeRefund = $insuStripeRefund = 0;
                foreach ($csrecords as $csRow) {
                    $csrecord = ['PaymentReport' => (array) $csRow];
                    $insideMonth = (date('Y-m-d', strtotime($csrecord['PaymentReport']['created'])) >= $record['SummaryReport']['date_from'] && date('Y-m-d', strtotime($csrecord['PaymentReport']['created'])) <= $record['SummaryReport']['date_to']) ? true : false;
                    $walletMonth = (date('Y-m-d', strtotime($csrecord['PaymentReport']['created'])) <= $record['SummaryReport']['date_to']) ? true : false;
                    if ($walletMonth && in_array($csrecord['PaymentReport']['type'], [2, 3, 5, 6, 7, 16]) && $csrecord['PaymentReport']['txn_type'] == 2 && $csrecord['PaymentReport']['source'] == 'wallet') {
                        $rentWalletRefund += abs($csrecord['PaymentReport']['amount']);
                    }
                    if ($walletMonth && in_array($csrecord['PaymentReport']['type'], [4, 14]) && $csrecord['PaymentReport']['txn_type'] == 2 && $csrecord['PaymentReport']['source'] == 'wallet') {
                        $insuWalletRefund += abs($csrecord['PaymentReport']['amount']);
                    }
                    if ($insideMonth && in_array($csrecord['PaymentReport']['type'], [2, 3, 5, 6, 7, 16]) && $csrecord['PaymentReport']['txn_type'] == 2 && $csrecord['PaymentReport']['source'] == 'stripe') {
                        $rentStripeRefund += abs($csrecord['PaymentReport']['amount']);
                    }
                    if ($insideMonth && in_array($csrecord['PaymentReport']['type'], [4, 14]) && $csrecord['PaymentReport']['txn_type'] == 2 && $csrecord['PaymentReport']['source'] == 'stripe') {
                        $insuStripeRefund += abs($csrecord['PaymentReport']['amount']);
                    }

                    if ($walletMonth && $csrecord['PaymentReport']['txn_type'] == 2 && $csrecord['PaymentReport']['source'] == 'wallet') {
                        $walletRefund += abs($csrecord['PaymentReport']['amount']);
                        continue;
                    }
                    if ($insideMonth && $csrecord['PaymentReport']['txn_type'] == 2 && $csrecord['PaymentReport']['source'] == 'stripe') {
                        $stripeRefund += abs($csrecord['PaymentReport']['amount']);
                        continue;
                    }

                    if ($csrecord['PaymentReport']['charged_at'] < $record['SummaryReport']['date_from'] && $csrecord['PaymentReport']['type'] == 4) {
                        $past_m_insurance += $csrecord['PaymentReport']['amount'];
                    } elseif (date('Y-m-d', strtotime($csrecord['PaymentReport']['charged_at'])) > $record['SummaryReport']['date_to'] && $csrecord['PaymentReport']['type'] == 4) {
                        $differ_m_insurance += $csrecord['PaymentReport']['amount'];
                    } elseif ($csrecord['PaymentReport']['type'] == 4) {
                        $insurance += $csrecord['PaymentReport']['amount'];
                    }
                    if ($csrecord['PaymentReport']['charged_at'] < $record['SummaryReport']['date_from'] && $csrecord['PaymentReport']['type'] == 14) {
                        $past_m_emfinsurance += $csrecord['PaymentReport']['amount'];
                    } elseif (date('Y-m-d', strtotime($csrecord['PaymentReport']['charged_at'])) > $record['SummaryReport']['date_to'] && $csrecord['PaymentReport']['type'] == 14) {
                        $differ_m_emfinsurance += $csrecord['PaymentReport']['amount'];
                    } elseif ($csrecord['PaymentReport']['type'] == 14) {
                        $emfinsurance += $csrecord['PaymentReport']['amount'];
                    }
                    if ($csrecord['PaymentReport']['type'] != 4 && $csrecord['PaymentReport']['type'] != 14) {
                        if ($csrecord['PaymentReport']['charged_at'] < $record['SummaryReport']['date_from']) {
                            $past_m_total_collected += $csrecord['PaymentReport']['amount'];
                        } elseif (date('Y-m-d', strtotime($csrecord['PaymentReport']['charged_at'])) > $record['SummaryReport']['date_to']) {
                            $differ_m_total_collected += $csrecord['PaymentReport']['amount'];
                        } else {
                            $total_collected += $csrecord['PaymentReport']['amount'];
                        }
                    }
                }

                $txns = DB::table('cs_payout_transactions')
                    ->where('cs_order_id', $record['SummaryReport']['id'])
                    ->select(['amount', 'created', 'stripe_amt'])
                    ->get();

                foreach ($txns as $txnRow) {
                    $txn = ['CsPayoutTransaction' => (array) $txnRow];
                    $net_paid_payout += $txn['CsPayoutTransaction']['stripe_amt'];
                    if ($txn['CsPayoutTransaction']['created'] >= $record['SummaryReport']['date_from'] && $txn['CsPayoutTransaction']['created'] <= $record['SummaryReport']['date_to']) {
                        $transfered += $txn['CsPayoutTransaction']['amount'];
                    } else {
                        $differtransfered += ! empty($txn) ? $txn['CsPayoutTransaction']['amount'] : 0;
                    }
                }
                $record['SummaryReport']['insurance_collected'] = $insurance;
                $record['SummaryReport']['past_m_insurance_collected'] = $past_m_insurance;
                $record['SummaryReport']['differ_m_insurance_collected'] = $differ_m_insurance;

                $record['SummaryReport']['dia_insu_collected'] = $emfinsurance;
                $record['SummaryReport']['past_m_dia_insu_collected'] = $past_m_emfinsurance;
                $record['SummaryReport']['differ_m_dia_insu_collected'] = $differ_m_emfinsurance;

                $record['SummaryReport']['past_m_total_collected'] = $past_m_total_collected;
                $record['SummaryReport']['differ_m_total_collected'] = $differ_m_total_collected;
                $record['SummaryReport']['total_collected'] = $total_collected;

                $record['SummaryReport']['rev_share'] = $revshare;
                $record['SummaryReport']['dealer_payout'] = $revPart1;
                $record['SummaryReport']['differ_m_dealer_payout'] = $revPart2;
                $record['SummaryReport']['past_m_payout'] = $revPart3;
                $record['SummaryReport']['total_payout'] = $revPart;
                $record['SummaryReport']['paid_payout'] = $transfered;
                $record['SummaryReport']['net_paid_payout'] = ($net_paid_payout > 0 ? $net_paid_payout : $transfered);
                $record['SummaryReport']['differ_paid_payout'] = $differtransfered;
                $record['SummaryReport']['wallet_refund'] = $walletRefund;
                $record['SummaryReport']['stripe_refund'] = $stripeRefund;
                $record['SummaryReport']['rent_wallet_refund'] = $rentWalletRefund;
                $record['SummaryReport']['insu_wallet_refund'] = $insuWalletRefund;
                $record['SummaryReport']['rent_stripe_refund'] = $rentStripeRefund;
                $record['SummaryReport']['insu_stripe_refund'] = $insuStripeRefund;

                $summaryReportService->save($record['SummaryReport']);
            }
        }
    }
}
