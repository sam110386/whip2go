<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Traits\ReportsTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\Vehicle;
use App\Models\Legacy\CsOrderPayment;

class ReportsController extends LegacyAppController
{
    use ReportsTrait;
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        $limit = $this->resolveLimit($request, 'admin_reports_limit');

        $keyword = trim((string) $this->searchInput($request, 'keyword'));
        $fieldname = trim((string) $this->searchInput($request, 'searchin'));
        $status_type = trim((string) $this->searchInput($request, 'status_type'));
        $dateFrom = trim((string) $this->searchInput($request, 'date_from'));
        $dateTo = trim((string) $this->searchInput($request, 'date_to'));
        $dealerid = trim((string) $this->searchInput($request, 'dealer_id'));
        $renterid = trim((string) $this->searchInput($request, 'renter_id'));

        if ($request->has('ClearFilter')) {
            session()->forget('admin_reports_search');
            return redirect('/admin/reports/index');
        }

        if ($request->has('search') || $request->has('Search')) {
            session([
                'admin_reports_search' => [
                    'keyword' => $keyword,
                    'fieldname' => $fieldname,
                    'status_type' => $status_type,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                    'dealerid' => $dealerid,
                    'renterid' => $renterid,
                ]
            ]);
        } else {
            $sess = session('admin_reports_search', []);
            $keyword = $sess['keyword'] ?? $keyword;
            $fieldname = $sess['fieldname'] ?? $fieldname;
            $status_type = $sess['status_type'] ?? $status_type;
            $dateFrom = $sess['date_from'] ?? $dateFrom;
            $dateTo = $sess['date_to'] ?? $dateTo;
            $dealerid = $sess['dealerid'] ?? $dealerid;
            $renterid = $sess['renterid'] ?? $renterid;
        }

        $q = CsOrder::query()
            ->from('cs_orders as o')
            ->leftJoin('users as renter', 'renter.id', '=', 'o.renter_id')
            ->leftJoin('users as owner', 'owner.id', '=', 'o.user_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'o.vehicle_id')
            ->select([
                'o.*',
                'renter.first_name as renter_first_name',
                'renter.last_name as renter_last_name',
                'owner.first_name as owner_first_name',
                'owner.last_name as owner_last_name',
                'owner.business_name as owner_business_name',
                'v.make',
                'v.model',
                'v.year',
                'v.vin_no'
            ]);

        $q->where('o.parent_id', 0);

        if ($keyword !== '') {
            if ($fieldname === '1') {
                $q->where('o.pickup_address', 'LIKE', '%' . $keyword . '%');
            } elseif ($fieldname === '2') {
                $q->where('o.vehicle_name', $keyword);
            } elseif ($fieldname === '3') {
                $q->where('o.increment_id', $keyword);
            }
        }

        if ($dateFrom !== '') {
            $q->whereDate('o.start_datetime', '>=', Carbon::parse($dateFrom)->toDateString());
        }
        if ($dateTo !== '') {
            $q->whereDate('o.end_datetime', '<=', Carbon::parse($dateTo)->toDateString());
        }

        if ($status_type !== '') {
            if ($status_type === 'cancel') {
                $q->where('o.status', 2);
            } elseif ($status_type === 'complete') {
                $q->where('o.status', 3);
            } elseif ($status_type === 'incomplete') {
                $q->whereIn('o.status', [0, 1]);
            }
        }

        if ($dealerid !== '') {
            $q->where('o.user_id', $dealerid);
        }
        if ($renterid !== '') {
            $q->where('o.renter_id', $renterid);
        }

        if ($request->input('search') === 'EXPORT' || $request->input('Search.search') === 'EXPORT') {
            return $this->export($q->orderBy('o.id', 'asc')->get());
        }

        $allowedSorts = ['increment_id', 'start_datetime', 'end_datetime'];
        $sort = $request->input('sort');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort && in_array($sort, $allowedSorts)) {
            $q->orderBy('o.' . $sort, $direction);
        } else {
            $q->orderByDesc('o.id');
        }

