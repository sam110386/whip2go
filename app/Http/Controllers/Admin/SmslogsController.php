<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Legacy\CsTwilioLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SmslogsController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('Record.limit') ?: Session::get('sms_logs_limit', 25);
        if ($request->has('Record.limit')) {
            Session::put('sms_logs_limit', $limit);
        }

        $query = CsTwilioLog::query();

        if ($request->has('Search')) {
            $search = $request->input('Search');
            if (!empty($search['keyword'])) {
                $query->where('renter_phone', 'LIKE', '%' . $search['keyword'] . '%');
            }
            if (!empty($search['status_type'])) {
                $query->where('type', $search['status_type']);
            }
            if (!empty($search['date_from'])) {
                $query->where('created', '>=', $search['date_from']);
            }
            if (!empty($search['date_to'])) {
                $query->where('created', '<=', $search['date_to']);
            }
        }

        $smslogs = $query->orderBy('id', 'DESC')->paginate($limit);

        return view('admin.smslogs.admin_index', compact('smslogs'));
    }

    public function details(Request $request, $id)
    {
        $id = base64_decode($id);
        $smslog = CsTwilioLog::find($id);
        
        return view('admin.smslogs.admin_details', compact('smslog'));
    }

    public function delete(Request $request, $id)
    {
        $id = base64_decode($id);
        CsTwilioLog::where('id', $id)->delete();
        
        return response()->json(['status' => 'success', 'msg' => 'Record deleted successfully', 'recordid' => $id]);
    }

    public function admin_index(Request $request)
    {
        return $this->index($request);
    }

    public function admin_details(Request $request, $id)
    {
        return $this->details($request, $id);
    }

    public function admin_delete(Request $request, $id)
    {
        return $this->delete($request, $id);
    }
}
