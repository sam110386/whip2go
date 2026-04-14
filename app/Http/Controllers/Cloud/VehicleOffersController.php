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

    public function index(Request $request)
    {
        return parent::index($request);
    }

    public function add(Request $request, $offer_id = null)
    {
        return parent::add($request, $offer_id);
    }

    public function userautocomplete(Request $request)
    {
        return parent::userautocomplete($request);
    }

    public function vehicleautocomplete(Request $request)
    {
        return parent::vehicleautocomplete($request);
    }

    public function cancel($id = null)
    {
        return parent::cancel($id);
    }

    public function delete($id = null)
    {
        return parent::delete($id);
    }

    public function view($offer_id = null)
    {
        return parent::view($offer_id);
    }

    public function qualify(Request $request)
    {
        return parent::qualify($request);
    }

    public function qualifyIncome(Request $request)
    {
        return parent::qualifyIncome($request);
    }

    public function getVehicleDynamicFareMatrix(Request $request)
    {
        return parent::getVehicleDynamicFareMatrix($request);
    }

    public function duplicate($offerid)
    {
        return parent::duplicate($offerid);
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

