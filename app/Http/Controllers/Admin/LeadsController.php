<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\LeadService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadsController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $adminUser = $this->getAdminUserid();
        $conditions = [];

        if (!$adminUser['administrator']) {
            $conditions['cs_leads.admin_id'] = $adminUser['parent_id'];
        }

        $query = DB::table('cs_leads');

        foreach ($conditions as $col => $val) {
            $query->where($col, $val);
        }

        $filters = $this->applySearchFilters($request, $query);
        $limit = $this->getPerPageLimit($request);

        if ($adminUser['administrator']) {
            $query->leftJoin('users as LeadOwner', 'LeadOwner.id', '=', 'cs_leads.admin_id');
        } else {
            $query->leftJoin('users as LeadOwner', 'LeadOwner.id', '=', 'cs_leads.sub_admin_id');
        }

        $leads = $query->select('cs_leads.*', 'LeadOwner.first_name as owner_first_name', 'LeadOwner.last_name as owner_last_name')
            ->orderByDesc('cs_leads.id')
            ->paginate($limit)
            ->appends($request->query());

        return view('admin.leads.index', array_merge(compact('leads', 'limit'), $filters, ['prefix' => 'admin']));
    }

    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decodedId = $id ? $this->decodeId($id) : null;
        $adminUser = $this->getAdminUserid();

        if ($adminUser['administrator']) {
            return redirect('/admin/leads/index')
                ->with('error', 'Sorry, you are not authorized user for this action');
        }

        $listTitle = !empty($decodedId) ? 'Update Lead' : 'Add New Lead';

        if ($request->isMethod('post')) {
            return $this->handleLeadSave($request, $adminUser, 'admin');
        }

        $data = [];
        if (!empty($decodedId)) {
            $data = DB::table('cs_leads')
                ->where('id', $decodedId)
                ->where('admin_id', $adminUser['parent_id'])
                ->whereIn('status', [0, 1])
                ->first();

            if (empty($data)) {
                return redirect('/admin/leads/index')
                    ->with('error', 'Sorry, you are not authorized user for this action');
            }
            $data = (array) $data;
        }

        return view('admin.leads.add', compact('data', 'listTitle'));
    }

    public function delete($id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decodedId = $this->decodeId($id);
        DB::table('cs_leads')->where('id', $decodedId)->delete();

        return redirect('/admin/leads/index')
            ->with('success', 'Record has been deleted, succesfully');
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

    protected function handleLeadSave(Request $request, array $adminUser, string $prefix)
    {
        $dataToSave = $request->input('Lead', []);
        $dataToSave['admin_id'] = $adminUser['parent_id'];
        $dataToSave['sub_admin_id'] = $adminUser['admin_id'];
        $dataToSave['phone'] = substr(preg_replace('/[^0-9]/', '', $dataToSave['phone'] ?? ''), -10);

        $rules = [
            'phone' => 'required',
            'email' => 'nullable|email',
        ];

        if (((int) ($dataToSave['type'] ?? 1)) === 1) {
            $rules['first_name'] = 'required';
            $rules['last_name'] = 'required';
        } else {
            $rules['dealer_name'] = 'required';
        }

        $validator = validator($dataToSave, $rules);
        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }

        if (empty($dataToSave['id'])) {
            $phoneExists = DB::table('cs_leads')->where('phone', $dataToSave['phone'])->exists();
            if ($phoneExists) {
                return back()->withInput()->with('error', 'Phone # already exists');
            }
        }

        try {
            $phoneUser = DB::table('users')->where('username', $dataToSave['phone'])->first();
            if (!empty($phoneUser)) {
                $dataToSave['user_id'] = $phoneUser->id;
                $dataToSave['status'] = 1;
                try {
                    DB::table('admin_user_associations')->insert([
                        'user_id' => $phoneUser->id,
                        'admin_id' => $adminUser['parent_id'],
                    ]);
                } catch (\Exception $e) {
                    // ignore duplicate
                }
            }

            (new LeadService())->pushToIntercom($dataToSave);

            $existingId = $dataToSave['id'] ?? null;
            unset($dataToSave['id']);

            if ($existingId) {
                DB::table('cs_leads')->where('id', $existingId)->update($dataToSave);
                return redirect("/{$prefix}/leads/index")->with('success', 'Lead has been updated successfully.');
            } else {
                DB::table('cs_leads')->insert($dataToSave);
                return redirect("/{$prefix}/leads/index")->with('success', 'Lead has been added successfully.');
            }
        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
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

    private function applySearchFilters(Request $request, $query): array
    {
        $dateFrom = $request->input('Search.date_from', $request->query('date_from', ''));
        $dateTo = $request->input('Search.date_to', $request->query('date_to', ''));
        $statusType = $request->input('Search.status_type', $request->query('status_type', ''));
        $type = $request->input('Search.type', $request->query('type', ''));
        $keyword = $request->input('Search.keyword', $request->query('keyword', ''));

        if (!empty($dateFrom) && empty($dateTo)) {
            $dateTo = date('Y-m-d');
        }
        if (!empty($dateFrom)) {
            $query->where('cs_leads.created', '>=', Carbon::parse($dateFrom)->startOfDay());
        }
        if (!empty($dateTo)) {
            $query->where('cs_leads.created', '<=', Carbon::parse($dateTo)->endOfDay());
        }
        if ($statusType !== '') {
            $query->where('cs_leads.status', $statusType);
        }
        if ($type === 'dealer') {
            $query->where('cs_leads.type', 2);
        }
        if ($type === 'driver') {
            $query->where('cs_leads.type', 1);
        }

        return compact('keyword', 'dateFrom', 'dateTo', 'statusType', 'type');
    }

    private function getPerPageLimit(Request $request): int
    {
        $sessName = 'leads_limit';
        $limit = (int) ($request->input('Record.limit') ?: session($sessName, 20));
        session([$sessName => $limit]);
        return $limit;
    }
}
