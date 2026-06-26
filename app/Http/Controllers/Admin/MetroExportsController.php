<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Legacy\MetroExport;

class MetroExportsController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = "Metro Export";
        $sessLimitName = "metro_exports_limit";

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessLimitName => $limit]);
        } else {
            $limit = session($sessLimitName, $this->recordsPerPage);
        }

        $exports = MetroExport::orderBy('id', 'desc')->paginate($limit);
        return view('admin.metro_exports.index', compact('title', 'exports', 'limit'));
    }
    public function export(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $startInput = $request->input('Export.start');
        $endInput = $request->input('Export.end');

        if (!empty($startInput) && !empty($endInput)) {
            try {
                $start = Carbon::createFromFormat('d-m-Y', '01-' . $startInput)->format('Y-m-d');
                $end = Carbon::createFromFormat('d-m-Y', '01-' . $endInput)->endOfMonth()->format('Y-m-d');
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Sorry, please select a valid date format.');
            }

            if ($end > $start) {
                $filename = time() . "_" . date('Ymd', strtotime($start)) . "_" . date('Ymd', strtotime($end)) . ".csv";

                MetroExport::create([
                    'start' => $start,
                    'end' => $end,
                    'filename' => $filename
                ]);

                return redirect()->back()->with('success', 'Your request is saved successfully. Please download file after complete process');
            }
        }

        return redirect()->back()->with('error', 'Sorry, please select correct date range');
    }
    public function download($filename = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $filePath = public_path('files/metroexport/' . $filename);

        if (file_exists($filePath)) {
            return response()->download($filePath);
        }

        return redirect()->back()->with('error', 'File not found!');
    }
}
