<?php

namespace App\Services\Legacy\Report;

use App\Models\Legacy\ReportQueue;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\ReportCustomer;

class ReportCustomerlibService
{

    public function saveReportQueue($orderId = null)
    {
        if (empty($orderId)) {
            return;
        }

        ReportQueue::insertOrIgnore([
            'order_id' => $orderId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return;
    }

    public function processQueue()
    {
        $queues = ReportQueue::orderBy('id', 'asc')
            ->limit(10)
            ->pluck('order_id', 'id');

        if ($queues->isEmpty()) {
            ReportQueue::truncate();
            return;
        }

        foreach ($queues as $queueId => $orderId) {
            $booking = CsOrder::select('id', 'parent_id')->find($orderId);

            if ($booking && $booking->parent_id) {
                $orderId = $booking->parent_id;
            }

            $exists = ReportCustomer::where('cs_order_id', $orderId)->first(['id']);

            if ($exists) {
                ReportCustomer::refreshReport($exists->id);
            } else {
                ReportCustomer::createReport($orderId);
                $record = ReportCustomer::orderBy('id', 'desc')->first(['id']);

                if ($record) {
                    ReportCustomer::refreshReport($record->id);
                }
            }

            ReportQueue::destroy($queueId);
        }

        return;
    }
}
