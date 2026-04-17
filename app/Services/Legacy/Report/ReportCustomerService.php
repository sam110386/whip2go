<?php

namespace App\Services\Legacy\Report;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Thin DB::table wrapper porting CakePHP Report\ReportCustomer (raw report SQL).
 */
class ReportCustomerService
{
    protected string $table = 'report_customers';

    public function builder(): Builder
    {
        return DB::table($this->table);
    }

    /**
     * @param  int|string  $id
     */
    public function findById($id): ?object
    {
        return $this->builder()->where('id', $id)->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function save(array $attributes): bool
    {
        if (! empty($attributes['id'])) {
            $id = $attributes['id'];
            unset($attributes['id']);

            return $this->builder()->where('id', $id)->update($attributes) !== false;
        }

        return $this->builder()->insert($attributes);
    }

    /**
     * @param  int|string  $reportid
     */
    public function refreshReport($reportid): void
    {
        $row = $this->findById($reportid);
        if ($row === null) {
            return;
        }

        $rowArr = (array) $row;
        $orderid = $rowArr['cs_order_id'];
        $tempRow = $rowArr;

        $revSettingObjData = DB::table('rev_settings')->where('user_id', $rowArr['user_id'])->first();
        $revshare = (! empty($revSettingObjData?->rev))
            ? $revSettingObjData->rev
            : config('legacy.OWNER_PART', 85);
        $taxIncluded = (isset($revSettingObjData->tax_included) && (int) $revSettingObjData->tax_included === 0)
            ? false
            : true;

        $revshare = 100 - $revshare;
        if ($taxIncluded) {
            $subquery = 'IFNULL((select SUM(((cs_order_payments.amount-cs_order_payments.dia_fee)*'.$revshare.'/100)+cs_order_payments.dia_fee) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id=cd.id AND cs_order_payments.type IN (2,3,16) AND cs_order_payments.status=1  where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as revpart';
        } else {
            $subquery = 'IFNULL((select SUM(((cs_order_payments.amount-cs_order_payments.dia_fee-cs_order_payments.tax)*'.$revshare.'/100)+cs_order_payments.dia_fee) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id=cd.id AND cs_order_payments.type IN (2,3,16) AND cs_order_payments.status=1 where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as revpart';
        }

        $sql = '
            select
            start_datetime,
            timezone,
            start_odometer,
            IFNULL((select SUM(dateDiff(end_datetime,start_datetime)) as days from cs_orders as cd where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as days,
            IFNULL((select SUM(rent+damage_fee+uncleanness_fee+cancellation_fee) from cs_orders as cd where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as rent,
            IFNULL((select SUM(extra_mileage_fee) from cs_orders as cd where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as extra_mile_fee,
            IFNULL((select SUM(tax+emf_tax+initial_fee_tax) from cs_orders as cd where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as tax,
            IFNULL((select SUM(dia_fee) from cs_orders as cd where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as dia_fee,
            IFNULL((select SUM(initial_fee) from cs_orders as cd where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as fixed_amt,
            IFNULL((select SUM(insurance_amt + dia_insu) from cs_orders as cd where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as calculated_insurance,
            IFNULL((select SUM(lateness_fee) from cs_orders as cd where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as total_latefee,
            IFNULL((select SUM(amount) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id=cd.id AND (cs_order_payments.type=4 OR cs_order_payments.type=14) AND cs_order_payments.status=1  where cs_order_payments.payer_id=cs_orders.user_id AND (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as insurance_by_dealer,
            IFNULL((select SUM(amount) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id=cd.id AND (cs_order_payments.type=4 OR cs_order_payments.type=14) AND cs_order_payments.status=1  where cs_order_payments.payer_id!=cs_orders.user_id AND (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as insurance_by_renter,
            IFNULL((select SUM(amount) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id=cd.id AND cs_order_payments.type IN (2,3) AND cs_order_payments.status=1  where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as total_rent_collected,
            IFNULL((select SUM(amount) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id=cd.id AND cs_order_payments.type=19 AND cs_order_payments.status=1  where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as collected_latefee,
            IFNULL((select SUM(cs_order_payments.amount) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id=cd.id AND cs_order_payments.type=16 AND cs_order_payments.status=1  where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as total_emf_collected,
            IFNULL((select SUM(cs_order_payments.tax) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id=cd.id AND cs_order_payments.type=16 AND cs_order_payments.status=1  where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as total_emf_tax_collected,
            IFNULL((select SUM(cs_order_payments.tax) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id=cd.id AND cs_order_payments.type IN (2,3) AND cs_order_payments.status=1  where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as total_tax_collected,
            IFNULL((select SUM(cs_order_payments.dia_fee) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id=cd.id AND cs_order_payments.type IN (2,3,16) AND cs_order_payments.status=1  where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as total_dia_collected,
            IFNULL((select SUM(amount) from cs_payout_transactions left join cs_orders as cd on cs_payout_transactions.cs_order_id=cd.id AND cs_payout_transactions.status=1  where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as dealer_amt,
            IFNULL((select SUM(stripe_amt) from cs_payout_transactions left join cs_orders as cd on cs_payout_transactions.cs_order_id=cd.id AND cs_payout_transactions.status=1  where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as net_stripe_amt,
            IFNULL((select SUM(refund) from cs_payout_transactions left join cs_orders as cd on cs_payout_transactions.cs_order_id=cd.id AND cs_payout_transactions.status=1 AND cs_payout_transactions.type=11  where (cd.id=cs_orders.id OR cd.parent_id=cs_orders.id) and cd.status=3),0) as dealer_refund,
            (select MAX(end_odometer) from cs_orders as cd where (cd.parent_id=cs_orders.id OR cd.id=cs_orders.id) and cd.status=3) as endodometer,
            '.$subquery.'
            from cs_orders
            where cs_orders.status=3 AND cs_orders.id='.$orderid;

        $result = DB::select($sql);
        if ($result === []) {
            return;
        }

        $resultObj = (array) $result[0];
        $subQuery = 'select end_datetime,status,vehicle_id from cs_orders where id='.$orderid.' OR parent_id='.$orderid.' order by id DESC limit 1';
        $resultsubQuery = DB::select($subQuery);
        $subRow = $resultsubQuery !== [] ? (array) $resultsubQuery[0] : [];

        $tempRow['days'] = $resultObj['days'];

        $tempRow['rent'] = $resultObj['rent'];
        $tempRow['extra_mile_fee'] = $resultObj['extra_mile_fee'];
        $tempRow['tax'] = $resultObj['tax'];
        $tempRow['dia_fee'] = $resultObj['dia_fee'];
        $tempRow['fixed_amt'] = $resultObj['fixed_amt'];
        $tempRow['total_rent'] = sprintf('%0.4f', ((float) $resultObj['rent'] + (float) $resultObj['extra_mile_fee'] + (float) $resultObj['tax']));
        $tempRow['total_billed'] = sprintf('%0.4f', ((float) $tempRow['total_rent'] + (float) $resultObj['fixed_amt'] + (float) $resultObj['dia_fee']));
        $tempRow['insurance'] = $resultObj['insurance_by_dealer'];
        $tempRow['calculated_insurance'] = $resultObj['calculated_insurance'];
        $tempRow['insurance_driver'] = $resultObj['insurance_by_renter'];
        $tempRow['total_collected'] = ($resultObj['total_rent_collected'] + $resultObj['total_emf_collected']);
        $tempRow['emf_collected'] = ($resultObj['total_emf_collected'] - $resultObj['total_emf_tax_collected']);
        $tempRow['tax_collected'] = ($resultObj['total_tax_collected'] + $resultObj['total_emf_tax_collected']);
        $tempRow['dia_fee_collected'] = $resultObj['total_dia_collected'];
        $tempRow['uncollected'] = sprintf('%0.4f', ((float) $tempRow['total_billed'] - ((float) ($resultObj['total_rent_collected'] + $resultObj['total_emf_collected']))));
        $tempRow['total_latefee'] = $resultObj['total_latefee'];
        $tempRow['collected_latefee'] = $resultObj['collected_latefee'];

        $tempRow['transferred'] = $resultObj['dealer_amt'];
        $tempRow['net_transferred'] = ($resultObj['net_stripe_amt'] > 0 ? $resultObj['net_stripe_amt'] : $resultObj['dealer_amt']);
        $startOdometer = $resultObj['start_odometer'] ?? null;
        $tempRow['miles'] = $resultObj['endodometer'] > 1 ? $resultObj['endodometer'] - $startOdometer : 0;
        $tempRow['revpart'] = $resultObj['revpart'];
        $tempRow['stripe_fee'] = ($resultObj['net_stripe_amt'] > 0 ? $resultObj['dealer_amt'] - $resultObj['net_stripe_amt'] : 0);
        $tempRow['pending'] = sprintf('%0.4f', ((float) $tempRow['total_collected'] - ((float) $resultObj['revpart'] + (float) $resultObj['dealer_amt'])));

        $tempRow['gross_revenue'] = sprintf('%0.4f', ($tempRow['total_collected'] - (float) $resultObj['revpart']));

        $tempRow['total_net_pay'] = ($tempRow['gross_revenue'] - (float) $resultObj['insurance_by_dealer']);
        $tempRow['revshare'] = $revshare ?? 0;
        $tempRow['tax_included'] = $taxIncluded;

        $tempRow['timezone'] = $resultObj['timezone'];
        $tempRow['start_datetime'] = $resultObj['start_datetime'];
        if ($subRow !== []) {
            $tempRow['end_datetime'] = $subRow['end_datetime'];
            $tempRow['status'] = $subRow['status'];
            $tempRow['vehicle_id'] = $subRow['vehicle_id'];
        }
        $tempRow['last_executed'] = date('Y-m-d H:i:d');
        $tempRow['updated'] = date('Y-m-d H:i:d');

        $subQuery2 = 'select downpayment,total_program_cost,write_down_allocation,finance_allocation,maintenance_allocation,disposition_fee,totalcost from cs_order_deposit_rules where cs_order_id='.$orderid.' order by id DESC limit 1';
        $resultsubQuery2 = DB::select($subQuery2);
        if ($resultsubQuery2 !== []) {
            $r2 = (array) $resultsubQuery2[0];
            $tempRow['total_program_cost'] = $r2['total_program_cost'];
            $tempRow['down_payment_goal'] = $r2['downpayment'];
            $tempRow['write_down_allocation'] = sprintf('%0.4f', (($tempRow['total_collected'] - $tempRow['tax_collected']) * ($r2['write_down_allocation'] / 100)));
            $tempRow['finance_allocation'] = sprintf('%0.4f', ($tempRow['total_collected'] - $tempRow['tax_collected']) * ($r2['finance_allocation'] / 100));
            $tempRow['maintenance_allocation'] = sprintf('%0.4f', ($tempRow['total_collected'] - $tempRow['tax_collected']) * ($r2['maintenance_allocation'] / 100));
            $tempRow['disposition_fee'] = $r2['disposition_fee'] > 0 ? sprintf('%0.2f', (($r2['disposition_fee'] / $r2['total_program_cost']) * ($tempRow['total_collected'] - $tempRow['tax_collected']))) : 0;
        }

        $id = $tempRow['id'];
        unset($tempRow['id']);
        $this->builder()->where('id', $id)->update($tempRow);
    }

    /**
     * @param  int|string  $orderid
     */
    public function createReport($orderid): void
    {
        $sql = 'insert into report_customers (user_id,renter_id,cs_order_id,increment_id,vehicle_id,last_executed,created)
            select cs_orders.user_id,cs_orders.renter_id,cs_orders.id,cs_orders.increment_id,cs_orders.vehicle_id,NOW(),NOW()
            from cs_orders where cs_orders.parent_id=0 AND cs_orders.id='.$orderid;
        DB::statement($sql);
    }
}
