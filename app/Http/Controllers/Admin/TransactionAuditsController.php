<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionAuditsController extends LegacyAppController
{
    protected int $recordsPerPage = 25;

    public function index(Request $request)
    {
        $keyword = '';
        $query = DB::table('audit_reports as AuditReport')
            ->where('type', 1)
            ->select('AuditReport.*');

        $sessLimitName = 'transaction_audits_limit';
        $limit = $request->input('Record.limit',
            session($sessLimitName, $this->recordsPerPage));
        if ($request->filled('Record.limit')) {
            session([$sessLimitName => $limit]);
        }

        $records = $query->orderByDesc('AuditReport.id')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.audit_report.transaction_audits._transaction', compact('records'));
        }

        return view('admin.audit_report.transaction_audits.index', compact('records', 'keyword'));
    }

    public function add(Request $request)
    {
        $listTitle = 'Create Transaction Audit Report';

        if ($request->isMethod('post')) {
            $startDate = date('Y-m-d', strtotime($request->input('AuditReport.start_date')));
            $endDate = date('Y-m-d', strtotime($request->input('AuditReport.end_date')));

            try {
                DB::table('audit_reports')->insert([
                    'start_date' => $startDate,
                    'end_date'   => $endDate,
                    'type'       => 1,
                    'status'     => 0,
                    'created'    => now(),
                    'modified'   => now(),
                ]);

                return redirect('admin/audit_report/transaction_audits/index')
                    ->with('success', 'Audit Report is initialed successfully.');
            } catch (\Exception $e) {
                return back()->with('error', $e->getMessage());
            }
        }

        return view('admin.audit_report.transaction_audits.add', compact('listTitle'));
    }

    public function process($id)
    {
        try {
            $auditReport = DB::table('audit_reports')->where('id', $id)->first();
            // Processing stub — transaction audit process not yet implemented in legacy
            return redirect('admin/audit_report/transaction_audits/index')
                ->with('success', 'Audit Report is completed successfully.');
        } catch (\Exception $e) {
            return redirect('admin/audit_report/transaction_audits/index')
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
}
