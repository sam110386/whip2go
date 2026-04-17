<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Admin\Report\Concerns\UsesReportPageLimit;
use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RevenueReportsController extends LegacyAppController
{
    use UsesReportPageLimit;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Vehicle Revenue Report';
        $keyword = '';
        $dealerid = '';
        $vehicleid = '';
        $date_from = '';
        $date_to = '';
        $conditions = [];

        if ($request->filled('Search')) {
            $dealerid = $request->input('Search.dealerid', '');
            $vehicleid = $request->input('Search.vehicleid', '');
            $date_from = $request->input('Search.datefrom', '');
            $date_to = $request->input('Search.dateto', '');
            if ($dealerid !== '') {
                $conditions['user_id'] = $dealerid;
            }
            if ($vehicleid !== '') {
                $conditions['vehicle_id'] = $vehicleid;
            }
            if ($date_from !== '') {
                $dtFrom = \DateTime::createFromFormat('m/Y', $date_from);
                $conditions['date_from'] = $dtFrom ? $dtFrom->format('Y-m-01') : null;
            }
            if ($date_to !== '') {
                $dtTo = \DateTime::createFromFormat('m/Y', $date_to);
                $conditions['date_to'] = $dtTo ? $dtTo->format('Y-m-t') : null;
            }
            if ($request->isMethod('post') && $request->filled('refresh')) {
                $this->revenueReport($conditions);

                return redirect()->back()->with('success', 'Revenue report generated successfully.');
            }
        }

        $limit = $this->getPageLimit($request, 'revenue_reports_limit', 50);
        $lists = DB::table('revenue_reports')->orderByDesc('vehicle_id')->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return view('admin.report.elements._revenue_report', compact(
                'lists',
                'keyword',
                'dealerid',
                'vehicleid',
                'date_from',
                'date_to',
                'title'
            ));
        }

        return view('admin.report.revenue_reports.index', compact(
            'title',
            'lists',
            'keyword',
            'dealerid',
            'vehicleid',
            'date_from',
            'date_to'
        ));
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function revenueReport(array $conditions = []): bool
    {
        $condi = '';
        if (! empty($conditions['date_from']) && ! empty($conditions['date_to'])) {
            $start_date = $conditions['date_from'];
            $end_date = $conditions['date_to'];
        } else {
            $start_date = date('Y-m-01');
            $end_date = date('Y-m-d', strtotime('last day of this month'));
        }
        $condi = " start_datetime <= '{$end_date}' AND end_datetime >= '{$start_date}'";
        if (isset($conditions['user_id']) && ! empty($conditions['user_id'])) {
            $user_id = (int) $conditions['user_id'];
            $condi .= " AND user_id = {$user_id}";
        }
        if (isset($conditions['vehicle_id']) && ! empty($conditions['vehicle_id'])) {
            $vehicle_id = (int) $conditions['vehicle_id'];
            $condi .= " AND vehicle_id = {$vehicle_id}";
        }

        DB::statement('TRUNCATE TABLE revenue_reports');

        $sql = "INSERT INTO revenue_reports (vehicle_id, vehicle_name, month, bookings,days, revenue_for_month, odometer_for_month)  
            WITH RECURSIVE date_expansion AS (
                SELECT 
                    id,
                    vehicle_id,
                    vehicle_name,
                    start_datetime,
                    end_datetime,
                    DATE(start_datetime) AS booking_date,
                    (rent + initial_fee + extra_mileage_fee + damage_fee + lateness_fee + uncleanness_fee ) AS total_revenue,
                    GREATEST(end_odometer - start_odometer, 0) AS total_odometer,
                    DATEDIFF(end_datetime, start_datetime) AS total_days
                FROM cs_orders
                WHERE status != 2
                AND {$condi}

                UNION ALL

                SELECT 
                    de.id,
                    de.vehicle_id,
                    de.vehicle_name,
                    de.start_datetime,
                    de.end_datetime,
                    DATE_ADD(de.booking_date, INTERVAL 1 DAY),
                    de.total_revenue,
                    de.total_odometer,
                    de.total_days
                FROM date_expansion de
                WHERE DATE_ADD(de.booking_date, INTERVAL 1 DAY) < DATE(de.end_datetime)
            )

            SELECT 
                vehicle_id,
                vehicle_name,
                DATE_FORMAT(booking_date, '%Y-%m') AS month,
                COUNT(DISTINCT id) AS bookings,
                COUNT(DISTINCT CONCAT(id, '-', booking_date)) AS days,
                ROUND(SUM(total_revenue / total_days), 2) AS revenue_for_month,
                ROUND(SUM(total_odometer / total_days), 2) AS odometer_for_month
                
            FROM date_expansion
            WHERE booking_date BETWEEN '2024-01-01' AND '2025-06-30'
            GROUP BY vehicle_id, vehicle_name, month
            ORDER BY month, vehicle_id";

        DB::unprepared($sql);

        return true;
    }
}
