<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\AdminUserAssociation;
use App\Models\Legacy\CsLead;
use App\Models\Legacy\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Exception;

trait LeadsTrait
{
    use LeadLib;
    protected function _indexCommon(Request $request, $prefix = 'admin')
    {
        $title = 'My Leads';
        $adminUser = $this->getAdminUserId();
        $query = CsLead::query();

        if (!$adminUser['administrator']) {
            $query->where('cs_leads.admin_id', $adminUser['parent_id']);
        }

        [
            'keyword' => $keyword,
            'fieldname' => $fieldname,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'status_type' => $status_type,
            'type' => $type
        ] = $this->_buildSearchConditions($query, $request);


        if ($adminUser['administrator']) {
            $query->leftJoin('users as owner', 'owner.id', '=', 'cs_leads.admin_id');
        } else {
            $query->leftJoin('users as owner', 'owner.id', '=', 'cs_leads.sub_admin_id');
        }

        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';
        $allowedSort = [
            'id' => 'cs_leads.id',
            'status' => 'cs_leads.status',
            'phone' => 'cs_leads.phone',
            'type' => 'cs_leads.type',
            'created' => 'cs_leads.created',
        ];

        $orderBy = $allowedSort[$sort] ?? 'cs_leads.id';

        $limit = $this->_getPerPageLimit($request);
        $request->merge(['Record' => ['limit' => $limit]]);

        $leads = $query->select('cs_leads.*', 'owner.first_name as owner_first_name', 'owner.last_name as owner_last_name')
            ->orderBy($orderBy, $direction)
            ->paginate($limit);

        return view("{$prefix}.leads.index", compact(
            'leads',
            'limit',
            'title',
            'keyword',
            'fieldname',
            'date_from',
            'date_to',
            'status_type',
            'type'
        ));
    }
    protected function _buildSearchConditions(Builder $query, Request $request): array
    {
        $value = '';
        $fieldname = '';
        $status_type = $this->_getSearchValue($request, 'status_type');
        $date_from = $this->_getSearchValue($request, 'date_from');
        $date_to = $this->_getSearchValue($request, 'date_to');
        $type = $this->_getSearchValue($request, 'type');

        if ($request->has('Search') || !empty($request->query())) {

            if (!empty($date_from) && empty($date_to)) {
                $date_to = now()->format('Y-m-d');
            }

            if (!empty($date_from)) {
                $formattedFrom = Carbon::parse($date_from)->startOfDay();
                $query->where('cs_leads.created', '>=', $formattedFrom);
            }

            if (!empty($date_to)) {
                $formattedTo = Carbon::parse($date_to)->endOfDay();
                $query->where('cs_leads.created', '<=', $formattedTo);
            }

            if ($status_type !== null && $status_type !== '') {
                $query->where('cs_leads.status', $status_type);
            }

            if ($type === 'dealer') {
                $query->where('cs_leads.type', 2);
            } elseif ($type === 'driver') {
                $query->where('cs_leads.type', 1);
            }
        }

        return [
            'keyword' => $value,
            'fieldname' => $fieldname,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'status_type' => $status_type,
            'type' => $type
        ];
    }
    protected function _getSearchValue(Request $request, string $key, $default = '')
    {
        return $request->input("Search.{$key}", $request->query($key, $default));
    }
    protected function _getPerPageLimit(Request $request): int
    {
        $routeAction = $request->route() ? $request->route()->getActionMethod() : 'leads';
        $sessionLimitName = "{$routeAction}_limit";

        if ($request->filled('Record.limit')) {
            $limit = (int) $request->input('Record.limit');
            $request->session()->put($sessionLimitName, $limit);
        } else {
            $limit = $request->session()->get($sessionLimitName, $this->recordsPerPage);
        }

        return $limit;
    }
    protected function _addCommon(Request $request, $id = null, $prefix = 'admin')
    {
        $id = $this->decodeId($id);
        $title = !empty($id) ? 'Update Lead' : 'Add New Lead';
        $adminUser = $this->getAdminUserId();

        if ($adminUser['administrator']) {
            return redirect("{$prefix}/leads/index")->with('error', 'Sorry, you are not an authorized user for this action');
        }

        if ($request->isMethod('post')) {
            return $this->_handleLeadSave($request, $adminUser, $id, $prefix = 'admin');
        }

        $lead = null;

        if (!empty($id)) {
            $lead = CsLead::where('id', $id)
                ->where('admin_id', $adminUser['parent_id'])
                ->whereIn('status', [0, 1])
                ->first();

            if (!$lead) {
                return redirect("{$prefix}/leads/index")->with('error', 'Sorry, you are not an authorized user for this action');
            }
        }

        return view('admin.leads.add', compact('lead', 'title'));
    }
    protected function _handleLeadSave(Request $request, $adminUser, $id = null, $prefix = 'admin')
    {
        $dataToSave = $request->input('Lead', $request->all());
        $dataToSave['admin_id'] = $adminUser['parent_id'];
        $dataToSave['sub_admin_id'] = $adminUser['admin_id'];
        $dataToSave['phone'] = substr(preg_replace('/[^0-9]/', '', $dataToSave['phone'] ?? ''), -10);
        $rules = [
            'Lead.phone' => 'required|unique:cs_leads,phone,' . $id,
            'Lead.email' => 'required|email',
        ];

        if ((int) ($dataToSave['type'] ?? 0) === 1) {
            $rules['Lead.first_name'] = 'required';
            $rules['Lead.last_name'] = 'required';
        } else {
            $rules['Lead.dealer_name'] = 'required';
        }

        $messages = [
            'Lead.phone.required' => 'Please enter phone#',
            'Lead.phone.unique' => 'Phone # already exists',
            'Lead.dealer_name.required' => 'Please enter your dealer name',
            'Lead.first_name.required' => 'Please enter your first name',
            'Lead.last_name.required' => 'Please enter last name',
            'Lead.email.required' => 'Please enter your email.',
            'Lead.email.email' => 'Please enter valid email address',
        ];

        $request->validate($rules, $messages);

        try {
            $phoneExists = User::where('username', $dataToSave['phone'])->first();

            if ($phoneExists) {
                $dataToSave['user_id'] = $phoneExists->id;
                $dataToSave['status'] = 1;

                try {
                    AdminUserAssociation::insertOrIgnore([
                        'user_id' => $phoneExists->id,
                        'admin_id' => $adminUser['parent_id']
                    ]);
                } catch (Exception $e) {
                    // Ignore duplicates gracefully matching original catch layout
                }
            }

            $this->_pushToIntercom($dataToSave);

            $lead = !empty($id) ? CsLead::findOrFail($id) : new CsLead();
            $lead->fill($dataToSave);
            $lead->save();

            $message = empty($id) ? 'Lead has been added successfully.' : 'Lead has been updated successfully.';

            return redirect("{$prefix}/leads/index")->with('success', $message);

        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
}