<?php
namespace App\Http\Controllers\Admin\Report;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Legacy\CsPaymentLog;
use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;

class MonthchargesController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if ($request->has('export')) {
            return $this->exportReport($request);
        }

        $title = 'Summary Report';
        $sessionLimitKey = "monthly_report_limit";
        $datefrom = $request->input('Search.datefrom', $request->input('datefrom', ''));
        $dateto = $request->input('Search.dateto', $request->input('dateto', ''));

        if (empty($datefrom) || empty($dateto)) {
            $datefrom = $dateto = now()->format('m/Y');
        }

        try {
            $firstDay = Carbon::createFromFormat('m/Y', $datefrom)->startOfMonth()->format('Y-m-d H:i:s');
            $lastDay = Carbon::createFromFormat('m/Y', $dateto)->endOfMonth()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->with('error', 'Sorry, something went wrong. Please try again');
        }

        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionLimitKey => $limit]);
        } else {
            $limit = session($sessionLimitKey, $this->recordsPerPage);
        }

        $query = CsPaymentLog::with('csOrder:id,increment_id,start_datetime,end_datetime,timezone')
            ->whereNotIn('type', $this->commonService->getRefundType())
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('refund_transaction_id', '')
                    ->orWhereNull('refund_transaction_id');
            })
            ->where('created', '>', $firstDay)
            ->whereRaw("DATE_FORMAT(created, '%Y-%m-%d') < ?", [$lastDay])
            ->orderBy('id', 'asc');

        $lists = $query->paginate($limit);
        $paymentTypeValue = $this->commonService->getpaymentTypeValue(true);

        if ($request->ajax()) {
            return view('admin.report.monthcharges.elements._monthcharge', compact('lists', 'paymentTypeValue', 'datefrom', 'dateto', 'limit'));
        }

        return view('admin.report.monthcharges.index', compact('title', 'lists', 'paymentTypeValue', 'datefrom', 'dateto', 'limit'));
    }
    private function exportReport(Request $request)
    {
        $dateFrom = $request->input('Search.datefrom', $request->input('datefrom', ''));
        $dateTo = $request->input('Search.dateto', $request->input('dateto', ''));

        if (empty($dateFrom) || empty($dateTo)) {
            return back()->with('error', 'Please choose date first');
        }

        try {
            $firstDay = Carbon::createFromFormat('m/Y', $dateFrom)->startOfMonth()->format('Y-m-d H:i:s');
            $lastDay = Carbon::createFromFormat('m/Y', $dateTo)->endOfMonth()->format('Y-m-d');
        } catch (\Exception $e) {
            return back()->with('error', 'Sorry, something went wrong. Please try again');
        }

        $paymentTypeValue = $this->commonService->getpaymentTypeValue(true);

        $records = CsPaymentLog::with('csOrder')
            ->whereNotIn('type', $this->commonService->getRefundType())
            ->where('status', 1)
            ->where(function ($q) {
                $q->where('refund_transaction_id', '')
                    ->orWhereNull('refund_transaction_id');
            })
            ->where('created', '>', $firstDay)
            ->whereRaw("DATE_FORMAT(created, '%Y-%m-%d') < ?", [$lastDay])
            ->orderBy('id', 'asc')
            ->get();

        $response = new StreamedResponse(function () use ($records, $paymentTypeValue) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['#', 'Start', 'End', 'Amount', 'Type', 'Transaction #', 'Created(UTC)']);

            foreach ($records as $log) {
                $start = $log->csOrder?->start_datetime ? Carbon::parse($log->csOrder->start_datetime)->format('Y-m-d h:i A') : '';
                $end = $log->csOrder?->end_datetime ? Carbon::parse($log->csOrder->end_datetime)->format('Y-m-d h:i A') : '';

                fputcsv($handle, [
                    $log->csOrder?->increment_id ?? '',
                    $start,
                    $end,
                    $log->amount,
                    $paymentTypeValue[$log->type] ?? '',
                    $log->transaction_id,
                    $log->created?->toDateTimeString() ?? '',
                ]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="Monthly_Charge_Report.csv"');

        return $response;
    }
}
