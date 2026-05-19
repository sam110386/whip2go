<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Legacy\AuditReport;
use Carbon\Carbon;


class TransactionAuditsController extends LegacyAppController
{
    public function index(Request $request)
    {
        $title = 'Transactions Report';
        $keyword = '';
        $sessionLimitKey = 'transaction_audits_limit';
        $query = AuditReport::query()->where('type', 1);

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
            return view('admin.audit_report.elements._transaction', compact('title', 'keyword', 'records', 'limit'));
        }

        return view('admin.audit_report.transaction_audits.index', compact('title', 'keyword', 'records', 'limit'));
    }

    public function add(Request $request)
    {
        $title = 'Create Transaction Audit Report';

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
                    'type' => 1,
                    'status' => 0,
                ]);

                return redirect('admin/transaction_audits/index')->with('success', 'Audit Report is initialized successfully.');

            } catch (\Exception $e) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('error', 'Something Went Wrong!');
            }
        }

        return view('admin.audit_report.transaction_audits.add', compact('title'));
    }

    public function process($id)
    {
        try {
            $auditReport = AuditReport::findOrFail($id);
            return redirect('admin/transaction_audits/index')->with('success', 'Audit Report is completed successfully.');
        } catch (\Exception $e) {
            return redirect('admin/transaction_audits/index')
                ->with('error', 'Something Went Wrong!');
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
