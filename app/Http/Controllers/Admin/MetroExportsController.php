<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetroExportsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $sessionLimitKey  = 'MetroExports_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        $exports = DB::table('metro_exports')
            ->orderBy('id', 'DESC')
            ->paginate($limit)
            ->withQueryString();

        return view('admin.metro_exports.index', [
            'title_for_layout' => 'Metro Export',
            'exports'          => $exports,
        ]);
    }

    public function admin_export(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $startRaw = $request->input('Export.start', '');
        $endRaw   = $request->input('Export.end', '');

        $start = date('Y-m-d', strtotime('01-' . strip_tags($startRaw)));
        $end   = date('Y-m-t', strtotime('01-' . strip_tags($endRaw)));

        if (!empty($startRaw) && !empty($endRaw) && $end > $start) {
            $filename = time() . '_' . date('Ymd', strtotime($start)) . '_' . date('Ymd', strtotime($end)) . '.csv';

            DB::table('metro_exports')->insert([
                'start'      => $start,
                'end'        => $end,
                'filename'   => $filename,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Your request is saved successfully. Please download file after complete process');
        }

        return redirect()->back()->with('error', 'Sorry, please select correct date range');
    }

    public function admin_download(Request $request, string $filename)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $filepath = public_path('files/metroexport/' . $filename);

        if (!file_exists($filepath)) {
            return redirect()->back()->with('error', 'Sorry, the requested file does not exist.');
        }

        return response()->download($filepath, $filename);
    }
}
