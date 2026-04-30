<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Legacy\MetroExport;

class MetroExportsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private const SESSION_LIMIT_KEY = 'metro_exports_limit';

    protected function exportDir(): string
    {
        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'webroot'
            . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'metroexport';
    }

    /**
     * Cake `admin_index`: paginated metro export queue; optional per-page limit in session.
     */
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $limit = $this->resolveLimit($request);

        $exports = null;
        if (Schema::hasTable('metro_exports')) {
            $exports = MetroExport::orderByDesc('id')
                ->paginate($limit)
                ->appends(['Record' => ['limit' => $limit]])
                ->withQueryString();
        }

        return view('admin.metro_exports.index', [
            'title_for_layout' => 'Metro Export',
            'exports' => $exports,
            'limit' => $limit,
        ]);
    }

    /**
     * Cake `admin_export`: queue CSV generation for a month range (POST).
     */
    public function export(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!Schema::hasTable('metro_exports')) {
            return back()->with('error', 'Metro exports table is not available.');
        }

        $startRaw = trim((string) $request->input('Export.start', ''));
        $endRaw = trim((string) $request->input('Export.end', ''));

        if ($startRaw === '' || $endRaw === '') {
            return back()->with('error', 'Sorry, please select correct date range');
        }

        $startParsed = Carbon::createFromFormat('d-m-Y', '01-' . $startRaw);
        $endParsed = Carbon::createFromFormat('d-m-Y', '01-' . $endRaw);

        if ($startParsed === false || $endParsed === false) {
            return back()->with('error', 'Sorry, please select correct date range');
        }

        $startDate = $startParsed->copy()->startOfDay();
        $endMonth = $endParsed->copy()->startOfDay();

        $startYmd = $startDate->format('Y-m-d');
        $endMonthEnd = $endMonth->copy()->endOfMonth();
        $endYmd = $endMonthEnd->format('Y-m-d');

        if ($endYmd <= $startYmd) {
            return back()->with('error', 'Sorry, please select correct date range');
        }

        $filename = time() . '_' . $startDate->format('Ymd') . '_' . $endMonthEnd->format('Ymd') . '.csv';
        $now = now()->toDateTimeString();

        $insert = [
            'start' => $startYmd,
            'end' => $endYmd,
            'filename' => $filename,
        ];

        if (Schema::hasColumn('metro_exports', 'status')) {
            $insert['status'] = 0;
        }
        if (Schema::hasColumn('metro_exports', 'offset')) {
            $insert['offset'] = 0;
        }

        MetroExport::create($insert);

        return back()->with('success', 'Your request is saved successfully. Please download file after complete process');
    }

    /**
     * Cake `admin_download`: stream a completed export from legacy webroot.
     */
    public function download(Request $request, $filename = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $safeName = $filename !== null ? basename((string) $filename) : '';
        if ($safeName === '' || str_contains($safeName, '..')) {
            return redirect('/admin/metro_exports/index')->with('error', 'Invalid file.');
        }

        if (!Schema::hasTable('metro_exports')) {
            return redirect('/admin/metro_exports/index')->with('error', 'Metro exports table is not available.');
        }

        $row = MetroExport::where('filename', $safeName)->first();
        if (!$row) {
            return redirect('/admin/metro_exports/index')->with('error', 'File not found.');
        }

        $dir = $this->exportDir();
        $fullPath = $dir . DIRECTORY_SEPARATOR . $safeName;

        if (!is_file($fullPath)) {
            return redirect('/admin/metro_exports/index')->with('error', 'File not found.');
        }

        $realDir = realpath($dir);
        $realFile = realpath($fullPath);
        if ($realDir === false || $realFile === false || !str_starts_with($realFile, $realDir . DIRECTORY_SEPARATOR)) {
            return redirect('/admin/metro_exports/admin/metro_exports/index')->with('error', 'Invalid file.');
        }

        return response()->download($realFile, $safeName);
    }

    protected function resolveLimit(Request $request): int
    {
        $allowed = [25, 50, 100, 200];
        $fromForm = $request->input('Record.limit');
        if ($fromForm !== null && $fromForm !== '') {
            $lim = (int) $fromForm;
            if (in_array($lim, $allowed, true)) {
                session()->put(self::SESSION_LIMIT_KEY, $lim);

                return $lim;
            }
        }
        $sess = (int) session()->get(self::SESSION_LIMIT_KEY, 0);
        if (in_array($sess, $allowed, true)) {
            return $sess;
        }

        return 25;
    }
}
