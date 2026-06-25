<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Admin\Report\Concerns\UsesReportPageLimit;
use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Legacy\RevenueReport;
use Carbon\Carbon;


class RevenueReportsController extends LegacyAppController
{
    use UsesReportPageLimit;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Vehicle Revenue Report';
        $keyword = $dealerid = $vehicleid = $date_from = $date_to = '';
        $sessionLimitKey = 'revenue_report_limit';
        $query = RevenueReport::query();

        if ($request->has('Search')) {
            $search = $request->input('Search');
            $dealerid = $search['dealerid'] ?? '';
            $vehicleid = $search['vehicleid'] ?? '';
            $date_from = $search['datefrom'] ?? '';
            $date_to = $search['dateto'] ?? '';

            if (!empty($dealerid)) {
                $query->where('user_id', $dealerid);
            }

            if (!empty($vehicleid)) {
                $query->where('vehicle_id', $vehicleid);
            }

            if (!empty($date_from)) {
                $formattedFrom = Carbon::createFromFormat('m/Y', $date_from)->startOfMonth()->format('Y-m-d');
                $query->where('date_from', $formattedFrom);
            }

            if (!empty($date_to)) {
                $formattedTo = Carbon::createFromFormat('m/Y', $date_to)->endOfMonth()->format('Y-m-d');
                $query->where('date_to', $formattedTo);
            }

            if ($request->isMethod('post') && $request->has('refresh')) {
                $this->_revenueReport($query->getBindings());
                return redirect()->back()->with('success', 'Revenue report generated successfully.');
            }
        }

        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            $request->session()->put($sessionLimitKey, $limit);
        } else {
            $limit = $request->session()->get($sessionLimitKey, $this->recordsPerPage ?? 10);
        }

        $request->merge(['Record' => ['limit' => $limit]]);
        $lists = $query->orderBy('vehicle_id', 'desc')->paginate($limit);


        if ($request->ajax()) {
            return view('admin.report.revenue_reports.elements._revenue_report', compact('title', 'lists', 'keyword', 'dealerid', 'vehicleid', 'date_from', 'date_to', 'limit'));
        }

        return view('admin.report.revenue_reports.index', compact('title', 'lists', 'keyword', 'dealerid', 'vehicleid', 'date_from', 'date_to', 'limit'));
    }

    private function _revenueReport($conditions = [])
    {
        if (!empty($conditions['date_from']) && !empty($conditions['date_to'])) {
            $startDate = $conditions['date_from'];
            $endDate = $conditions['date_to'];
        } else {
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $whereClauses = ["start_datetime <= :end_date", "end_datetime >= :start_date"];
        $bindings = [
            'end_date' => $endDate,
            'start_date' => $startDate
        ];

        if (!empty($conditions['user_id'])) {
            $whereClauses[] = "user_id = :user_id";
            $bindings['user_id'] = $conditions['user_id'];
        }

        if (!empty($conditions['vehicle_id'])) {
            $whereClauses[] = "vehicle_id = :vehicle_id";
            $bindings['vehicle_id'] = $conditions['vehicle_id'];
        }

        $condiString = implode(' AND ', $whereClauses);

        RevenueReport::truncate();

        $sql = "INSERT INTO revenue_reports (vehicle_id, vehicle_name, month, bookings, days, revenue_for_month, odometer_for_month)  
            WITH RECURSIVE date_expansion AS (
                SELECT 
                    id,
                    vehicle_id,
                    vehicle_name,
                    start_datetime,
                    end_datetime,
                    DATE(start_datetime) AS booking_date,
                    (rent + initial_fee + extra_mileage_fee + damage_fee + lateness_fee + uncleanness_fee) AS total_revenue,
                    GREATEST(end_odometer - start_odometer, 0) AS total_odometer,
                    DATEDIFF(end_datetime, start_datetime) AS total_days
                FROM cs_orders
                WHERE status != 2
                AND {$condiString}

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
            WHERE booking_date BETWEEN :report_start AND :report_end
            GROUP BY vehicle_id, vehicle_name, month
            ORDER BY month, vehicle_id";

        $bindings['report_start'] = '2024-01-01';
        $bindings['report_end'] = '2025-06-30';

        DB::statement($sql, $bindings);
        return true;
    }
}
