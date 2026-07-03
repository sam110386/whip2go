<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\LeadsTrait;
use App\Models\Legacy\CsLead;
use Illuminate\Http\Request;

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

        return $this->_refreshLeadCommon($request);
    }
    public function associatelead(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->_associateLeadCommon($request);
    }
}
