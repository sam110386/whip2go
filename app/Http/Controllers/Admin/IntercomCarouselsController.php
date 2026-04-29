<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\IntercomCarousel;
use Illuminate\Http\Request;

class IntercomCarouselsController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if ($request->isMethod('post') && !empty($request->input('Carousel'))) {
            IntercomCarousel::truncate();

            foreach ($request->input('Carousel') as $data) {
                if (empty($data['screen'])) {
                    continue;
                }
                IntercomCarousel::create([
                    'screen' => $data['screen'],
                    'intercom' => $data['intercom'] ?? '',
                ]);
            }

            return redirect()->back()->with('success', 'Records updated successfully.');
        }

        $obj = IntercomCarousel::pluck('intercom', 'screen')->toArray();

        return view('admin.intercom_carousels.index', compact('obj'));
    }
}
