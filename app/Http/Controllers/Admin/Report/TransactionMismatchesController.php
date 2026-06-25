<?php

namespace App\Http\Controllers\Admin\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\TransactionMismatch;
use Carbon\Carbon;


class TransactionMismatchesController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if ($request->has('refresh')) {
            $this->initiateReport();
        }

        $title = 'Transaction Mismatch Report';
        $sessionLimitName = "transaction_mismatch_limit";
        $datefrom = $dateto = date('m/Y');

        if ($request->has('Search') || $request->has('datefrom') || $request->has('dateto')) {
            $datefrom = $request->input('datefrom')
                ? base64_decode($request->input('datefrom'), true) ?: $request->input('datefrom')
                : $request->input('Search.datefrom', date('m/Y'));

            $dateto = $request->input('dateto')
                ? base64_decode($request->input('dateto'), true) ?: $request->input('dateto')
                : $request->input('Search.dateto', date('m/Y'));
        }

        try {
            $firstDay = Carbon::createFromFormat("m/Y", $datefrom)->startOfMonth()->format("Y-m-d");
            $lastDay = Carbon::createFromFormat("m/Y", $dateto)->endOfMonth()->format("Y-m-d");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Sorry, something went wrong. Please try again');
        }

        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionLimitName => $limit]);
        } else {
            $limit = session($sessionLimitName, $this->recordsPerPage);
        }

        $query = TransactionMismatch::where('charged_at', '>', $firstDay)
            ->whereRaw("DATE_FORMAT(charged_at, '%Y-%m-%d') < ?", [$lastDay])
            ->orderBy('charged_at', 'DESC');

        $lists = $query->paginate($limit);


        if ($request->ajax()) {
            return view('admin.report.transaction_mismatches.elements._transaction_mismatch', compact('title', 'datefrom', 'dateto', 'lists', 'limit'));
        }

        return view('admin.report.transaction_mismatches.index', compact('title', 'datefrom', 'dateto', 'lists', 'limit'));
    }
    private function initiateReport(): void
    {
        $createViewSql = "
            CREATE OR REPLACE VIEW transaction_mismatches_view AS 
            SELECT 
                cpl.cs_order_id AS cpl_order_id,
                cpl.created AS cpl_created,
                cpl.amount AS cpl_amount,
                cpl.transaction_id AS cpl_transaction_id,
                cop.amount,
                cop.transaction_id,
                cop.cs_order_id,
                cop.charged_at 
            FROM cs_payment_logs AS cpl 
            LEFT JOIN cs_order_payments AS cop ON cop.transaction_id = cpl.transaction_id 
            WHERE cpl.amount != cop.amount 
            AND cop.status = 1 
            AND cpl.status = 1 
            AND cpl.old_transaction_id IS NULL
        ";

        DB::unprepared($createViewSql);
        DB::table('transaction_mismatches')->truncate();

        $insertSql = "
            INSERT INTO transaction_mismatches (
                cs_order_id, 
                charged_at, 
                cpl_amount, 
                cpl_transaction_id, 
                c_amount, 
                c_transaction_id
            ) 
            SELECT 
                cpl_order_id, 
                cpl_created, 
                cpl_amount, 
                cpl_transaction_id, 
                SUM(amount) AS camt, 
                transaction_id 
            FROM transaction_mismatches_view 
            GROUP BY 
                transaction_id, 
                cpl_order_id, 
                cpl_created, 
                cpl_amount, 
                cpl_transaction_id
            HAVING cpl_amount != camt
        ";

        DB::unprepared($insertSql);
    }
}
