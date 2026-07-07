<?php

namespace App\Http\Controllers\Admin\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\Legacy\SummaryReport;
use App\Models\Legacy\PaymentReport;
use App\Models\Legacy\CsPayoutTransaction;
use App\Http\Controllers\Traits\ReportTrait;
use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;

class SummaryController extends LegacyAppController
{
    use ReportTrait;

    public function index(Request $request, $process = 0)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Summary Report';
        $datefrom = $dateto = '';
        $sessLimitName = "report_customer_limit";

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            Session::put($sessLimitName, $limit);
        } else {
            $limit = Session::get($sessLimitName, 50);
        }

        $lists = SummaryReport::orderBy('id', 'asc')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.report.summary.elements.summary', compact('title', 'lists', 'datefrom', 'dateto', 'process', 'limit'));
        }

        return view('admin.report.summary.index', compact('title', 'lists', 'datefrom', 'dateto', 'process', 'limit'));
    }
    public function generatereport(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if ($request->has('export')) {
            return $this->exportReport();
        }

        $data = $request->input('Search', []);

        if (empty($data['datefrom']) || empty($data['dateto'])) {
            return redirect()->back()->with('error', 'Sorry, something went wrong. Please try again');
        }

        $dtFrom = \DateTime::createFromFormat('m/Y', $data['datefrom']);
        $dtTo = \DateTime::createFromFormat('m/Y', $data['dateto']);
        $firstDay = $dtFrom ? $dtFrom->format('Y-m-01') : null;
        $lastDay = $dtTo ? $dtTo->format('Y-m-t') : null;

        if (empty($firstDay) || empty($lastDay)) {
            return redirect()->back()->with('error', 'Sorry, something went wrong. Please try again');
        }

        DB::statement('truncate summary_reports');

        $sql = "Select id from cs_orders where  ((`start_datetime`>='{$firstDay}' AND DATE_FORMAT(`start_datetime`,'%Y-%m-%d')<='{$lastDay}') OR (`end_datetime`>='{$firstDay}' AND DATE_FORMAT(`end_datetime`,'%Y-%m-%d')<='{$lastDay}')) UNION SELECT cs_order_id FROM payment_reports where charged_at>'{$firstDay}' and DATE_FORMAT(charged_at,'%Y-%m-%d')<='{$lastDay}'";

        $rows = DB::select($sql);

        foreach ($rows as $rowObj) {
            $row = (array) $rowObj;

            $orderId = (int) ($row['id'] ?? 0);
            if ($orderId === 0) {
                continue;
            }

            $query = "insert into summary_reports (id,user_id,increment_id,start_datetime,end_datetime,rent,lateness_fee,extra_mileage_fee,tax,dia_fee,insurance_amt,dia_insu,initial_fee,booking_status,date_from,date_to) 
            SELECT CsOrder.id,CsOrder.user_id,CsOrder.increment_id,CsOrder.start_datetime,CsOrder.end_datetime, `CsOrder`.`rent`, (CsOrder.damage_fee+CsOrder.lateness_fee+CsOrder.uncleanness_fee+CsOrder.cancellation_fee) as lateness_fee, `CsOrder`.`extra_mileage_fee`, (CsOrder.tax+CsOrder.emf_tax) as tax, `CsOrder`.`dia_fee`, `CsOrder`.`insurance_amt`, `CsOrder`.`dia_insu`, `CsOrder`.`initial_fee`, CsOrder.status,'{$firstDay}','{$lastDay}' FROM `cs_orders` AS `CsOrder`  WHERE CsOrder.id=" . $orderId;

            DB::unprepared($query);
        }

        return redirect(url('/admin/report/summary/index/1'));
    }
    public function processReport(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        // Prevent execution timeouts for heavy looping
        set_time_limit(0);

        while (true) {

            $records = SummaryReport::with('revSetting')
                ->where('processed', 0)
                ->orderBy('id', 'DESC')
                ->limit(10)
                ->get();

            if ($records->isEmpty()) {
                break;
            }

            foreach ($records as $record) {
                $record->processed = 1;
                $bookinginterval = $remainingDays = 1;
                $differday = $pastday = $processnext = 0;

                $startDt = Carbon::parse($record->start_datetime);
                $endDt = Carbon::parse($record->end_datetime);

                if ($record->start_datetime < $record->date_from && $record->end_datetime < $record->date_from) {
                    $bookinginterval = $this->commonService->days_between_dates($record->start_datetime, $record->end_datetime);
                    $dateFromWithTime = "{$record->date_from} {$startDt->format('H:i:s')}";
                    $pastday = $daysinterval = $bookinginterval;
                    $remainingDays = (int) ($bookinginterval - $daysinterval);
                    $processnext = 1;
                }

                if (!$processnext && $record->start_datetime < $record->date_from) {
                    $bookinginterval = $this->commonService->days_between_dates($record->start_datetime, $record->end_datetime);
                    $dateFromWithTime = "{$record->date_from} {$startDt->format('H:i:s')}";
                    $pastday = $daysinterval = $this->commonService->days_between_dates($dateFromWithTime, $record->start_datetime);
                    $remainingDays = (int) ($bookinginterval - $daysinterval);
                }

                if (!$processnext && $record->start_datetime > $record->date_from && $record->end_datetime > $record->date_to) {
                    $bookinginterval = $this->commonService->days_between_dates($record->start_datetime, $record->end_datetime);
                    $dateToWithTime = "{$record->date_to} {$startDt->format('H:i:s')}";
                    $remainingDays = $this->commonService->days_between_dates($record->start_datetime, $dateToWithTime);
                    $differday = $daysinterval = (int) ($bookinginterval - $remainingDays);
                }

                if (!$processnext && $record->start_datetime > $record->date_from && $record->end_datetime < $record->date_to) {
                    $bookinginterval = $this->commonService->days_between_dates($record->start_datetime, $record->end_datetime);
                    $remainingDays = $bookinginterval;
                    $differday = $daysinterval = (int) ($bookinginterval - $remainingDays);
                }

                if ($processnext && $record->end_datetime > $record->date_to) {
                    $bookinginterval = $this->commonService->days_between_dates($record->start_datetime, $record->end_datetime);
                    $dateToWithEndTime = "{$record->date_to} {$endDt->format('H:i:s')}";
                    $differday = $daysinterval = $this->commonService->days_between_dates($record->end_datetime, $dateToWithEndTime);
                    $remainingDays = (int) ($bookinginterval - $daysinterval);
                }

                if ($record->booking_status == 1) {
                    $bookinginterval = $this->commonService->days_between_dates($record->start_datetime, $record->end_datetime);
                    $dateToWithTime = "{$record->date_to} {$startDt->format('H:i:s')}";
                    $daysinterval = $this->commonService->days_between_dates($record->start_datetime, $dateToWithTime);
                    $remainingDays = (int) $daysinterval;
                    $differday = $pastday = 0;

                    if ($record->start_datetime < $record->date_from) {
                        $pastday = $this->commonService->days_between_dates($record->date_from, $record->start_datetime);
                        $remainingDays -= $pastday;
                    }

                    if ($record->end_datetime > $record->date_to) {
                        $differday = $this->commonService->days_between_dates($record->date_to, $record->end_datetime);
                        $remainingDays -= $differday;
                    }
                }

                // --- Calculation Blocks ---
                $divisor = $bookinginterval ?: 1; // Prevent Division by Zero if interval outputs 0

                // Past Days Pro-Rata Allocation
                $record->past_m_rent = sprintf('%0.2f', (($record->rent / $divisor) * $pastday));
                $record->past_m_lateness_fee = sprintf('%0.2f', (($record->lateness_fee / $divisor) * $pastday));
                $record->past_m_emf = sprintf('%0.2f', (($record->extra_mileage_fee / $divisor) * $pastday));
                $record->past_m_tax = sprintf('%0.2f', (($record->tax / $divisor) * $pastday));
                $record->past_m_dia_fee = sprintf('%0.2f', (($record->dia_fee / $divisor) * $pastday));
                $record->past_m_insurance_amt = sprintf('%0.2f', (($record->insurance_amt / $divisor) * $pastday));
                $record->past_m_dia_insu = sprintf('%0.2f', (($record->dia_insu / $divisor) * $pastday));
                $record->past_m_initial_fee = sprintf('%0.2f', (($record->initial_fee / $divisor) * $pastday));

                // Differ Days Pro-Rata Allocation
                $record->differ_m_rent = sprintf('%0.2f', (($record->rent / $divisor) * $differday));
                $record->differ_m_lateness_fee = sprintf('%0.2f', (($record->lateness_fee / $divisor) * $differday));
                $record->differ_m_emf = sprintf('%0.2f', (($record->extra_mileage_fee / $divisor) * $differday));
                $record->differ_m_tax = sprintf('%0.2f', (($record->tax / $divisor) * $differday));
                $record->differ_m_dia_fee = sprintf('%0.2f', (($record->dia_fee / $divisor) * $differday));
                $record->differ_m_insurance_amt = sprintf('%0.2f', (($record->insurance_amt / $divisor) * $differday));
                $record->differ_m_dia_insu = sprintf('%0.2f', (($record->dia_insu / $divisor) * $differday));
                $record->differ_m_initial_fee = sprintf('%0.2f', (($record->initial_fee / $divisor) * $differday));

                // Range Days Pro-Rata Allocation
                if (!$processnext) {
                    $record->rent = sprintf('%0.2f', (($record->rent / $divisor) * $remainingDays));
                    $record->lateness_fee = sprintf('%0.2f', (($record->lateness_fee / $divisor) * $remainingDays));
                    $record->extra_mileage_fee = sprintf('%0.2f', (($record->extra_mileage_fee / $divisor) * $remainingDays));
                    $record->tax = sprintf('%0.2f', (($record->tax / $divisor) * $remainingDays));
                    $record->dia_fee = sprintf('%0.2f', (($record->dia_fee / $divisor) * $remainingDays));
                    $record->insurance_amt = sprintf('%0.2f', (($record->insurance_amt / $divisor) * $remainingDays));
                    $record->dia_insu = sprintf('%0.2f', (($record->dia_insu / $divisor) * $remainingDays));
                    $record->initial_fee = sprintf('%0.2f', (($record->initial_fee / $divisor) * $remainingDays));
                } else {
                    $record->past_m_rent = $record->rent;
                    $record->past_m_lateness_fee = $record->lateness_fee;
                    $record->past_m_emf = $record->extra_mileage_fee;
                    $record->past_m_tax = $record->tax;
                    $record->past_m_dia_fee = $record->dia_fee;
                    $record->past_m_insurance_amt = $record->insurance_amt;
                    $record->past_m_dia_insu = $record->dia_insu;
                    $record->past_m_initial_fee = $record->initial_fee;

                    // Roll current values down to zero
                    $record->rent = 0;
                    $record->lateness_fee = 0;
                    $record->extra_mileage_fee = 0;
                    $record->tax = 0;
                    $record->dia_fee = 0;
                    $record->insurance_amt = 0;
                    $record->dia_insu = 0;
                    $record->initial_fee = 0;
                }

                $revPart = $revPart1 = $revPart2 = $revPart3 = $transfered = $differtransfered = $net_paid_payout = 0;
                $revshare = !empty($record->revSetting?->rev) ? $record->revSetting->rev : config('legacy.OWNER_PART', 85);
                $taxIncluded = (isset($record->revSetting?->tax_included) && $record->revSetting->tax_included == 0) ? false : true;

                // Rev Share Structural Calculations
                if ($taxIncluded) {
                    $revPart1 = sprintf('%0.2f', (($record->initial_fee + $record->rent + $record->extra_mileage_fee + $record->tax + $record->lateness_fee - $record->dia_fee) * $revshare / 100));
                    $revPart2 = sprintf('%0.2f', (($record->differ_m_initial_fee + $record->differ_m_rent + $record->differ_m_emf + $record->differ_m_tax + $record->differ_m_lateness_fee - $record->differ_m_dia_fee) * $revshare / 100));
                    $revPart3 = sprintf('%0.2f', (($record->past_m_initial_fee + $record->past_m_rent + $record->past_m_emf + $record->past_m_tax + $record->past_m_lateness_fee - $record->past_m_dia_fee) * $revshare / 100));
                } else {
                    $revPart1 = sprintf('%0.2f', (($record->initial_fee + $record->rent + $record->extra_mileage_fee + $record->lateness_fee - $record->dia_fee) * $revshare / 100)) + $record->tax;
                    $revPart2 = sprintf('%0.2f', (($record->differ_m_initial_fee + $record->differ_m_rent + $record->differ_m_emf + $record->differ_m_lateness_fee - $record->differ_m_dia_fee) * $revshare / 100)) + $record->differ_m_tax;
                    $revPart3 = sprintf('%0.2f', (($record->past_m_initial_fee + $record->past_m_rent + $record->past_m_emf + $record->past_m_lateness_fee - $record->past_m_dia_fee) * $revshare / 100)) + $record->past_m_tax;
                }

                $revPart = sprintf('%0.2f', ($revPart1 + $revPart2 + $revPart3));

                $csrecords = PaymentReport::whereIn('type', [2, 3, 5, 6, 16, 4, 14])
                    ->where('cs_order_id', $record->id)
                    ->get();

                $insurance = $emfinsurance = $past_m_insurance = $past_m_emfinsurance = $differ_m_insurance = $differ_m_emfinsurance = $past_m_total_collected = $differ_m_total_collected = $total_collected = $walletRefund = $stripeRefund = $rentWalletRefund = $insuWalletRefund = $rentStripeRefund = $insuStripeRefund = 0;

                foreach ($csrecords as $csrecord) {
                    $createdDate = Carbon::parse($csrecord->created)->format('Y-m-d');
                    $chargedAtDate = Carbon::parse($csrecord->charged_at)->format('Y-m-d');

                    $insideMonth = ($createdDate >= $record->date_from && $createdDate <= $record->date_to);
                    $walletMonth = ($createdDate <= $record->date_to);

                    // Rental Wallet Refund
                    if ($walletMonth && in_array($csrecord->type, [2, 3, 5, 6, 7, 16]) && $csrecord->txn_type == 2 && $csrecord->source == 'wallet') {
                        $rentWalletRefund += abs($csrecord->amount);
                    }

                    // Insurance Wallet Refund
                    if ($walletMonth && in_array($csrecord->type, [4, 14]) && $csrecord->txn_type == 2 && $csrecord->source == 'wallet') {
                        $insuWalletRefund += abs($csrecord->amount);
                    }

                    // Rental Stripe Refund
                    if ($insideMonth && in_array($csrecord->type, [2, 3, 5, 6, 7, 16]) && $csrecord->txn_type == 2 && $csrecord->source == 'stripe') {
                        $rentStripeRefund += abs($csrecord->amount);
                    }

                    // Insurance Stripe Refund
                    if ($insideMonth && in_array($csrecord->type, [4, 14]) && $csrecord->txn_type == 2 && $csrecord->source == 'stripe') {
                        $insuStripeRefund += abs($csrecord->amount);
                    }

                    // Total Wallet Refund Check
                    if ($walletMonth && $csrecord->txn_type == 2 && $csrecord->source == 'wallet') {
                        $walletRefund += abs($csrecord->amount);
                        continue;
                    }

                    // Stripe Refund Check
                    if ($insideMonth && $csrecord->txn_type == 2 && $csrecord->source == 'stripe') {
                        $stripeRefund += abs($csrecord->amount);
                        continue;
                    }

                    // INSURANCE Calculations
                    if ($csrecord->charged_at < $record->date_from && $csrecord->type == 4) {
                        $past_m_insurance += $csrecord->amount;
                    } elseif ($chargedAtDate > $record->date_to && $csrecord->type == 4) {
                        $differ_m_insurance += $csrecord->amount;
                    } elseif ($csrecord->type == 4) {
                        $insurance += $csrecord->amount;
                    }

                    // EMF INSURANCE Calculations
                    if ($csrecord->charged_at < $record->date_from && $csrecord->type == 14) {
                        $past_m_emfinsurance += $csrecord->amount;
                    } elseif ($chargedAtDate > $record->date_to && $csrecord->type == 14) {
                        $differ_m_emfinsurance += $csrecord->amount;
                    } elseif ($csrecord->type == 14) {
                        $emfinsurance += $csrecord->amount;
                    }

                    // RENTAL Calculations
                    if ($csrecord->type != 4 && $csrecord->type != 14) {
                        if ($csrecord->charged_at < $record->date_from) {
                            $past_m_total_collected += $csrecord->amount;
                        } elseif ($chargedAtDate > $record->date_to) {
                            $differ_m_total_collected += $csrecord->amount;
                        } else {
                            $total_collected += $csrecord->amount;
                        }
                    }
                }

                // Payout Transaction loops
                $txns = CsPayoutTransaction::where('cs_order_id', $record->id)
                    ->select('amount', 'created', 'stripe_amt')
                    ->get();

                foreach ($txns as $txn) {
                    $net_paid_payout += $txn->stripe_amt;
                    $txnCreated = Carbon::parse($txn->created);
                    $dateFromBound = Carbon::parse($record->date_from);
                    $dateToBound = Carbon::parse($record->date_to);

                    if ($txnCreated->between($dateFromBound, $dateToBound)) {
                        $transfered += $txn->amount;
                    } else {
                        $differtransfered += !empty($txn) ? $txn->amount : 0;
                    }
                }

                $record->insurance_collected = $insurance;
                $record->past_m_insurance_collected = $past_m_insurance;
                $record->differ_m_insurance_collected = $differ_m_insurance;
                $record->dia_insu_collected = $emfinsurance;
                $record->past_m_dia_insu_collected = $past_m_emfinsurance;
                $record->differ_m_dia_insu_collected = $differ_m_emfinsurance;
                $record->past_m_total_collected = $past_m_total_collected;
                $record->differ_m_total_collected = $differ_m_total_collected;
                $record->total_collected = $total_collected;
                $record->rev_share = $revshare;
                $record->dealer_payout = $revPart1;
                $record->differ_m_dealer_payout = $revPart2;
                $record->past_m_payout = $revPart3;
                $record->total_payout = $revPart;
                $record->paid_payout = $transfered;
                $record->net_paid_payout = ($net_paid_payout > 0 ? $net_paid_payout : $transfered);
                $record->differ_paid_payout = $differtransfered;
                $record->wallet_refund = $walletRefund;
                $record->stripe_refund = $stripeRefund;
                $record->rent_wallet_refund = $rentWalletRefund;
                $record->insu_wallet_refund = $insuWalletRefund;
                $record->rent_stripe_refund = $rentStripeRefund;
                $record->insu_stripe_refund = $insuStripeRefund;
                $record->save();
            }
        }

        // abort(200, 'Processing Complete');
        return response('OK', 200);
    }
    public function view(Request $request, int|string $id)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Summary Report';

        $data = DB::table('report_customers as rc')
            ->leftJoin('users as u', 'u.id', '=', 'rc.user_id')
            ->where('rc.user_id', $id)
            ->selectRaw('rc.user_id, u.first_name, u.last_name, (select count(DISTINCT vehicle_id) from report_customers rc2 where rc2.user_id = rc.user_id) as activevehicles')
            ->selectRaw('SUM(rc.days) as days, SUM(rc.miles) as miles, SUM(rc.total_rent+rc.fixed_amt) as total_rent, SUM(rc.uncollected) as uncollected, SUM(rc.total_collected) as total_collected, SUM(rc.revpart) as revpart, SUM(rc.insurance) as insurance')
            ->selectRaw('SUM(rc.total_net_pay) as total_net_pay, SUM(rc.transferred-rc.insurance) as transferred, SUM(rc.pending) as pending')
            ->orderByDesc('rc.id')
            ->get();

        return view('admin.report.summary.view', compact('data', 'id', 'title'));
    }
}
