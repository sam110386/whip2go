<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditReportsController extends LegacyAppController
{
    protected int $recordsPerPage = 25;

    public function index(Request $request)
    {
        $keyword = '';
        $query = DB::table('audit_reports as AuditReport')
            ->where('type', 0)
            ->select('AuditReport.*');

        $sessLimitName = 'audit_reports_limit';
        $limit = $request->input('Record.limit',
            session($sessLimitName, $this->recordsPerPage));
        if ($request->filled('Record.limit')) {
            session([$sessLimitName => $limit]);
        }

        $records = $query->orderByDesc('AuditReport.id')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.audit_report.audit_reports._admin_index', compact('records'));
        }

        return view('admin.audit_report.audit_reports.index', compact('records', 'keyword'));
    }

    public function add(Request $request)
    {
        $listTitle = 'Create Audit Report';

        if ($request->isMethod('post')) {
            $startDate = date('Y-m-d', strtotime($request->input('AuditReport.start_date')));
            $endDate = date('Y-m-d', strtotime($request->input('AuditReport.end_date')));

            try {
                $reportId = DB::table('audit_reports')->insertGetId([
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                    'type'       => 0,
                    'status'     => 0,
                    'created'    => now(),
                    'modified'   => now(),
                ]);

                $this->initiateReport($reportId, $startDate, $endDate);

                return redirect('admin/audit_report/audit_reports/index')
                    ->with('success', 'Audit Report is initialed successfully.');
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        return view('admin.audit_report.audit_reports.add', compact('listTitle'));
    }

    public function process($id)
    {
        try {
            $auditReport = DB::table('audit_reports')->where('id', $id)->first();
            $this->processReport($auditReport);

            return redirect('admin/audit_report/audit_reports/index')
                ->with('success', 'Audit Report is completed successfully.');
        } catch (\Exception $e) {
            return redirect('admin/audit_report/audit_reports/index')
                ->with('error', $e->getMessage());
        }
    }

    public function download($id)
    {
        $id = base64_decode($id);
        $auditReport = DB::table('audit_reports')->where('id', $id)->first();
        if (empty($auditReport)) {
            return redirect()->back();
        }

        $filePath = public_path('files/auditreport/' . $auditReport->file_name);
        return response()->download($filePath);
    }

    public function delete($id)
    {
        $id = base64_decode($id);
        $auditReport = DB::table('audit_reports')->where('id', $id)->first();
        if (!empty($auditReport)) {
            DB::table('audit_reports')->where('id', $id)->delete();
            $filePath = public_path('files/auditreport/' . $auditReport->file_name);
            if (is_file($filePath)) {
                unlink($filePath);
            }
            return redirect()->back()->with('success', 'Audit Report is deleted successfully.');
        }
        return redirect()->back();
    }

    private function initiateReport(int $reportid, string $start, string $end): void
    {
        DB::statement('TRUNCATE audit_report_logs');

        DB::statement("INSERT INTO audit_report_logs (cs_order_id,increment_id,user_id,start_datetime,end_datetime,first_name,last_name,report_id,status)
            SELECT cd.id,cd.increment_id,cd.user_id,cd.start_datetime,cd.end_datetime,rnt.first_name,rnt.last_name,?,0
            FROM cs_orders AS cd LEFT JOIN users AS rnt ON rnt.id=cd.renter_id
            WHERE (cd.start_datetime > ? AND cd.start_datetime < ?)", [
            $reportid,
            $start . ' 00:01:00',
            $end . ' 23:59:00',
        ]);

        DB::statement("INSERT IGNORE INTO audit_report_logs (cs_order_id,increment_id,user_id,start_datetime,end_datetime,first_name,last_name,report_id,status)
            SELECT cd.id,cd.increment_id,cd.user_id,cd.start_datetime,cd.end_datetime,rnt.first_name,rnt.last_name,?,0
            FROM payment_reports AS pr LEFT JOIN cs_orders AS cd ON cd.id=pr.cs_order_id LEFT JOIN users AS rnt ON rnt.id=cd.renter_id
            WHERE (pr.charged_at > ? AND pr.charged_at < ?) GROUP BY pr.cs_order_id", [
            $reportid,
            $start . ' 00:00:00',
            $end . ' 23:59:59',
        ]);
    }

    private function processReport(object $auditReport): void
    {
        $filename = time() . '_' . $auditReport->start_date . '_' . $auditReport->end_date . '.csv';
        $dir = public_path('files/auditreport');
        if (!file_exists($dir)) {
            @mkdir($dir, 0755, true);
        }
        $fileObj = $dir . '/' . $filename;
        $fp = fopen($fileObj, 'w+');
        if (!$fp) {
            return;
        }

        $paymentTypes = app()->bound('common')
            ? app('common')->getPayoutTypeValue(true)
            : [];

        $header = [
            'increment_id', 'user_id', 'start_datetime', 'end_datetime',
            'Driver first name', 'Driver last name', 'Payment Type',
            'amount', 'transaction_id', 'payer_id', 'txn_type', 'source',
            'description', 'currency', 'charged_at', 'created',
            'Transferred Amount', 'Pulled Amount', 'Amount Type',
            'Transferred Source transaction_id', 'Transfer_id', 'Transfer created At',
        ];
        fputcsv($fp, $header);

        while (true) {
            $logObj = DB::table('audit_report_logs')
                ->where('cs_order_id', '!=', 0)
                ->whereNotNull('cs_order_id')
                ->where('report_id', $auditReport->id)
                ->where('status', 0)
                ->first();

            if (empty($logObj)) {
                fclose($fp);
                DB::table('audit_reports')->where('id', $auditReport->id)->update([
                    'file_name' => $filename,
                    'status'    => 1,
                ]);
                break;
            }

            DB::table('audit_report_logs')->where('id', $logObj->id)->update(['status' => 1]);

            $writeToCsv = array_fill(0, 22, '');
            $writeToCsv[0] = $logObj->increment_id;
            $writeToCsv[1] = $logObj->user_id;
            $writeToCsv[2] = $logObj->start_datetime;
            $writeToCsv[3] = $logObj->end_datetime;
            $writeToCsv[4] = $logObj->first_name;
            $writeToCsv[5] = $logObj->last_name;
            $orderCsv = $writeToCsv;
            fputcsv($fp, $writeToCsv);

            $paymentReports = DB::table('payment_reports')
                ->where('cs_order_id', $logObj->cs_order_id)
                ->get();

            foreach ($paymentReports as $pr) {
                $writeToCsv[6] = $paymentTypes[$pr->type] ?? $pr->type;
                $writeToCsv[7] = $pr->amount;
                $writeToCsv[8] = $pr->transaction_id;
                $writeToCsv[9] = $pr->payer_id;
                $writeToCsv[10] = $pr->txn_type == 1 ? 'Charge' : 'Refund';
                $writeToCsv[11] = $pr->source;
                $writeToCsv[12] = $pr->description;
                $writeToCsv[13] = $pr->currency;
                $writeToCsv[14] = $pr->txn_type == 1 ? $pr->charged_at : $pr->created;
                $writeToCsv[15] = $pr->created;
                fputcsv($fp, $writeToCsv);
            }

            $writeToCsv = $orderCsv;
            $csPayoutTransactions = DB::table('cs_payout_transactions')
                ->where('cs_order_id', $logObj->cs_order_id)
                ->get();

            foreach ($csPayoutTransactions as $cpt) {
                $writeToCsv[16] = $cpt->amount;
                $writeToCsv[17] = $cpt->refund;
                $writeToCsv[18] = $paymentTypes[$cpt->type] ?? $cpt->type;
                $writeToCsv[19] = $cpt->transaction_id;
                $writeToCsv[20] = $cpt->transfer_id;
                $writeToCsv[21] = $cpt->created;
                fputcsv($fp, $writeToCsv);
            }
        }
    }
}