        $reportlists = $q->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return response()->view('admin.reports.elements.index', compact('reportlists'));
        }

        return view('admin.reports.index', compact(
            'reportlists',
            'dateFrom',
            'dateTo',
            'keyword',
            'fieldname',
            'status_type',
            'dealerid',
            'renterid',
            'limit'
        ));
    }

    private function export($orders)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Booking_Report.csv"',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ["No", "Booking No", "Duration", "Total Rental", "Mileage", "Insurance", "Type", "Car Info", "VIN Number", "Start Date", "End Date", "Owner Name", "Driver Name", "DIA Commission", "Late Fee Share", "Late Insurance", "City Tax", "Tourism Surcharge", "City", "Amount To Owner"]);

            $i = 1;
            foreach ($orders as $o) {
                $duration = Carbon::parse($o->start_datetime)->diffInDays(Carbon::parse($o->end_datetime));
                $carInfo = trim(($o->make ?? '') . ' ' . ($o->model ?? '') . ' ' . ($o->year ?? ''));

                fputcsv($file, [
                    $i++,
                    $o->increment_id,
                    $duration,
                    $o->rent,
                    $o->end_odometer,
                    $o->insurance_amt,
                    $o->parent_id ? "Extended" : "",
                    $carInfo,
                    $o->vin_no ?? '',
                    Carbon::parse($o->start_datetime)->format('m/d/Y'),
                    Carbon::parse($o->end_datetime)->format('m/d/Y'),
                    $o->owner_business_name ?: ($o->owner_first_name . ' ' . $o->owner_last_name),
                    $o->renter_first_name . ' ' . $o->renter_last_name,
                    sprintf("%0.2f", ($o->rent * 15 / 100)),
                    0.0,
                    0.0,
                    3.43,
                    2,
                    $o->pickup_address,
                    sprintf("%0.2f", ($o->rent * 85 / 100))
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function details($id)
    {
        return $this->_details($id);
    }

    public function loadsubbooking($orderid)
    {
        $id = $this->decodeId((string) $orderid);
        if (!$id) {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong']);
        }
        $subbookinglists = CsOrder::query()
            ->from('cs_orders as o')
            ->leftJoin('users as u', 'u.id', '=', 'o.renter_id')
            ->where('o.id', $id)->orWhere('o.parent_id', $id)
            ->orderByDesc('o.id')
            ->select('o.*', 'u.first_name', 'u.last_name')
            ->get()
            ->map(function ($item) {
                return ['CsOrder' => $item->toArray(), 'User' => ['first_name' => $item->first_name, 'last_name' => $item->last_name]];
            })->toArray();

        if (empty($subbookinglists)) {
            return response()->json(['status' => 'error', 'message' => 'Sorry, no record found']);
        }

        $booking_id = $id;
        $html = view('admin.reports.elements.loadsubbooking', compact('subbookinglists', 'booking_id'))->render();

        return response()->json(['status' => 'success', 'booking_id' => $id, 'data' => $html]);
    }

    public function autorenewddetails($id)
    {
        return $this->_autorenewddetails($id);
    }

    public function productivity(Request $request)
    {
        $dateFrom = trim((string) $this->searchInput($request, 'date_from'));
        $dateTo = trim((string) $this->searchInput($request, 'date_to'));
        $user_id = trim((string) $this->searchInput($request, 'user_id'));

        $q = Vehicle::query()
            ->from('vehicles as v')
            ->leftJoin('cs_orders as o', function ($join) {
                $join->on('o.vehicle_id', '=', 'v.id')->where('o.status', '=', 3);
            })
            ->select([
                'v.id',
                'v.vehicle_name',
                'v.msrp',
                'v.created as created_at',
                DB::raw('SUM(o.rent + o.initial_fee + o.damage_fee + o.uncleanness_fee) as totalrent'),
                DB::raw('SUM(o.end_odometer - o.start_odometer) as mileage'),
                DB::raw('SUM(DATEDIFF(o.end_datetime, o.start_datetime)) as totaldays'),
                DB::raw('SUM(o.extra_mileage_fee) as extra_mileage_fee')
            ])
            ->groupBy('v.id', 'v.vehicle_name', 'v.msrp', 'v.created');

        if ($user_id !== '') {
            $q->where('v.user_id', $user_id);
        }

        // Date filters apply to the orders joined
        if ($dateFrom !== '') {
            $q->whereDate('o.end_datetime', '>=', Carbon::parse($dateFrom)->toDateString());
        }
        if ($dateTo !== '') {
            $q->whereDate('o.end_datetime', '<=', Carbon::parse($dateTo)->toDateString());
        }

        if ($request->input('search') === 'EXPORT' || $request->input('Search.search') === 'EXPORT') {
            return $this->exportproductivity($q->orderByDesc('v.id')->get(), $dateFrom, $dateTo);
        }

        $allowedSorts = ['vehicle_name', 'msrp', 'totalrent', 'mileage', 'totaldays', 'extra_mileage_fee'];
        $sort = $request->input('sort');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if ($sort && in_array($sort, $allowedSorts)) {
            if (in_array($sort, ['vehicle_name', 'msrp'])) {
                $q->orderBy('v.' . $sort, $direction);
            } else {
                $q->orderBy($sort, $direction);
            }
        } else {
            $q->orderByDesc('v.id');
        }

        $limit = $this->resolveLimit($request, 'admin_productivity_limit');
        $reportlists = $q->paginate($limit)->withQueryString();

        return view('admin.reports.productivity', compact('reportlists', 'dateFrom', 'dateTo', 'user_id', 'limit'));
    }

    private function exportproductivity($data, $dateFrom, $dateTo)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="Productivity_Report.csv"',
        ];

        $callback = function () use ($data, $dateFrom, $dateTo) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ["Vehicle", "Vehicle Cost", "Depreciation", "Base Uses ($)", "Extra Usage", "Total Usage Fee", "Total Distance", "Total Days", "Idle Days"]);

            $portfolioSvc = new \App\Services\Legacy\Portfolio();
            foreach ($data as $r) {
                $expenses = $portfolioSvc->getVehicleExpenses($r->id, $dateFrom, $dateTo);

                if (empty($dateFrom) || empty($dateTo)) {
                    $totalRangeDays = Carbon::parse($r->created_at)->diffInDays(Carbon::now());
                } else {
                    $totalRangeDays = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo));
                }

                $totalUsageFee = (float) $r->totalrent + (float) $r->extra_mileage_fee;
                $idleDays = $totalRangeDays - (int) $r->totaldays;

                fputcsv($file, [
                    $r->vehicle_name,
                    $r->msrp,
                    $expenses['depreciation'] ?: 0,
                    sprintf('%0.2f', $r->totalrent ?: 0),
                    $r->extra_mileage_fee ?: 0,
                    sprintf('%0.2f', $totalUsageFee),
                    $r->mileage ?: 0,
                    $r->totaldays ?: 0,
                    $idleDays > 0 ? $idleDays : 0
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function paymentspopup(Request $request)
    {
        $id = $this->decodeId((string) $request->input('orderid', ''));
        if (!$id) {
            return response('Invalid booking id', 400);
        }
        $payments = CsOrderPayment::where('cs_order_id', $id)->where('status', 1)->orderByDesc('id')->get();
        $paymentTypeValue = app(\App\Services\Legacy\Common::class)->getPayoutTypeValue(true);

        return response()->view('reports._paymentspopup', compact('payments', 'paymentTypeValue'));
    }

    private function searchInput(Request $request, string $key): ?string
    {
        $v = $request->input('Search.' . $key);
        if ($v !== null && $v !== '') {
            return (string) $v;
        }

        return $request->input($key);
    }

    private function resolveLimit(Request $request, string $sessionKey): int
    {
        if ($request->has('Record.limit')) {
            $lim = (int) $request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session([$sessionKey => $lim]);
            }
        }
        $limit = (int) session($sessionKey, 50);

        return $limit > 0 ? $limit : 50;
    }

}

