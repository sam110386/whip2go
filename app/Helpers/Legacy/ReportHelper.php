<?php

namespace App\Helpers\Legacy;

use App\Services\Legacy\Common as CommonService;
use App\Services\Legacy\Report\PortfolioService;
use App\Models\Legacy\OrderExtlog;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\DepositRule;

class ReportHelper
{
    public static function getExtCount($orderId)
    {
        return OrderExtlog::where('cs_order_id', $orderId)
            ->where('admin_count', 0)
            ->count();
    }

    public static function getExtParentWithSiblingCount($orderId)
    {
        $orderIds = CsOrder::where('id', $orderId)
            ->orWhere('parent_id', $orderId)
            ->pluck('id');

        return OrderExtlog::whereIn('cs_order_id', $orderIds)
            ->where('admin_count', 0)
            ->count();
    }

    public static function getVehicleDepriciation($vehicle)
    {
        $commonService = new CommonService();
        $createdAt = data_get($vehicle, 'created');
        $msrp = data_get($vehicle, 'msrp');
        $vehicleId = data_get($vehicle, 'id');

        if (empty($createdAt)) {
            return 0;
        }

        $months = $commonService->getDifference(date('Y-m-d H:i:s'), $vehicle->created_at, 5);
        $months = $months > 1 ? $months - 1 : 0;

        if (!$months) {
            return 0;
        }

        $depositRule = DepositRule::where('vehicle_id', $vehicleId)
            ->first(['depreciation_rate']);

        if ($depositRule && !is_null($depositRule->depreciation_rate)) {
            return sprintf('%0.2f', (($msrp * $depositRule->depreciation_rate / 100) * $months));
        }

        return 0;
    }

    public static function getVehiclePortfolio($vehicleid)
    {
        $Portfolio = new PortfolioService();
        return $Portfolio->getVehiclePortfolio($vehicleid);
    }

    public static function getVehicleExpenses($vehicleid, $date_from, $date_to)
    {
        $Portfolio = new PortfolioService();
        return $Portfolio->getVehicleExpenses($vehicleid, $date_from, $date_to);
    }

    public static function getVehicleDepriciationReport($vehicleid)
    {
        $Portfolio = new PortfolioService();
        return $Portfolio->getVehicleDepriciationReport($vehicleid);
    }

    public static function getVehicleFixedProgramCost($ownerid)
    {
        $Portfolio = new PortfolioService();
        return $Portfolio->getVehicleFixedProgramCost($ownerid);
    }

}