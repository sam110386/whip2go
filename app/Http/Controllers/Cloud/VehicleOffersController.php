<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\VehicleOffersController as AdminVehicleOffersController;
use App\Models\Legacy\AdminUserAssociation;
use Illuminate\Http\Request;

class VehicleOffersController extends AdminVehicleOffersController
{
    protected function offerBasePath(): string
    {
        return '/cloud/vehicle_offers';
    }

    public function cloud_index(Request $request)
    {
        return $this->admin_index($request);
    }

    public function cloud_add(Request $request, $offer_id = null)
    {
        return $this->admin_add($request, $offer_id);
    }

    public function cloud_userautocomplete(Request $request)
    {
        return $this->admin_userautocomplete($request);
    }

    public function cloud_vehicleautocomplete(Request $request)
    {
        return $this->admin_vehicleautocomplete($request);
    }

    public function cloud_cancel($id = null)
    {
        return $this->admin_cancel($id);
    }

    public function cloud_delete($id = null)
    {
        return $this->admin_delete($id);
    }

    public function cloud_view($offer_id = null)
    {
        return $this->admin_view($offer_id);
    }

    public function cloud_qualify(Request $request)
    {
        return $this->admin_qualify($request);
    }

    public function cloud_qualifyIncome(Request $request)
    {
        return $this->admin_qualifyIncome($request);
    }

    public function cloud_getVehicleDynamicFareMatrix(Request $request)
    {
        return $this->admin_getVehicleDynamicFareMatrix($request);
    }

    public function cloud_duplicate($offerid)
    {
        return $this->admin_duplicate($offerid);
    }

    protected function offerQuery()
    {
        $q = parent::offerQuery();
        $admin = $this->getAdminUserid();
        if (!empty($admin['administrator'])) {
            return $q->whereRaw('1=0');
        }
        $parentId = (int)($admin['parent_id'] ?? 0);
        $dealerIds = AdminUserAssociation::query()
            ->where('admin_id', $parentId)
            ->pluck('user_id')
            ->map(static fn ($id) => (int)$id)
            ->filter(static fn ($id) => $id > 0)
            ->values()
            ->all();

        return $q->whereIn('vo.user_id', $dealerIds === [] ? [0] : $dealerIds);
    }
}

