<?php

namespace App\Console\Commands;

use App\Services\Legacy\InspektService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InspektProcessCommand extends Command
{
    protected $signature = 'inspekt:process';
    protected $description = 'Process Inspekt vehicle scan cron tasks';

    public function handle(): int
    {
        $lockFile = storage_path('app/InspektShellCron.lock');
        $fp = fopen($lockFile, 'w+');
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            $this->process();
            flock($fp, LOCK_UN);
        } else {
            $this->info('Process already running.');
        }
        fclose($fp);
        return 0;
    }

    private function process(): void
    {
        $inspectionSetting = DB::table('inspection_settings')->where('id', 1)->first();
        if (empty($inspectionSetting) || $inspectionSetting->status == 0) {
            return;
        }

        $execute = false;
        if ($inspectionSetting->schedule == 1) $execute = true;

        $today = (int) date('d');
        if ($inspectionSetting->schedule == 2 && ($today % 7 == 0)) $execute = true;
        if ($inspectionSetting->schedule == 3 && ($today % 14 == 0)) $execute = true;
        if ($inspectionSetting->schedule == 4 && ($today == 1)) $execute = true;

        if (!$execute) return;

        $csOrders = DB::table('cs_orders as CsOrder')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'CsOrder.vehicle_id')
            ->where('CsOrder.status', 1)
            ->select('CsOrder.vehicle_id', 'CsOrder.parent_id', 'CsOrder.id', 'CsOrder.renter_id', 'CsOrder.user_id', 'Vehicle.vin_no')
            ->get();

        if ($csOrders->isEmpty()) return;

        $inspektService = new InspektService();
        foreach ($csOrders as $csOrder) {
            $stillOpen = DB::table('vehicle_scan_inspections')
                ->where('vehicle_id', $csOrder->vehicle_id)
                ->where('status', 0)
                ->count();
            if ($stillOpen) continue;

            $reqObj = [
                'vehicle_id' => $csOrder->vehicle_id,
                'vin_no' => $csOrder->vin_no,
                'rand' => $csOrder->id,
            ];
            $tokenObj = $inspektService->generateToken($reqObj);
            if (!$tokenObj['status']) continue;

            DB::table('vehicle_scan_inspections')->insert([
                'case_id' => $tokenObj['result']['caseId'],
                'token' => $tokenObj['result']['token'],
                'vehicle_id' => $csOrder->vehicle_id,
                'order_id' => $csOrder->id,
                'parent_order_id' => !empty($csOrder->parent_id) ? $csOrder->parent_id : $csOrder->id,
            ]);

            DB::table('cs_vehicle_issues')->insert([
                'renter_id' => $csOrder->renter_id,
                'vehicle_id' => $csOrder->vehicle_id,
                'user_id' => $csOrder->user_id,
                'cs_order_id' => $csOrder->id,
                'type' => 7,
                'extra' => json_encode($tokenObj['result']),
            ]);
        }
    }
}
