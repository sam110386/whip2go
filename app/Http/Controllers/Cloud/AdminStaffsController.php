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

    public function index(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::index($request);
    }

    public function multiplAction(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::multiplAction($request);
    }

    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::add($request, $id);
    }

    public function status(Request $request, $id = null, $status = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::status($request, $id, $status);
    }

    public function delete(Request $request, $id = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::delete($request, $id);
    }

    protected function adminsSearchRedirectUrl(string $keyword, string $searchin, string $show): string
    {
        return '/cloud/admins/index?keyword=' . rawurlencode($keyword) . '&searchin=' . rawurlencode($searchin) . '&showtype=' . rawurlencode($show);
    }
}
