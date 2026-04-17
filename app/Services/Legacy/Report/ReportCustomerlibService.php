<?php

namespace App\Services\Legacy\Report;

use Illuminate\Support\Facades\DB;

class ReportCustomerlibService
{
    protected ReportCustomerService $reportCustomer;

    public function __construct(ReportCustomerService $reportCustomer)
    {
        $this->reportCustomer = $reportCustomer;
    }

    public function saveReportQueue(?int $orderId = null): void
    {
        if (empty($orderId)) {
            return;
        }

        DB::statement(
            'INSERT IGNORE INTO report_queues (order_id, created, updated) VALUES (?, NOW(), NOW())',
            [$orderId]
        );
    }

    public function processQueue(): void
    {
        $queues = DB::table('report_queues')
            ->orderBy('id')
            ->limit(10)
            ->pluck('order_id', 'id');

        if ($queues->isEmpty()) {
            DB::statement('TRUNCATE report_queues');

            return;
        }

        foreach ($queues as $queueId => $orderId) {
            $booking = DB::table('cs_orders')
                ->where('id', $orderId)
                ->select('id', 'parent_id')
                ->first();

            $orderId = ($booking !== null && ! empty($booking->parent_id))
                ? (int) $booking->parent_id
                : (int) $orderId;

            $exists = DB::table('report_customers')
                ->where('cs_order_id', $orderId)
                ->value('id');

            if ($exists) {
                $this->reportCustomer->refreshReport((int) $exists);
            } else {
                $this->reportCustomer->createReport($orderId);
                $record = DB::table('report_customers')
                    ->where('cs_order_id', $orderId)
                    ->value('id');
                if ($record) {
                    $this->reportCustomer->refreshReport((int) $record);
                }
            }

            DB::table('report_queues')->where('id', $queueId)->delete();
        }
    }
}
