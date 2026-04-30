<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TelematicsSubDevicesController extends LegacyAppController
{
    protected int $recordsPerPage = 25;

    public function index(Request $request, $subid)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decodedSubid = $this->decodeId($subid);
        if (empty($decodedSubid)) {
            return redirect('/admin/telematics_subscriptions/index');
        }

        $status_type = '';
        $conditions = [
            ['TelematicsDevice.sub_id', '=', $decodedSubid],
        ];

        if ($request->has('Search') || $request->filled('status_type')) {
            $status_type = $request->input('Search.status_type', $request->input('status_type', ''));
            if (!empty($status_type)) {
                $statusMap = ['inactive' => 0, 'active' => 1];
                if (isset($statusMap[$status_type])) {
                    $conditions[] = ['TelematicsDevice.status', '=', $statusMap[$status_type]];
                }
            }
        }

        $sessLimitKey = 'telematics_devices_limit';
        $limit = $request->input('Record.limit')
            ?: session($sessLimitKey, $this->recordsPerPage);
        session([$sessLimitKey => $limit]);

        $records = DB::table('telematics_devices as TelematicsDevice')
            ->where($conditions)
            ->select('TelematicsDevice.*')
            ->orderByDesc('TelematicsDevice.id')
            ->paginate($limit)
            ->withQueryString();

        $viewData = [
            'records' => $records,
            'status_type' => $status_type,
            'subid' => $decodedSubid,
            'title_for_layout' => 'Telematics Subscriptions Devices',
        ];

        if ($request->ajax()) {
            return response()->view('admin.telematics.sub_devices._table', $viewData);
        }

        return view('admin.telematics.sub_devices.index', $viewData);
    }

    public function add(Request $request)
    {
        $subid = $this->decodeId($request->input('subid'));
        $deviceid = $this->decodeId($request->input('deviceid'));
        $device = null;

        if (empty($subid)) {
            return redirect('/admin/telematics_subscriptions/index');
        }

        if (!empty($deviceid)) {
            $device = DB::table('telematics_devices')->where('id', $deviceid)->first();
        }

        return response()->view('admin.telematics.sub_devices._add', [
            'subid' => $subid,
            'device' => $device,
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $return = ['status' => false, 'message' => 'Sorry, something went wrong. Please try again later'];

        $data = $request->input('TelematicsDevice', $request->all());

        if (!empty($data)) {
            if (!empty($data['id'])) {
                DB::table('telematics_devices')
                    ->where('id', $data['id'])
                    ->update([
                        'sub_id' => $data['sub_id'] ?? null,
                        'device_name' => $data['device_name'] ?? '',
                        'gps_serialno' => $data['gps_serialno'] ?? '',
                        'updated' => now(),
                    ]);
            } else {
                DB::table('telematics_devices')->insert([
                    'sub_id' => $data['sub_id'] ?? null,
                    'device_name' => $data['device_name'] ?? '',
                    'gps_serialno' => $data['gps_serialno'] ?? '',
                    'status' => 1,
                    'created' => now(),
                    'updated' => now(),
                ]);
            }
            $return = ['status' => true, 'message' => 'Record saved successfully'];
        }

        return response()->json($return);
    }

    public function status($id = null, $status = null): RedirectResponse
    {
        $decodedId = $this->decodeId($id);
        if (!empty($decodedId)) {
            DB::table('telematics_devices')
                ->where('id', $decodedId)
                ->update(['status' => ($status == 1) ? 1 : 0]);
        }

        session()->flash('success', 'Record status has been changed.');
        return redirect()->back();
    }

    public function remove($id = null): RedirectResponse
    {
        $decodedId = $this->decodeId($id);
        if (!empty($decodedId)) {
            DB::table('telematics_devices')->where('id', $decodedId)->delete();
        }

        session()->flash('success', 'Record has been deleted successfully.');
        return redirect()->back();
    }
}
