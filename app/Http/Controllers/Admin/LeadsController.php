<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\LeadsTrait;
use App\Models\Legacy\CsLead;
use App\Services\Legacy\LeadService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadsController extends LegacyAppController
{
    use LeadsTrait;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->_indexCommon($request, 'admin');
    }
    public function delete($id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decodedId = $this->decodeId($id);
        CsLead::where('id', $decodedId)->delete();
        return redirect('/admin/leads/index')->with('success', 'Record has been deleted, succesfully');
    }
    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->_addCommon($request, $id, 'admin');
    }



    public function refreshlead(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->refreshLeadCommon($request);
    }

    public function associatelead(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->associateLeadCommon($request);
    }

    protected function refreshLeadCommon(Request $request)
    {
        $error = true;
        $user = [];
        $message = 'Sorry, something went wrong.';
        $leadid = $this->decodeId($request->input('leadid'));
        $adminUser = $this->getAdminUserid();

        $query = DB::table('cs_leads')->where('id', $leadid);
        if (!$adminUser['administrator']) {
            $query->where('admin_id', $adminUser['parent_id']);
        }
        $lead = $query->first();

        if (empty($lead)) {
            $message = 'Sorry, lead record not found.';
            return view('admin.leads._refreshlead', compact('leadid', 'error', 'message', 'user', 'lead'))
                ->with('intecomContact', [])
                ->with('VehicleReservation', []);
        }

        $phone = substr(preg_replace('/[^0-9]/', '', $lead->phone), -10);
        $user = DB::table('users')
            ->where('username', $phone)
            ->orWhere('contact_number', 'LIKE', '%' . $phone)
            ->first();

        if (empty($user)) {
            $message = 'Sorry, no registered user found with respective phone#.';
        } else {
            $error = false;
            DB::table('cs_leads')->where('id', $lead->id)->update([
                'status' => 1,
                'user_id' => $user->id,
            ]);
            $lead->status = 1;
            $lead->user_id = $user->id;
        }

        $intecomContact = [];
        if (!empty($lead->intercom_id)) {
            $intecomContact = (new LeadService())->pullIntercomContact($lead->intercom_id);
        }

        $VehicleReservation = null;
        if (!empty($lead->user_id)) {
            $VehicleReservation = DB::table('vehicle_reservations')
                ->where('renter_id', $lead->user_id)
                ->orderByDesc('id')
                ->first();
        }

        return view('admin.leads._refreshlead', compact('lead', 'error', 'message', 'user', 'intecomContact', 'VehicleReservation'));
    }

    protected function associateLeadCommon(Request $request)
    {
        $return = ['status' => false, 'message' => 'Sorry, something went wrong.'];

        $leadid = $this->decodeId($request->input('leadid'));
        $userid = $this->decodeId($request->input('userid'));
        $adminUser = $this->getAdminUserid();

        $query = DB::table('cs_leads')->where('id', $leadid)->where('status', 0);
        if (!$adminUser['administrator']) {
            $query->where('admin_id', $adminUser['parent_id']);
        }
        $lead = $query->first();

        if (!empty($lead)) {
            if ((int) $lead->type === 2) {
                try {
                    DB::table('admin_user_associations')->insert([
                        'user_id' => $userid,
                        'admin_id' => $adminUser['parent_id'],
                    ]);
                    $return['message'] = 'Dealer associateded with your account successfully.';
                } catch (\Exception $e) {
                    $return['message'] = 'Dealer already associated with your account.';
                }
                $return['status'] = true;
                DB::table('cs_leads')->where('id', $leadid)->update(['status' => 1]);
            } else {
                $return['message'] = 'Sorry, lead record is not created as Dealer.';
            }
        } else {
            $return['message'] = 'Sorry, lead record not found or already approved.';
        }

        return response()->json($return);
    }
}
