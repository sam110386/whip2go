<?php

namespace App\Http\Controllers\Legacy;

use App\Services\Legacy\PathToOwnership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WidgetsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private string $logfile;

    public function __construct()
    {
        parent::__construct();
        $this->logfile = public_path('files/widgets/' . date('Y-m-d') . '_log.jsonl');
    }

    /**
     * CORS headers — called from middleware or manually before each action.
     */
    protected function applyCorsHeaders(Request $request): array
    {
        $origin = $request->header('Origin', '');
        $allowedOrigins = [
            'https://www.chapmannissan.com',
            'localhost',
        ];

        $allowOrigin = in_array($origin, $allowedOrigins, true) ? $origin : '*';

        return [
            'Access-Control-Allow-Origin' => $allowOrigin,
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type',
        ];
    }

    /**
     * Handle OPTIONS preflight requests.
     */
    public function preflight(Request $request)
    {
        return response('', 200)->withHeaders($this->applyCorsHeaders($request));
    }

    /**
     * Cake `index($vin)`: JSON API — looks up vehicle by VIN, returns HTML button snippet.
     */
    public function index(Request $request, $vin = null)
    {
        $cors = $this->applyCorsHeaders($request);

        if (!$vin) {
            return response()->json(['error' => 'VIN is required'])->withHeaders($cors);
        }

        $vehicle = DB::table('vehicles')
            ->where('vin_no', $vin)
            ->where('status', 1)
            ->where('trash', 0)
            ->where('day_rent', '>', 0)
            ->where(function ($q) {
                $q->where('booked', 0)
                  ->orWhere('type', 'demo');
            })
            ->select('id', 'vin_no', 'make', 'model', 'year', 'homenet_msrp', 'premium_msrp', 'day_rent')
            ->first();

        if (!$vehicle) {
            return response()->json(['error' => 'Vehicle not found'])->withHeaders($cors);
        }

        $html = '<p><button onclick="window.DIAWidget.open(\'' . e($vehicle->vin_no) . '\')" style="{buttonCss}">{buttonText}</button></p>';

        return response()->json(['html' => $html])->withHeaders($cors);
    }

    /**
     * Cake `program($vin, $provider)`: JSON API — looks up vehicle with user join,
     * calculates fare via PathToOwnership, logs event to JSONL.
     */
    public function program(Request $request, $vin = null, $provider = null)
    {
        $cors = $this->applyCorsHeaders($request);

        if (!$vin) {
            return response()->json(['error' => 'VIN is required'])->withHeaders($cors);
        }

        $vehicle = DB::table('vehicles')
            ->leftJoin('users', 'users.id', '=', 'vehicles.user_id')
            ->where('vehicles.vin_no', $vin)
            ->where('vehicles.status', 1)
            ->where('vehicles.trash', 0)
            ->where('vehicles.day_rent', '>', 0)
            ->where(function ($q) {
                $q->where('vehicles.booked', 0)
                  ->orWhere('vehicles.type', 'demo');
            })
            ->select(
                'vehicles.*',
                'users.first_name',
                'users.last_name',
                'users.business_name',
                'users.timezone',
                'users.currency',
                'users.distance_unit'
            )
            ->first();

        if (!$vehicle) {
            return response()->json(['error' => 'Vehicle not found'])->withHeaders($cors);
        }

        $vehicleData = (array) $vehicle;
        $vehicleData['currency'] = $vehicle->currency;
        $vehicleData['timezone'] = $vehicle->timezone;

        $pto = new PathToOwnership();
        $calculations = $pto->getVehicleDynamicFareMatrix($vehicleData, []);

        $this->appendLog([
            'event' => 'widget_program_button_clicked',
            'vin' => $vin,
            'referer' => $provider,
            'timestamp' => date('c'),
            'ip' => $request->ip(),
            'user' => '',
        ]);

        return response()->json([
            'calculations' => $calculations,
            'vehicle' => $vehicle->id,
        ])->withHeaders($cors);
    }

    /**
     * Cake `save($vin, $provider)`: JSON API — logs redirect event, returns redirect URL.
     */
    public function save(Request $request, $vin = null, $provider = null)
    {
        $cors = $this->applyCorsHeaders($request);

        if (!$provider) {
            return response()->json(['error' => 'provider is required'])->withHeaders($cors);
        }

        $this->appendLog([
            'event' => 'widget_redirect_to_dia_clicked',
            'vin' => $vin,
            'referer' => $provider,
            'timestamp' => date('c'),
            'ip' => $request->ip(),
            'user' => '',
        ]);

        $diawebUrl = config('legacy.diaweb_url', 'https://cars.driveitaway.com');

        return response()->json([
            'status' => true,
            'message' => 'success',
            'redirect' => $diawebUrl . '/car/details/',
        ])->withHeaders($cors);
    }

    /**
     * Append a JSON line to the daily widget log file.
     */
    private function appendLog(array $data): void
    {
        $dir = public_path('files/widgets');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $jsonLine = json_encode($data) . "\n";
        @file_put_contents($this->logfile, $jsonLine, FILE_APPEND);
    }
}
