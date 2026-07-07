<?php
namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Legacy\AuditReport;
use App\Models\Legacy\AuditReportLog;
use App\Models\Legacy\PaymentReport;
use App\Models\Legacy\CsPayoutTransaction;
use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;

class AuditReportsController extends LegacyAppController
{
    public function index(Request $request)
    {
        $title = 'Audit Reports';
        $keyword = '';
        $sessionLimitKey = 'audit_report_limit';
        $query = AuditReport::query()->where('type', 0);

        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            $request->session()->put($sessionLimitKey, $limit);
        } else {
            $limit = $request->session()->get($sessionLimitKey, $this->recordsPerPage ?? 10);
        }

        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        $allowedSort = ['id'];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'id';
            $direction = 'desc';
        }

        $request->merge(['Record' => ['limit' => $limit]]);
        $records = $query->orderBy($sort, $direction)->paginate($limit);

        if ($request->ajax()) {
            return view('admin.audit_report.elements.index', compact('title', 'keyword', 'records', 'limit'));
        }

        return view('admin.audit_report.index', compact('title', 'keyword', 'records', 'limit'));

    }
    public function add(Request $request)
    {
        $title = 'Create Audit Report';

        if ($request->isMethod('post')) {
            $validatedData = $request->validate([
                'AuditReport.start_date' => 'required|date',
                'AuditReport.end_date' => 'required|date|after_or_equal:AuditReport.start_date',
            ]);
            try {
                $startDate = Carbon::parse($request->input('AuditReport.start_date'))->format('Y-m-d');
                $endDate = Carbon::parse($request->input('AuditReport.end_date'))->format('Y-m-d');

                $auditReport = AuditReport::create([
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);

                $this->initiateReport($auditReport->id, $startDate, $endDate);

                return redirect('admin/audit_reports/index')->with('success', 'Audit Report is initialized successfully.');

            } catch (\Exception $e) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Something Went Wrong!');
            }
        }

        return view('admin.audit_report.add', compact('title'));
    }
    private function initiateReport($reportid, $start, $end): void
    {
        DB::table('audit_report_logs')->truncate();

        $bindings = [
            'report_id' => $reportid,
            'start_time' => "{$start} 00:01:00",
            'end_time' => "{$end} 23:59:00",
            'start_time_alt' => "{$start} 00:00:00",
            'end_time_alt' => "{$end} 23:59:59"
        ];

        $sql1 = "INSERT INTO audit_report_logs (
                    cs_order_id, increment_id, user_id, start_datetime, 
                    end_datetime, first_name, last_name, report_id, status
                )
                SELECT 
                    cd.id, cd.increment_id, cd.user_id, cd.start_datetime, 
                    cd.end_datetime, rnt.first_name, rnt.last_name, :report_id, '0' 
                FROM `cs_orders` AS cd 
                LEFT JOIN users AS rnt ON rnt.id = cd.renter_id  
                WHERE cd.start_datetime > :start_time AND cd.start_datetime < :end_time";

        DB::statement($sql1, [
            'report_id' => $bindings['report_id'],
            'start_time' => $bindings['start_time'],
            'end_time' => $bindings['end_time']
        ]);

        $sql2 = "INSERT IGNORE INTO audit_report_logs (
                    cs_order_id, increment_id, user_id, start_datetime, 
                    end_datetime, first_name, last_name, report_id, status
                )
                SELECT 
                    cd.id, cd.increment_id, cd.user_id, cd.start_datetime, 
                    cd.end_datetime, rnt.first_name, rnt.last_name, :report_id, '0' 
                FROM payment_reports AS pr 
                LEFT JOIN `cs_orders` AS cd ON cd.id = pr.cs_order_id 
                LEFT JOIN users AS rnt ON rnt.id = cd.renter_id 
                WHERE pr.charged_at > :start_time_alt AND pr.charged_at < :end_time_alt 
                GROUP BY 
                    pr.cs_order_id, 
                    cd.id, 
                    cd.increment_id, 
                    cd.user_id, 
                    cd.start_datetime, 
                    cd.end_datetime, 
                    rnt.first_name, 
                    rnt.last_name";

        DB::statement($sql2, [
            'report_id' => $bindings['report_id'],
            'start_time_alt' => $bindings['start_time_alt'],
            'end_time_alt' => $bindings['end_time_alt']
        ]);

    }
    public function process($id)
    {
        try {
            $auditReport = AuditReport::findOrFail($id);
            $this->_process($auditReport);
            return redirect('admin/audit_reports/index')->with('success', 'Audit Report is completed successfully.');
        } catch (\Exception $e) {
            return redirect('admin/audit_reports/index')
                ->with('error', 'Something Went Wrong!');
        }
    }
    private function _process($auditReportObj): void
    {
        $filename = time() . '_' . $auditReportObj->start_date . '_' . $auditReportObj->end_date . '.csv';

        $directory = 'files/auditreport';

        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        $filePath = Storage::path($directory . '/' . $filename);
        $fp = fopen($filePath, 'w+');

        if (!$fp) {
            return;
        }

        $paymentTypes = $this->commonService->getPayoutTypeValue(true);

        $header = [
            1 => "increment_id",
            2 => "user_id",
            3 => "start_datetime",
            4 => "end_datetime",
            5 => "Driver first name",
            6 => "Driver last name",
            7 => "Payment Type",
            8 => "amount",
            9 => "transaction_id",
            10 => "payer_id",
            11 => "txn_type",
            12 => "source",
            13 => "description",
            14 => "currency",
            15 => "charged_at",
            16 => "created",
            17 => "Transferred Amount",
            18 => "Pulled Amount",
            19 => "Amount Type",
            20 => "Transferred Source transaction_id",
            21 => "Transfer_id",
            22 => "Transfer created At"
        ];

        fputcsv($fp, $header);

        while (true) {
            $auditReportLogObj = AuditReportLog::where('cs_order_id', '!=', 0)
                ->whereNotNull('cs_order_id')
                ->where('report_id', $auditReportObj->id)
                ->where('status', 0)
                ->first();

            if (!$auditReportLogObj) {
                fclose($fp);
                $auditReportObj->file_name = $filename;
                $auditReportObj->status = 1;
                $auditReportObj->save();
                break;
            }

            $auditReportLogObj->status = 1;
            $auditReportLogObj->save();

            $writeToCsv = array_fill(1, 22, "");
            $writeToCsv[1] = $auditReportLogObj->increment_id;
            $writeToCsv[2] = $auditReportLogObj->user_id;
            $writeToCsv[3] = $auditReportLogObj->start_datetime;
            $writeToCsv[4] = $auditReportLogObj->end_datetime;
            $writeToCsv[5] = $auditReportLogObj->first_name;
            $writeToCsv[6] = $auditReportLogObj->last_name;

            $orderCsv = $writeToCsv;
            fputcsv($fp, $writeToCsv);

            $paymentReports = PaymentReport::where('cs_order_id', $auditReportLogObj->cs_order_id)->get();

            foreach ($paymentReports as $paymentReport) {
                $writeToCsv[7] = $paymentTypes[$paymentReport->type] ?? $paymentReport->type;
                $writeToCsv[8] = $paymentReport->amount;
                $writeToCsv[9] = $paymentReport->transaction_id;
                $writeToCsv[10] = $paymentReport->payer_id;
                $writeToCsv[11] = $paymentReport->txn_type == 1 ? 'Charge' : 'Refund';
                $writeToCsv[12] = $paymentReport->source;
                $writeToCsv[13] = $paymentReport->description;
                $writeToCsv[14] = $paymentReport->currency;
                $writeToCsv[15] = $paymentReport->txn_type == 1 ? $paymentReport->charged_at : $paymentReport->created;
                $writeToCsv[16] = $paymentReport->created;
                fputcsv($fp, $writeToCsv);
            }

            $writeToCsv = $orderCsv;
            $csPayoutTransactions = CsPayoutTransaction::where('cs_order_id', $auditReportLogObj->cs_order_id)->get();

            foreach ($csPayoutTransactions as $csPayoutTransaction) {
                $writeToCsv[17] = $csPayoutTransaction->amount;
                $writeToCsv[18] = $csPayoutTransaction->refund;
                $writeToCsv[19] = $paymentTypes[$csPayoutTransaction->type] ?? $csPayoutTransaction->type;
                $writeToCsv[20] = $csPayoutTransaction->transaction_id;
                $writeToCsv[21] = $csPayoutTransaction->transfer_id;
                $writeToCsv[22] = $csPayoutTransaction->created;
                fputcsv($fp, $writeToCsv);
            }
        }
    }
    public function download($id)
    {
        $decodedId = $this->decodeId($id);
        $auditReportObj = AuditReport::find($decodedId);

        if (!$auditReportObj || empty($auditReportObj->file_name)) {
            return redirect()->back();
        }

        $directory = 'files/auditreport/';
        $filePath = $directory . $auditReportObj->file_name;

        if (Storage::exists($filePath)) {
            return Storage::download($filePath);
        }

        return redirect()->back()->with('error', 'File not found.');
    }
    public function delete($id)
    {
        $decodedId = $this->decodeId($id);
        $auditReportObj = AuditReport::find($decodedId);

        if ($auditReportObj) {
            $directory = 'files/auditreport/';
            $filePath = $directory . $auditReportObj->file_name;

            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            $auditReportObj->delete();
            return redirect()->back()->with('success', 'Audit Report is deleted successfully.');
        }

        return redirect()->back();
    }
}
