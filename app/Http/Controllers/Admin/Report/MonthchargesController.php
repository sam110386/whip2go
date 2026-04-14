<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Admin\Report\Concerns\UsesReportPageLimit;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\Common;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthchargesController extends LegacyAppController
{
    use UsesReportPageLimit;

    protected Common $common;

    public function __construct(Common $common)
    {
        parent::__construct();
        $this->common = $common;
    }

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if ($request->filled('export')) {
            return $this->exportReport($request);
        }

        $title = 'Summary Report';
        $datefrom = '';
        $dateto = '';

        if ($request->filled('Search') || $request->query->has('datefrom') || $request->query->has('dateto')) {
            if ($request->query->has('datefrom')) {
                $datefrom = base64_decode((string) $request->query('datefrom'), true) ?: '';
            } elseif ($request->filled('Search.datefrom')) {
                $datefrom = (string) $request->input('Search.datefrom');
            }
            if ($request->query->has('dateto')) {
                $dateto = base64_decode((string) $request->query('dateto'), true) ?: '';
            } elseif ($request->filled('Search.dateto')) {
                $dateto = (string) $request->input('Search.dateto');
            }
        } else {
            $datefrom = $dateto = date('m/Y');
        }

        $dtFrom = \DateTime::createFromFormat('m/Y', $datefrom);
        $dtTo = \DateTime::createFromFormat('m/Y', $dateto);
        $firstDay = $dtFrom ? $dtFrom->format('Y-m-01') : null;
        $lastDay = $dtTo ? $dtTo->format('Y-m-t') : null;
        if (empty($firstDay) || empty($lastDay)) {
            return redirect()->back()->with('error', 'Sorry, something went wrong. Please try again');
        }

        $refundTypes = $this->common->getRefundType();
        $limit = $this->getPageLimit($request, 'monthcharges_limit', 50);

        $query = DB::table('cs_payment_logs as cpl')
            ->leftJoin('cs_orders as co', 'co.id', '=', 'cpl.cs_order_id')
            ->whereNotIn('cpl.type', $refundTypes)
            ->where('cpl.status', 1)
            ->where(function ($q) {
                $q->whereRaw("cpl.refund_transaction_id = ''")
                    ->orWhereNull('cpl.refund_transaction_id');
            })
            ->whereRaw('cpl.created > ?', [$firstDay])
            ->whereRaw("DATE_FORMAT(cpl.created,'%Y-%m-%d') < ?", [$lastDay])
            ->select('cpl.*', 'co.increment_id', 'co.start_datetime', 'co.end_datetime', 'co.timezone')
            ->orderBy('cpl.id', 'asc');

        $lists = $query->paginate($limit)->withQueryString();
        $paymentTypeValue = $this->common->getpaymentTypeValue(true);

        if ($request->ajax()) {
            return view('admin.report.elements.admin_monthcharge', compact('lists', 'datefrom', 'dateto', 'paymentTypeValue', 'title'));
        }

        return view('admin.report.monthcharges.index', compact('title', 'lists', 'datefrom', 'dateto', 'paymentTypeValue'));
    }

    private function exportReport(Request $request): StreamedResponse|RedirectResponse
    {
        $datefrom = (string) $request->input('Search.datefrom', '');
        $dateto = (string) $request->input('Search.dateto', '');
        if ($datefrom === '' || $dateto === '') {
            return redirect()->back()->with('error', 'Please choose date first');
        }

        $dtFrom = \DateTime::createFromFormat('m/Y', $datefrom);
        $dtTo = \DateTime::createFromFormat('m/Y', $dateto);
        $firstDay = $dtFrom ? $dtFrom->format('Y-m-01') : null;
        $lastDay = $dtTo ? $dtTo->format('Y-m-t') : null;
        if (empty($firstDay) || empty($lastDay)) {
            return redirect()->back()->with('error', 'Sorry, something went wrong. Please try again');
        }

        $refundTypes = $this->common->getRefundType();
        $records = DB::table('cs_payment_logs as cpl')
            ->leftJoin('cs_orders as co', 'co.id', '=', 'cpl.cs_order_id')
            ->whereNotIn('cpl.type', $refundTypes)
            ->where('cpl.status', 1)
            ->where(function ($q) {
                $q->whereRaw("cpl.refund_transaction_id = ''")
                    ->orWhereNull('cpl.refund_transaction_id');
            })
            ->whereRaw('cpl.created > ?', [$firstDay])
            ->whereRaw("DATE_FORMAT(cpl.created,'%Y-%m-%d') < ?", [$lastDay])
            ->select('cpl.*', 'co.increment_id', 'co.start_datetime', 'co.end_datetime')
            ->orderBy('cpl.id')
            ->get();

        $paymentTypeValue = $this->common->getpaymentTypeValue(true);

        return response()->streamDownload(function () use ($records, $paymentTypeValue) {
            $fp = fopen('php://output', 'w');
            $header = ['#', 'Start', 'End', 'Amount', 'Type', 'Transaction #', 'Created(UTC)'];
            fputcsv($fp, $header);
            foreach ($records as $list) {
                $row = [
                    $list->increment_id,
                    $list->start_datetime ? Carbon::parse($list->start_datetime)->format('Y-m-d h:i A') : '',
                    $list->end_datetime ? Carbon::parse($list->end_datetime)->format('Y-m-d h:i A') : '',
                    $list->amount,
                    $paymentTypeValue[$list->type] ?? '',
                    $list->transaction_id,
                    $list->created,
                ];
                fputcsv($fp, $row);
            }
            fclose($fp);
        }, 'Monthly_Charge_Report.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
