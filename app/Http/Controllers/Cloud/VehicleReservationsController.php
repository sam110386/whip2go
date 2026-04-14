<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\VehicleReservationsController as AdminVehicleReservationsController;
use App\Models\Legacy\AdminUserAssociation;
use Illuminate\Http\Request;

class VehicleReservationsController extends AdminVehicleReservationsController
{
    public function index(Request $request)
    {
        return $this->scopedResponse($request, 'index');
    }

    public function all(Request $request)
    {
        return $this->scopedResponse($request, 'all');
    }

    public function singleload(Request $request)
    {
        return parent::singleload($request);
    }

    public function changeSaveStatus(Request $request)
    {
        return parent::changeSaveStatus($request);
    }

    public function markBookingCancel(Request $request)
    {
        return parent::markBookingCancel($request);
    }

    public function markBookingCompleted(Request $request)
    {
        return parent::markBookingCompleted($request);
    }

    public function getuserdetails(Request $request)
    {
        return parent::getuserdetails($request);
    }

    public function updatelist(Request $request)
    {
        return $this->scopedResponse($request, 'updatelist');
    }

    public function updatemvr(Request $request)
    {
        return parent::updatemvr($request);
    }

    public function createBooking(Request $request)
    {
        return parent::createBooking($request);
    }

    public function saveVehicleBooking(Request $request)
    {
        return parent::saveVehicleBooking($request);
    }

    public function changeVehicle(Request $request)
    {
        return parent::changeVehicle($request);
    }

    public function updateReservationVehicle(Request $request)
    {
        return parent::updateReservationVehicle($request);
    }

    public function changeDatetime(Request $request)
    {
        return parent::changeDatetime($request);
    }

    public function updateDatetime(Request $request)
    {
        return parent::updateDatetime($request);
    }

    public function changeStatus(Request $request)
    {
        return parent::changeStatus($request);
    }

    public function loadstatuschecklist(Request $request)
    {
        return parent::loadstatuschecklist($request);
    }

    public function updatechecklist(Request $request)
    {
        return parent::updatechecklist($request);
    }

    public function vehicleReservationLog(Request $request)
    {
        return parent::vehicleReservationLog($request);
    }

    public function getfarecalculations(Request $request)
    {
        return parent::getfarecalculations($request);
    }

    public function loadcancelblock(Request $request)
    {
        return parent::loadcancelblock($request);
    }

    public function loadinsurancepopup(Request $request)
    {
        return parent::loadinsurancepopup($request);
    }

    public function changeinsurancepopup(Request $request)
    {
        return parent::changeinsurancepopup($request);
    }

    public function changeinsurancesave(Request $request)
    {
        return parent::changeinsurancesave($request);
    }

    public function changeinsurancetypepopup(Request $request)
    {
        return parent::changeinsurancetypepopup($request);
    }

    public function saveinsurancepayer(Request $request)
    {
        return parent::saveinsurancepayer($request);
    }

    public function generateAgrement(Request $request)
    {
        return parent::generateAgrement($request);
    }

    public function capturepayment(Request $request)
    {
        return parent::capturepayment($request);
    }

    public function processcapturepayment(Request $request)
    {
        return parent::processcapturepayment($request);
    }

    public function paymentcapturevehiclereservation(Request $request)
    {
        return parent::paymentcapturevehiclereservation($request);
    }

    public function recapturevehiclereservation(Request $request)
    {
        return parent::recapturevehiclereservation($request);
    }

    public function renderlog($filename)
    {
        return parent::renderlog($filename);
    }

    protected function reservationQuery(?array $statuses = null)
    {
        $q = parent::reservationQuery($statuses);
        $admin = $this->getAdminUserid();

        if (!empty($admin['administrator'])) {
            return $q->whereRaw('1=0');
        }

        $parentId = (int)($admin['parent_id'] ?? 0);
        if ($parentId <= 0) {
            return $q->whereRaw('1=0');
        }

        $dealerIds = AdminUserAssociation::query()
            ->where('admin_id', $parentId)
            ->pluck('user_id')
            ->map(static fn ($id) => (int)$id)
            ->filter(static fn ($id) => $id > 0)
            ->values()
            ->all();

        if ($dealerIds === []) {
            return $q->whereRaw('1=0');
        }

        return $q->whereIn('vr.user_id', $dealerIds);
    }

    private function scopedResponse(Request $request, string $method)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return $this->{$method}($request);
    }
}

