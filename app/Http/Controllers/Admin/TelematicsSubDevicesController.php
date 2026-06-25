<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\TelematicsDevice;
use Illuminate\Http\Request;

class TelematicsSubDevicesController extends LegacyAppController
{
    public function index(Request $request, $subid)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = "Telematics Subscriptions Devices";
        $sessionKey = "telematics_sub_devices_limit";
        $subid = $this->decodeId($subid);

        if (empty($subid)) {
            return redirect('admin/telematics_subscriptions/index');
        }

        $query = TelematicsDevice::where('sub_id', $subid);
        $status_type = $request->input('Search.status_type') ?? $request->query('status_type', '');

        if (!empty($status_type)) {
            if ($status_type === 'inactive') {
                $query->where('status', 0);
            } elseif ($status_type === 'active') {
                $query->where('status', 1);
            }
        }

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionKey => $limit]);
        } else {
            $limit = session($sessionKey, $this->recordsPerPage);
        }

        $request->merge(['Record' => ['limit' => $limit]]);
        $records = $query->orderBy('id', 'DESC')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.telematics.sub_devices.elements.devices', compact('status_type', 'subid', 'records', 'limit'));
        }

        return view('admin.telematics.sub_devices.index', compact('title', 'status_type', 'subid', 'records', 'limit'));
    }
    public function add(Request $request)
    {
        $subid = $this->decodeId($request->input('subid'));
        $deviceid = $this->decodeId($request->input('deviceid'));
        $device = null;

        if (empty($subid)) {
            return redirect('admin/telematics_subscriptions/index');
        }


        if (!empty($deviceid)) {
            $device = TelematicsDevice::find($deviceid);
        }

        return response()->view('admin.telematics.sub_devices.add', compact('subid', 'device'));
    }
    public function save(Request $request)
    {
        $deviceData = $request->input('TelematicsDevice', []);

        if ($request->isMethod('post') && !empty($deviceData)) {
            $id = $deviceData['id'] ?? null;

            TelematicsDevice::updateOrCreate(
                ['id' => $id],
                $deviceData
            );

            return response()->json([
                'status' => true,
                'message' => 'Record saved successfully'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Sorry, something went wrong. Please try again later'
        ], 400);
    }
    public function status($id = null, $status = null)
    {
        $decodedId = $this->decodeId($id);

        if (!empty($decodedId)) {
            $device = TelematicsDevice::find($decodedId);

            if ($device) {
                $device->status = ($status == 1) ? 1 : 0;
                $device->save();
            }
        }

        return redirect()->back()->with('success', 'Record status has been changed.');
    }
    public function remove($id = null)
    {
        $decodedId = $this->decodeId($id);

        if (!empty($decodedId)) {
            TelematicsDevice::destroy($decodedId);
        }

        return redirect()->back()->with('success', 'Record has been deleted successfully.');
    }
}
