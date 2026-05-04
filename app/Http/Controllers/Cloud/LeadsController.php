<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\LeadsController as AdminLeadsController;
use Illuminate\Http\Request;

/**
 * Cloud leads mirror the admin leads controller with cloud session checks
 * and cloud-specific view/redirect paths.
 */
class LeadsController extends AdminLeadsController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $adminUser = $this->getAdminUserid();
        $conditions = [];

        if (!$adminUser['administrator']) {
            $conditions['cs_leads.admin_id'] = $adminUser['parent_id'];
        }

        $query = \DB::table('cs_leads');
        foreach ($conditions as $col => $val) {
            $query->where($col, $val);
        }

        $filters = $this->applySearchFiltersCloud($request, $query);
        $limit = $this->getPerPageLimitCloud($request);

        if ($adminUser['administrator']) {
            $query->leftJoin('users as LeadOwner', 'LeadOwner.id', '=', 'cs_leads.admin_id');
        } else {
            $query->leftJoin('users as LeadOwner', 'LeadOwner.id', '=', 'cs_leads.sub_admin_id');
        }

        $leads = $query->select('cs_leads.*', 'LeadOwner.first_name as owner_first_name', 'LeadOwner.last_name as owner_last_name')
            ->orderByDesc('cs_leads.id')
            ->paginate($limit)
            ->appends($request->query());

        return view('cloud.leads.index', array_merge(compact('leads', 'limit'), $filters, ['prefix' => 'cloud']));
    }

    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $decodedId = $id ? $this->decodeId($id) : null;
        $adminUser = $this->getAdminUserid();

        if ($adminUser['administrator']) {
            return redirect('/cloud/leads/index')
                ->with('error', 'Sorry, you are not authorized user for this action');
        }

        $listTitle = !empty($decodedId) ? 'Update Lead' : 'Add New Lead';

        if ($request->isMethod('post')) {
            return $this->handleLeadSave($request, $adminUser, 'cloud');
        }

        $data = [];
        if (!empty($decodedId)) {
            $data = \DB::table('cs_leads')
                ->where('id', $decodedId)
                ->where('admin_id', $adminUser['parent_id'])
                ->whereIn('status', [0, 1])
                ->first();

            if (empty($data)) {
                return redirect('/cloud/leads/index')
                    ->with('error', 'Sorry, you are not authorized user for this action');
            }
            $data = (array) $data;
        }

        return view('cloud.leads.add', compact('data', 'listTitle'));
    }

    public function delete($id = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $decodedId = $this->decodeId($id);
        \DB::table('cs_leads')->where('id', $decodedId)->delete();

        return redirect('/cloud/leads/index')
            ->with('success', 'Record has been deleted, succesfully');
    }

    public function refreshlead(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return $this->refreshLeadCommon($request);
    }

    public function associatelead(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return $this->associateLeadCommon($request);
    }

    private function applySearchFiltersCloud(Request $request, $query): array
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
            $query->where('cs_leads.created', '>=', \Carbon\Carbon::parse($dateFrom)->startOfDay());
        }
        if (!empty($dateTo)) {
            $query->where('cs_leads.created', '<=', \Carbon\Carbon::parse($dateTo)->endOfDay());
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

    private function getPerPageLimitCloud(Request $request): int
    {
        $sessName = 'leads_limit';
        $limit = (int) ($request->input('Record.limit') ?: session($sessName, 20));
        session([$sessName => $limit]);
        return $limit;
    }
}
