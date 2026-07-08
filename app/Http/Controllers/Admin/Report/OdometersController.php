<?php
namespace App\Http\Controllers\Admin\Report;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\ReportCustomer;

class OdometersController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Vehicle Odometer Report';
        $sessionLimitKey = "odometer_report_limit";

        $query = ReportCustomer::with('vehicle:id,vin_no,modified,last_mile,vehicle_name')
            ->where('last_executed', '>', now()
                ->subDays(30)
                ->toDateString());

        $dealerid = $request->input('dealerid') ?? $request->input('Search.dealerid');

        if (!empty($dealerid)) {
            $query->where('user_id', $dealerid);
        }

        if ($request->input('search') === 'EXPORT') {
            return $this->generateCsv($query);
        }

        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionLimitKey => $limit]);
        } else {
            $limit = session($sessionLimitKey, $this->recordsPerPage);
        }

        $query->orderBy('vehicle_id', 'desc');
        $lists = $query->paginate($limit);

        if ($request->ajax()) {
            return view('admin.report.odometers.elements._odometer', compact('lists', 'dealerid', 'limit'));
        }

        return view('admin.report.odometers.index', compact('lists', 'dealerid', 'title', 'limit'));
    }
    private function generateCsv($query)
    {
        $records = $query->orderBy('vehicle_id', 'desc')->get();
        $response = new StreamedResponse(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['VIN', 'Odometer', 'Last Checked']);

            foreach ($records as $record) {
                $vehicle = $record->vehicle;
                fputcsv($handle, [
                    $vehicle->vin_no ?? '',
                    $vehicle->last_mile ?? '',
                    $vehicle?->modified ? date('m/d/Y', strtotime($record->modified)) : ''
                ]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="odometer_report.csv"');
        $response->headers->set('Cache-Control', 'max-age=0');
        return $response;
    }
}
