<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentLogsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function admin_index(Request $request)
    {
        $limit = $this->resolveLimit($request, 'payment_logs_limit');
        $dateFrom = trim((string)$this->searchInput($request, 'date_from'));
        $dateTo = trim((string)$this->searchInput($request, 'date_to'));
        $keyword = trim((string)$this->searchInput($request, 'keyword'));

        $q = DB::table('payment_logs as p')->orderByDesc('p.id');
        if ($dateFrom !== '') {
            $q->whereDate('p.created', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $q->whereDate('p.created', '<=', $dateTo);
        }
        if ($keyword !== '') {
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            $q->where(function ($qq) use ($like) {
                $qq->where('p.transaction_id', 'like', $like)
                    ->orWhere('p.reference_id', 'like', $like)
                    ->orWhere('p.message', 'like', $like);
            });
        }

        $rows = $q->paginate($limit)->withQueryString();

        return view('admin.payment_logs.index', compact('rows', 'dateFrom', 'dateTo', 'keyword', 'limit'));
    }

    private function searchInput(Request $request, string $key): ?string
    {
        $v = $request->input('Search.' . $key);
        if ($v !== null && $v !== '') {
            return (string)$v;
        }

        return $request->input($key);
    }

    private function resolveLimit(Request $request, string $sessionKey): int
    {
        if ($request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session([$sessionKey => $lim]);
            }
        }
        $limit = (int)session($sessionKey, 50);

        return $limit > 0 ? $limit : 50;
    }
}

