<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\AdminStaffsController as AdminAdminStaffsController;
use Illuminate\Http\Request;

class AdminStaffsController extends AdminAdminStaffsController
{
    protected function staffBasePath(): string
    {
        return '/cloud/admin_staffs';
    }

    public function cloud_index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::admin_index($request);
    }

    public function cloud_multiplAction(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::admin_multiplAction($request);
    }

    public function cloud_add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::admin_add($request, $id);
    }

    public function cloud_status(Request $request, $id = null, $status = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::admin_status($request, $id, $status);
    }

    public function cloud_delete(Request $request, $id = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::admin_delete($request, $id);
    }

    protected function adminsSearchRedirectUrl(string $keyword, string $searchin, string $show): string
    {
        return '/cloud/admins/index?keyword=' . rawurlencode($keyword) . '&searchin=' . rawurlencode($searchin) . '&showtype=' . rawurlencode($show);
    }
}
