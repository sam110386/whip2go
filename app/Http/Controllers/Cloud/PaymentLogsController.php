<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\PaymentLogsController as AdminPaymentLogsController;
use App\Models\Legacy\AdminUserAssociation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentLogsController extends AdminPaymentLogsController
{
    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return redirect('/admin/payment_logs/index')->with('error', 'Sorry, you are not authorized user for this action');
        }
        $parentId = (int)($admin['parent_id'] ?? 0);
        $dealerIds = AdminUserAssociation::query()
            ->where('admin_id', $parentId)
            ->pluck('user_id')
            ->map(static fn ($id) => (int)$id)
            ->filter(static fn ($id) => $id > 0)
            ->values()
            ->all();

        $limit = 50;
        $dateFrom = trim((string)$request->input('Search.date_from', $request->input('date_from', '')));
        $dateTo = trim((string)$request->input('Search.date_to', $request->input('date_to', '')));
        $keyword = trim((string)$request->input('Search.keyword', $request->input('keyword', '')));

        $q = DB::table('payment_logs as p')
            ->whereIn('p.user_id', $dealerIds === [] ? [0] : $dealerIds)
            ->orderByDesc('p.id');
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
}

