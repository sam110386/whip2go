<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Admin\Report\Concerns\ProcessesSummaryReports;
use App\Http\Controllers\Admin\Report\Concerns\UsesReportPageLimit;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\Common;
use App\Services\Legacy\Report\SummaryReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SummaryController extends LegacyAppController
{
    use ProcessesSummaryReports;
    use UsesReportPageLimit;

    protected Common $common;

    protected SummaryReportService $summaryReportService;

    public function __construct(Common $common, SummaryReportService $summaryReportService)
    {
        parent::__construct();
        $this->common = $common;
        $this->summaryReportService = $summaryReportService;
    }

    public function index(Request $request, int|string $process = 0)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Summary Report';
        $datefrom = '';
        $dateto = '';

        $limit = $this->getPageLimit($request, 'summary_limit', 50);
        $lists = DB::table('summary_reports')->orderBy('id')->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return view('admin.report.elements.admin_summary', compact('lists', 'datefrom', 'dateto', 'process', 'title'));
        }

        return view('admin.report.summary.index', compact('title', 'lists', 'datefrom', 'dateto', 'process'));
    }

    public function generatereport(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if ($request->filled('export')) {
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

        $csOrdersTable = 'cs_orders';
        $paymentReportsTable = 'payment_reports';
        $sql = "Select id from {$csOrdersTable} where  ((`start_datetime`>='{$firstDay}' AND DATE_FORMAT(`start_datetime`,'%Y-%m-%d')<='{$lastDay}') OR (`end_datetime`>='{$firstDay}' AND DATE_FORMAT(`end_datetime`,'%Y-%m-%d')<='{$lastDay}')) UNION SELECT cs_order_id FROM {$paymentReportsTable} where charged_at>'{$firstDay}' and DATE_FORMAT(charged_at,'%Y-%m-%d')<='{$lastDay}'";
        $rows = DB::select($sql);

        foreach ($rows as $rowObj) {
            $row = (array) $rowObj;
            $orderId = (int) ($row['id'] ?? 0);
            if ($orderId === 0) {
                continue;
            }
            $query = "insert into summary_reports (id,user_id,increment_id,start_datetime,end_datetime,rent,lateness_fee,extra_mileage_fee,tax,dia_fee,insurance_amt,dia_insu,initial_fee,booking_status,date_from,date_to) 
            SELECT CsOrder.id,CsOrder.user_id,CsOrder.increment_id,CsOrder.start_datetime,CsOrder.end_datetime, `CsOrder`.`rent`, (CsOrder.damage_fee+CsOrder.lateness_fee+CsOrder.uncleanness_fee+CsOrder.cancellation_fee) as lateness_fee, `CsOrder`.`extra_mileage_fee`, (CsOrder.tax+CsOrder.emf_tax) as tax, `CsOrder`.`dia_fee`, `CsOrder`.`insurance_amt`, `CsOrder`.`dia_insu`, `CsOrder`.`initial_fee`, CsOrder.status,'{$firstDay}','{$lastDay}' FROM `cs_orders` AS `CsOrder`  WHERE CsOrder.id=".$orderId;
            DB::unprepared($query);
        }

        return redirect(rtrim((string) config('app.url'), '/').'/admin/report/summary/index/1');
    }

    public function processReport(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $this->runSummaryProcessReportLoop($this->common, $this->summaryReportService);

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

    private function exportReport(): StreamedResponse
    {
        $records = DB::table('summary_reports')->orderBy('id')->get();

        return response()->stream(function () use ($records) {
            $fp = fopen('php://output', 'w');
            $header = [
                '#',
                'Start',
                'End',
                'Rent',
                'EMF',
                'DIA FEE',
                'Tax',
                'Lateness',
                'Total Rent',
                'Rental Revenue This Month',
                'Past Revenue',
                'Deferred Revenue',
                'Total Revenue',
                'Total Collected This Month',
                'Rental Wallet Refund',
                'Rental Stripe Refund',
                'Net Collected This Month',
                'Already Collected',
                'Differ Collected',
                'Total Collected',
                'Uncollected',
                'Total Insurance',
                'Insu. This Month',
                'Past Insu.',
                'Deferred Insu.',
                'Insu. Wallet Refund',
                'Insu. Stripe Refund',
                'Insu. Collected This Month',
                'Collected Past Insu.',
                'Collected Deferred Insu.',
                'Total Insu. Collected',
                'Total Insu. Calculated',
                'Insu. Uncollected',
                'Current Payout Owed',
                'Past Payout Owed',
                'Differ Payout Owed',
                'Total Payout Owed',
                'Paid out in Month',
                'Stripe Fee',
                'Net Paid out in Month',
                'Paid out in Differ Month',
                'Total Paid out',
                'Dealer Owed',
                'Wallet Refund',
                'Stripe Refund',
            ];
            fputcsv($fp, $header);

            foreach ($records as $list) {
                $l = (array) $list;
                foreach ([
                    'initial_fee', 'rent', 'extra_mileage_fee', 'dia_fee', 'tax', 'lateness_fee',
                    'past_m_initial_fee', 'past_m_rent', 'past_m_emf', 'past_m_dia_fee', 'past_m_tax', 'past_m_lateness_fee',
                    'differ_m_initial_fee', 'differ_m_rent', 'differ_m_emf', 'differ_m_dia_fee', 'differ_m_tax', 'differ_m_lateness_fee',
                    'total_collected', 'past_m_total_collected', 'differ_m_total_collected',
                    'past_m_dia_insu', 'past_m_insurance_amt', 'differ_m_dia_insu', 'differ_m_insurance_amt',
                    'insurance_collected', 'past_m_insurance_collected', 'dia_insu_collected', 'past_m_dia_insu_collected',
                    'insurance_amt', 'dia_insu', 'dealer_payout', 'past_m_payout', 'differ_m_dealer_payout', 'total_payout',
                    'paid_payout', 'net_paid_payout', 'differ_paid_payout', 'rent_wallet_refund', 'rent_stripe_refund',
                    'insu_wallet_refund', 'insu_stripe_refund', 'wallet_refund', 'stripe_refund',
                ] as $_nk) {
                    if (! array_key_exists($_nk, $l) || $l[$_nk] === null) {
                        $l[$_nk] = 0;
                    }
                }
                $total = sprintf('%0.2f', ($l['initial_fee'] + $l['rent'] + $l['extra_mileage_fee'] + $l['dia_fee'] + $l['tax'] + $l['lateness_fee'] +
                            $l['past_m_initial_fee'] + $l['past_m_rent'] + $l['past_m_emf'] + $l['past_m_dia_fee'] + $l['past_m_tax'] + $l['past_m_lateness_fee'] +
                            $l['differ_m_initial_fee'] + $l['differ_m_rent'] + $l['differ_m_emf'] + $l['differ_m_dia_fee'] + $l['differ_m_tax'] + $l['differ_m_lateness_fee']));

                $Revtotal = sprintf('%0.2f', ($l['initial_fee'] + $l['rent'] + $l['extra_mileage_fee'] + $l['dia_fee'] + $l['tax'] + $l['lateness_fee']));
                $pasttotal = sprintf('%0.2f', ($l['past_m_initial_fee'] + $l['past_m_rent'] + $l['past_m_emf'] + $l['past_m_dia_fee'] + $l['past_m_tax'] + $l['past_m_lateness_fee']));
                $Diffetotal = sprintf('%0.2f', ($l['differ_m_initial_fee'] + $l['differ_m_rent'] + $l['differ_m_emf'] + $l['differ_m_dia_fee'] + $l['differ_m_tax'] + $l['differ_m_lateness_fee']));
                $total_collected = $l['total_collected'];
                $past_m_total_collected = $l['past_m_total_collected'];
                $differ_m_total_collected = $l['differ_m_total_collected'];

                $pastinsu_calculated = ($l['past_m_dia_insu'] + $l['past_m_insurance_amt']);

                $differinsu_calculated = ($l['differ_m_dia_insu'] + $l['differ_m_insurance_amt']);

                $collectedinsu = sprintf('%0.2f', (($l['insurance_collected'] + $l['past_m_insurance_collected']) + ($l['dia_insu_collected']) + ($l['past_m_dia_insu_collected'])));

                $currentinsu_calculated = sprintf('%0.2f', ($l['insurance_amt'] + $l['dia_insu']));
                $row = [
                    '#'=>$l['increment_id'],
                    'Start'=>$l['start_datetime'] ? Carbon::parse($l['start_datetime'])->format('Y-m-d h:i A') : '',
                    'End'=>$l['end_datetime'] ? Carbon::parse($l['end_datetime'])->format('Y-m-d h:i A') : '',
                    'Rent'=>($l['rent'] + $l['initial_fee'] + $l['past_m_rent'] + $l['past_m_initial_fee'] + $l['differ_m_rent'] + $l['differ_m_initial_fee']),
                    'EMF'=>($l['extra_mileage_fee'] + $l['past_m_emf'] + $l['differ_m_emf']),
                    'DIA FEE'=>($l['dia_fee'] + $l['past_m_dia_fee'] + $l['differ_m_dia_fee']),
                    'Tax'=>($l['tax'] + $l['past_m_tax'] + $l['differ_m_tax']),
                    'Lateness'=>($l['lateness_fee'] + $l['past_m_lateness_fee'] + $l['differ_m_lateness_fee']),
                    'Total Rent'=>$total,
                    'Rental Revenue This Month'=>$Revtotal,
                    'Past Revenue'=>$pasttotal,
                    'Deferred Revenue'=>$Diffetotal,
                    'Total Revenue'=>sprintf('%0.2f', ($Revtotal + $Diffetotal + $pasttotal)),
                    'Total Collected This Month'=>$total_collected,
                    'Rental Wallet Refund'=>$l['rent_wallet_refund'],
                    'Rental Stripe Refund'=>$l['rent_stripe_refund'],
                    'Net Collected This Month'=>sprintf('%0.2f', ($total_collected - ($l['rent_wallet_refund'] + $l['rent_stripe_refund']))),
                    'Already Collected'=>$past_m_total_collected,
                    'Differ Collected'=>$differ_m_total_collected,
                    'Total Collected'=>sprintf('%0.2f', (($total_collected + $past_m_total_collected) - $l['rent_stripe_refund'])),
                    'Uncollected'=>sprintf('%0.2f', (($Revtotal + $pasttotal) - ($total_collected + $past_m_total_collected))),
                    'Total Insurance'=>($currentinsu_calculated + $pastinsu_calculated + $differinsu_calculated),
                    'Insu. This Month'=>$currentinsu_calculated,
                    'Past Insu.'=>$pastinsu_calculated,
                    'Deferred Insu.'=>$differinsu_calculated,
                    'Insu. Wallet Refund'=>$l['insu_wallet_refund'],
                    'Insu. Stripe Refund'=>$l['insu_stripe_refund'],
                    'Insu. Collected This Month'=>($l['insurance_collected'] + $l['dia_insu_collected']),
                    'Collected Past Insu.'=>($l['past_m_insurance_collected'] + $l['past_m_dia_insu_collected']),
                    'Collected Deferred Insu.'=>($l['differ_m_insurance_amt'] + $l['differ_m_dia_insu_collected']),
                    'Total Insu. Collected'=>($collectedinsu - $l['insu_stripe_refund'] - $l['insu_wallet_refund']),
                    'Total Insu. Calculated'=>($currentinsu_calculated + $pastinsu_calculated + $differinsu_calculated),
                    'Insu. Uncollected'=>sprintf('%0.2f', (($currentinsu_calculated + $pastinsu_calculated) - ($collectedinsu - $l['insu_stripe_refund'] - $l['insu_wallet_refund']))),
                    'Current Payout Owed'=>$l['dealer_payout'],
                    'Past Payout Owed'=>$l['past_m_payout'],
                    'Differ Payout Owed'=>$l['differ_m_dealer_payout'],
                    'Total Payout Owed'=>$l['total_payout'],
                    'Paid out in Month'=>$l['paid_payout'],
                    'Stripe Fee'=>($l['net_paid_payout'] > 0 ? sprintf('%0.2f', ($l['paid_payout'] - $l['net_paid_payout'])) : 0),
                    'Net Paid out in Month'=>$l['net_paid_payout'],
                    'Paid out in Differ Month'=>$l['differ_paid_payout'],
                    'Total Paid out'=>sprintf('%0.2f', ($l['differ_paid_payout'] + $l['paid_payout'])),
                    'Dealer Owed'=>sprintf('%0.2f', ($l['differ_paid_payout'] + $l['paid_payout'] - $l['total_payout'])),
                    'Wallet Refund'=>sprintf('%0.2f', $l['wallet_refund']),
                    'Stripe Refund'=>sprintf('%0.2f', $l['stripe_refund']),
                ];
                fputcsv($fp, array_values($row));
            }
            fclose($fp);
        }, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename=Revenue_Report.csv',
        ]);
    }
}
