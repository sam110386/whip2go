<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\AdminStaffsController as AdminAdminStaffsController;
use Illuminate\Http\Request;

class AdminStaffsController extends AdminAdminStaffsController
{
    protected bool $shouldLoadLegacyModules = true;

    // ─── cloud_index (Cloud Staff List) ──────────────────────────────────────
    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;
        
        // Reusing base admin_index logic
        return $this->admin_index($request);
    }

    // ─── cloud_add (Add or Edit Cloud Staff) ─────────────────────────────────
    public function cloud_add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;
        
        // Reusing base admin_add logic
        return $this->admin_add($request, $id);
    }

    // ─── cloud_status ────────────────────────────────────────────────────────
    public function cloud_status($id, $status)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;
        
        return $this->admin_status($id, $status);
    }

    // ─── cloud_delete ────────────────────────────────────────────────────────
    public function cloud_delete($id)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;
        
        return $this->admin_delete($id);
    }

    public function cloud_multiplAction(Request $request)
    {
        if ($redirect = $this->ensureCloudSession()) return $redirect;

        $status = $request->input('User.status');
        $selected = $request->input('select', []);
        foreach ($selected as $id => $enabled) {
            if ($enabled) {
                \App\Models\Legacy\User::where('id', (int) $id)->update(['status' => $status]);
            }
        }
        return redirect()->back()->with('success', 'Staff users updated successfully.');
    }
}
