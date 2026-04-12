<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\VehicleReservationsController as AdminVehicleReservationsController;
use App\Models\Legacy\AdminUserAssociation;
use Illuminate\Http\Request;

class VehicleReservationsController extends AdminVehicleReservationsController
{
    public function cloud_index(Request $request)
    {
        return $this->scopedResponse($request, 'admin_index');
    }

    public function cloud_all(Request $request)
    {
        return $this->scopedResponse($request, 'admin_all');
    }

    public function cloud_singleload(Request $request)
    {
        return $this->admin_singleload($request);
    }

    public function cloud_changeSaveStatus(Request $request)
    {
        return $this->admin_changeSaveStatus($request);
    }

    public function cloud_markBookingCancel(Request $request)
    {
        return $this->admin_markBookingCancel($request);
    }

    public function cloud_markBookingCompleted(Request $request)
    {
        return $this->admin_markBookingCompleted($request);
    }

    public function cloud_getuserdetails(Request $request)
    {
        return $this->admin_getuserdetails($request);
    }

    public function cloud_updatelist(Request $request)
    {
        return $this->scopedResponse($request, 'admin_updatelist');
    }

    public function cloud_updatemvr(Request $request)
    {
        return $this->admin_updatemvr($request);
    }

    public function cloud_createBooking(Request $request)
    {
        return $this->admin_createBooking($request);
    }

    public function cloud_saveVehicleBooking(Request $request)
    {
        return $this->admin_saveVehicleBooking($request);
    }

    public function cloud_changeVehicle(Request $request)
    {
        return $this->admin_changeVehicle($request);
    }

    public function cloud_updateReservationVehicle(Request $request)
    {
        return $this->admin_updateReservationVehicle($request);
    }

    public function cloud_changeDatetime(Request $request)
    {
        return $this->admin_changeDatetime($request);
    }

    public function cloud_updateDatetime(Request $request)
    {
        return $this->admin_updateDatetime($request);
    }

    public function cloud_changeStatus(Request $request)
    {
        return $this->admin_changeStatus($request);
    }

    public function cloud_loadstatuschecklist(Request $request)
    {
        return $this->admin_loadstatuschecklist($request);
    }

    public function cloud_updatechecklist(Request $request)
    {
        return $this->admin_updatechecklist($request);
    }

    public function cloud_vehicleReservationLog(Request $request)
    {
        return $this->admin_vehicleReservationLog($request);
    }

    public function cloud_getfarecalculations(Request $request)
    {
        return $this->admin_getfarecalculations($request);
    }

    public function cloud_loadcancelblock(Request $request)
    {
        return $this->admin_loadcancelblock($request);
    }

    public function cloud_loadinsurancepopup(Request $request)
    {
        return $this->admin_loadinsurancepopup($request);
    }

    public function cloud_changeinsurancepopup(Request $request)
    {
        return $this->admin_changeinsurancepopup($request);
    }

    public function cloud_changeinsurancesave(Request $request)
    {
        return $this->admin_changeinsurancesave($request);
    }

    public function cloud_changeinsurancetypepopup(Request $request)
    {
        return $this->admin_changeinsurancetypepopup($request);
    }

    public function cloud_saveinsurancepayer(Request $request)
    {
        return $this->admin_saveinsurancepayer($request);
    }

    public function cloud_generateAgrement(Request $request)
    {
        return $this->admin_generateAgrement($request);
    }

    public function cloud_capturepayment(Request $request)
    {
        return $this->admin_capturepayment($request);
    }

    public function cloud_processcapturepayment(Request $request)
    {
        return $this->admin_processcapturepayment($request);
    }

    public function cloud_paymentcapturevehiclereservation(Request $request)
    {
        return $this->admin_paymentcapturevehiclereservation($request);
    }

    public function cloud_recapturevehiclereservation(Request $request)
    {
        return $this->admin_recapturevehiclereservation($request);
    }

    public function cloud_renderlog($filename)
    {
        return $this->admin_renderlog($filename);
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

