<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\CsOrder;
use App\Models\Legacy\CsPaymentLog;
use Illuminate\Support\Collection;

trait PaymentLogsTrait
{
    /**
     * Shared: fetch the latest payment logs for a given order (and its siblings).
     * Used by Legacy index() AJAX modal and Admin/Cloud index().
     *
     * @param int   $orderId
     * @param int   $limit
     * @param bool  $includeParent  When true, includes parent+child orders (Legacy).
     * @param int|null $userId      When set, scopes sibling search to dealer.
     */
    protected function fetchPaymentLogs(int $orderId, int $limit = 200, bool $includeParent = true, ?int $userId = null): array
    {
        if (empty($orderId)) {
            return ['logs' => collect(), 'allSiblings' => collect()];
        }

        if ($includeParent) {
            // Get all sibling orders (same parent or this order is the parent)
            $siblingQuery = CsOrder::whereRaw("(id = ? OR parent_id = ?)", [$orderId, $orderId])
                ->select('id', 'increment_id', 'start_datetime', 'end_datetime', 'timezone');

            if ($userId !== null) {
                $siblingQuery->where('user_id', $userId);
            }

            $allSiblings = $siblingQuery->get()->keyBy('id');
            $siblingIds  = $allSiblings->keys()->toArray();
        } else {
            $allSiblings = collect();
            $siblingIds  = [$orderId];
        }

        $logs = CsPaymentLog::whereIn('cs_order_id', $siblingIds)
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get();

        return ['logs' => $logs, 'allSiblings' => $allSiblings];
    }

    /**
     * Shared: filter payment logs by date range and status (used by Legacy `all()`).
     */
    protected function buildPaymentLogQuery(int $orderId, int $userId, array $search): \Illuminate\Pagination\LengthAwarePaginator
    {
        $allSiblings = CsOrder::where('user_id', $userId)
            ->whereRaw("(id = ? OR parent_id = ?)", [$orderId, $orderId])
            ->select('id', 'increment_id', 'start_datetime', 'end_datetime', 'timezone')
            ->get()
            ->keyBy('id');

        $siblingIds = $allSiblings->keys()->toArray();

        $query = CsPaymentLog::whereIn('cs_order_id', $siblingIds)
            ->orderBy('id', 'DESC');

        if (!empty($search['date_from'])) {
            $query->where('created_at', '>=', $search['date_from']);
        }
        if (!empty($search['date_to'])) {
            $query->where('created_at', '<=', $search['date_to']);
        }
        if ($search['status'] !== '' && $search['status'] !== null) {
            $query->where('status', $search['status']);
        }

        return $query->paginate(session('PaymentLogs_limit', 20))->withQueryString();
    }
}
