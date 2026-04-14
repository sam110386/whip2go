<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Admin\Report\Concerns\UsesReportPageLimit;
use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OdometersController extends LegacyAppController
{
    use UsesReportPageLimit;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Vehicle Odometer Report';
        $dealerid = '';
        $threshold = date('Y-m-d', strtotime('-30 days'));

        $query = DB::table('report_customers as rc')
            ->leftJoin('vehicles as v', 'v.id', '=', 'rc.vehicle_id')
            ->whereRaw('rc.last_executed > ?', [$threshold])
            ->select(
                'rc.increment_id',
                'rc.vehicle_id',
                'v.id as vehicle_join_id',
                'v.vin_no',
                'v.modified',
                'v.last_mile',
                'v.vehicle_name'
            )
            ->orderByDesc('rc.vehicle_id');

        if ($request->filled('Search') || $request->query->has('dealerid')) {
            $dealerid = $request->input('Search.dealerid', $request->query('dealerid', ''));
            if ($dealerid !== '') {
                $query->where('rc.user_id', $dealerid);
            }
        }

        if ($request->input('search') === 'EXPORT') {
            return $this->generateCsv($query);
        }

        $limit = $this->getPageLimit($request, 'odometers_limit', 50);
        $lists = (clone $query)->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return view('admin.report.elements._odometer', compact('lists', 'dealerid', 'title'));
        }

        return view('admin.report.odometers.index', compact('title', 'lists', 'dealerid'));
    }

    private function generateCsv($baseQuery): StreamedResponse
    {
        $records = (clone $baseQuery)->get();

        return response()->stream(function () use ($records) {
            $header = ['VIN', 'Odometer', 'Last Checked'];
            $fp = fopen('php://output', 'w');
            fputcsv($fp, $header);
            foreach ($records as $record) {
                fputcsv($fp, [
                    $record->vin_no,
                    $record->last_mile,
                    $record->modified ? date('m/d/Y', strtotime((string) $record->modified)) : '',
                ]);
            }
            fclose($fp);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="odometer_report.csv"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
