<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntercomCarouselsController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if ($request->isMethod('post') && !empty($request->input('Carousel'))) {
            DB::statement('TRUNCATE intercom_carousels');

            foreach ($request->input('Carousel') as $data) {
                if (empty($data['screen'])) {
                    continue;
                }
                DB::table('intercom_carousels')->insert([
                    'screen' => $data['screen'],
                    'intercom' => $data['intercom'] ?? '',
                ]);
            }

            return redirect()->back()->with('success', 'Records updated successfully.');
        }

        $obj = DB::table('intercom_carousels')
            ->pluck('intercom', 'screen')
            ->toArray();

        return view('admin.intercom_carousels.index', compact('obj'));
    }
}
