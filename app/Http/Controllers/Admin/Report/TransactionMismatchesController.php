<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Admin\Report\Concerns\UsesReportPageLimit;
use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionMismatchesController extends LegacyAppController
{
    use UsesReportPageLimit;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if ($request->filled('refresh')) {
            $this->initiateReport();
        }

        $title = 'Transaction Mismatch Report';
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

        $limit = $this->getPageLimit($request, 'transaction_mismatches_limit', 50);

        $lists = DB::table('transaction_mismatches')
            ->whereRaw("charged_at > ?", [$firstDay])
            ->whereRaw("DATE_FORMAT(charged_at,'%Y-%m-%d') < ?", [$lastDay])
            ->orderByDesc('charged_at')
            ->paginate($limit)
            ->withQueryString();

        if ($request->ajax()) {
            return view('admin.report.elements._transaction_mismatch', compact('lists', 'datefrom', 'dateto', 'title'));
        }

        return view('admin.report.transaction_mismatches.index', compact('title', 'lists', 'datefrom', 'dateto'));
    }

    private function initiateReport(): void
    {
        $sql = 'CREATE OR REPLACE VIEW transaction_mismatches_view AS SELECT cpl.cs_order_id as cpl_order_id,cpl.created as cpl_created,cpl.amount as cpl_amount,cpl.transaction_id as cpl_transaction_id,cop.amount,cop.transaction_id,cop.cs_order_id,cop.charged_at FROM cs_payment_logs as cpl left join cs_order_payments as cop ON cop.transaction_id=cpl.transaction_id where cpl.amount!=cop.amount and cop.status=1 and cpl.status=1 and cpl.old_transaction_id IS NULL';
        DB::unprepared($sql);
        DB::statement('truncate transaction_mismatches');

        $qry = 'INSERT INTO transaction_mismatches (cs_order_id,charged_at,cpl_amount,cpl_transaction_id,c_amount,c_transaction_id) SELECT cpl_order_id,cpl_created,cpl_amount,cpl_transaction_id,SUM(amount) as camt,transaction_id FROM transaction_mismatches_view GROUP by transaction_id having cpl_amount!=camt';
        DB::unprepared($qry);
    }
}
