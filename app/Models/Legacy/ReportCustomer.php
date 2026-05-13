<?php

namespace App\Models\Legacy;

use Illuminate\Support\Facades\DB;
use App\Models\Legacy\LegacyModel;

class ReportCustomer extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'report_customers';

    protected $fillable = [
        'user_id',
        'renter_id',
        'cs_order_id',
        'increment_id',
        'vehicle_id',
        'days',
        'miles',
        'rent',
        'extra_mile_fee',
        'tax',
        'dia_fee',
        'fixed_amt',
        'total_rent',
        'calculated_insurance',
        'insurance',
        'insurance_driver',
        'total_billed',
        'uncollected',
        'total_collected',
        'emf_collected',
        'tax_collected',
        'dia_fee_collected',
        'revpart',
        'gross_revenue',
        'driver_credit',
        'total_net_pay',
        'transferred',
        'net_transferred',
        'revshare',
        'tax_included',
        'pending',
        'start_datetime',
        'end_datetime',
        'total_program_cost',
        'down_payment_goal',
        'write_down_allocation',
        'finance_allocation',
        'maintenance_allocation',
        'disposition_fee',
        'stripe_fee',
        'total_latefee',
        'collected_latefee',
        'status',
        'timezone',
        'last_executed',
        'created',
        'updated',
    ];

    public static function refreshReport($reportId)
    {
        $report = self::findOrFail($reportId);
        $orderId = $report->cs_order_id;
        $revSetting = DB::table('rev_settings')->where('user_id', $report->user_id)->first();
        $ownerPart = $revSetting->rev ?? config('legacy.OWNER_PART', 0);
        $taxIncluded = ($revSetting && isset($revSetting->tax_included) && $revSetting->tax_included == 0) ? false : true;
        $revshare = 100 - $ownerPart;

        $revPartSql = $taxIncluded
            ? "((cs_order_payments.amount - cs_order_payments.dia_fee) * $revshare / 100) + cs_order_payments.dia_fee"
            : "((cs_order_payments.amount - cs_order_payments.dia_fee - cs_order_payments.tax) * $revshare / 100) + cs_order_payments.dia_fee";

        $result = DB::table('cs_orders')
            ->select([
                'start_datetime',
                'timezone',
                'start_odometer',
                DB::raw("IFNULL((select SUM(DATEDIFF(end_datetime, start_datetime)) from cs_orders as cd where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as days"),
                DB::raw("IFNULL((select SUM(rent + damage_fee + uncleanness_fee + cancellation_fee) from cs_orders as cd where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as rent"),
                DB::raw("IFNULL((select SUM(extra_mileage_fee) from cs_orders as cd where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as extra_mile_fee"),
                DB::raw("IFNULL((select SUM(tax + emf_tax + initial_fee_tax) from cs_orders as cd where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as tax"),
                DB::raw("IFNULL((select SUM(dia_fee) from cs_orders as cd where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as dia_fee"),
                DB::raw("IFNULL((select SUM(initial_fee) from cs_orders as cd where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as fixed_amt"),
                DB::raw("IFNULL((select SUM(insurance_amt + dia_insu) from cs_orders as cd where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as calculated_insurance"),
                DB::raw("IFNULL((select SUM(lateness_fee) from cs_orders as cd where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as total_latefee"),
                DB::raw("IFNULL((select SUM(amount) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id = cd.id AND cs_order_payments.type IN (4,14) AND cs_order_payments.status = 1 where cs_order_payments.payer_id = cs_orders.user_id AND (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as insurance_by_dealer"),
                DB::raw("IFNULL((select SUM(amount) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id = cd.id AND cs_order_payments.type IN (4,14) AND cs_order_payments.status = 1 where cs_order_payments.payer_id != cs_orders.user_id AND (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as insurance_by_renter"),
                DB::raw("IFNULL((select SUM(amount) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id = cd.id AND cs_order_payments.type IN (2,3) AND cs_order_payments.status = 1 where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as total_rent_collected"),
                DB::raw("IFNULL((select SUM(amount) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id = cd.id AND cs_order_payments.type = 19 AND cs_order_payments.status = 1 where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as collected_latefee"),
                DB::raw("IFNULL((select SUM(cs_order_payments.amount) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id = cd.id AND cs_order_payments.type = 16 AND cs_order_payments.status = 1 where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as total_emf_collected"),
                DB::raw("IFNULL((select SUM(cs_order_payments.tax) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id = cd.id AND cs_order_payments.type = 16 AND cs_order_payments.status = 1 where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as total_emf_tax_collected"),
                DB::raw("IFNULL((select SUM(cs_order_payments.tax) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id = cd.id AND cs_order_payments.type IN (2,3) AND cs_order_payments.status = 1 where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as total_tax_collected"),
                DB::raw("IFNULL((select SUM(cs_order_payments.dia_fee) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id = cd.id AND cs_order_payments.type IN (2,3,16) AND cs_order_payments.status = 1 where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as total_dia_collected"),
                DB::raw("IFNULL((select SUM(amount) from cs_payout_transactions left join cs_orders as cd on cs_payout_transactions.cs_order_id = cd.id AND cs_payout_transactions.status = 1 where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as dealer_amt"),
                DB::raw("IFNULL((select SUM(stripe_amt) from cs_payout_transactions left join cs_orders as cd on cs_payout_transactions.cs_order_id = cd.id AND cs_payout_transactions.status = 1 where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as net_stripe_amt"),
                DB::raw("IFNULL((select MAX(end_odometer) from cs_orders as cd where (cd.parent_id = cs_orders.id OR cd.id = cs_orders.id) and cd.status = 3), 0) as endodometer"),
                DB::raw("IFNULL((select SUM($revPartSql) from cs_order_payments left join cs_orders as cd on cs_order_payments.cs_order_id = cd.id AND cs_order_payments.type IN (2,3,16) AND cs_order_payments.status = 1 where (cd.id = cs_orders.id OR cd.parent_id = cs_orders.id) and cd.status = 3), 0) as revpart")
            ])
            ->where('id', $orderId)
            ->where('status', 3)
            ->first();

        if (!$result) {
            return;
        }

        $lastBooking = DB::table('cs_orders')
            ->where('id', $orderId)
            ->orWhere('parent_id', $orderId)
            ->orderBy('id', 'DESC')
            ->first(['end_datetime', 'status', 'vehicle_id']);

        $depositRule = DB::table('cs_order_deposit_rules')
            ->where('cs_order_id', $orderId)
            ->orderBy('id', 'DESC')
            ->first();

        $report->days = $result->days;
        $report->rent = $result->rent;
        $report->extra_mile_fee = $result->extra_mile_fee;
        $report->tax = $result->tax;
        $report->dia_fee = $result->dia_fee;
        $report->fixed_amt = $result->fixed_amt;
        $report->total_rent = number_format($result->rent + $result->extra_mile_fee + $result->tax, 4, '.', '');
        $report->total_billed = number_format($report->total_rent + $result->fixed_amt + $result->dia_fee, 4, '.', '');
        $report->insurance = $result->insurance_by_dealer;
        $report->calculated_insurance = $result->calculated_insurance;
        $report->insurance_driver = $result->insurance_by_renter;
        $report->total_collected = $result->total_rent_collected + $result->total_emf_collected;
        $report->emf_collected = $result->total_emf_collected - $result->total_emf_tax_collected;
        $report->tax_collected = $result->total_tax_collected + $result->total_emf_tax_collected;
        $report->dia_fee_collected = $result->total_dia_collected;
        $report->uncollected = number_format($report->total_billed - $report->total_collected, 4, '.', '');
        $report->total_latefee = $result->total_latefee;
        $report->collected_latefee = $result->collected_latefee;
        $report->transferred = $result->dealer_amt;
        $report->net_transferred = $result->net_stripe_amt > 0 ? $result->net_stripe_amt : $result->dealer_amt;
        $report->miles = $result->endodometer > 1 ? ($result->endodometer - $result->start_odometer) : 0;
        $report->revpart = $result->revpart;
        $report->stripe_fee = $result->net_stripe_amt > 0 ? ($result->dealer_amt - $result->net_stripe_amt) : 0;
        $report->pending = number_format($report->total_collected - ($result->revpart + $result->dealer_amt), 4, '.', '');
        $report->gross_revenue = number_format($report->total_collected - $result->revpart, 4, '.', '');
        $report->total_net_pay = $report->gross_revenue - $result->insurance_by_dealer;
        $report->revshare = $revshare;
        $report->tax_included = $taxIncluded;
        $report->timezone = $result->timezone;
        $report->start_datetime = $result->start_datetime;

        if ($lastBooking) {
            $report->end_datetime = $lastBooking->end_datetime;
            $report->status = $lastBooking->status;
            $report->vehicle_id = $lastBooking->vehicle_id;
        }

        if ($depositRule) {
            $report->total_program_cost = $depositRule->total_program_cost;
            $report->down_payment_goal = $depositRule->downpayment;
            $baseForAllocation = $report->total_collected - $report->tax_collected;
            $report->write_down_allocation = number_format($baseForAllocation * ($depositRule->write_down_allocation / 100), 4, '.', '');
            $report->finance_allocation = number_format($baseForAllocation * ($depositRule->finance_allocation / 100), 4, '.', '');
            $report->maintenance_allocation = number_format($baseForAllocation * ($depositRule->maintenance_allocation / 100), 4, '.', '');

            if ($depositRule->disposition_fee > 0 && $depositRule->total_program_cost > 0) {
                $report->disposition_fee = number_format(($depositRule->disposition_fee / $depositRule->total_program_cost) * $baseForAllocation, 2, '.', '');
            } else {
                $report->disposition_fee = 0;
            }
        }

        $report->last_executed = now();
        $report->save();

        return;
    }

    public static function createReport($orderId)
    {
        $columns = [
            'user_id',
            'renter_id',
            'cs_order_id',
            'increment_id',
            'vehicle_id',
            'last_executed',
            'created'
        ];

        $query = DB::table('cs_orders')
            ->select('user_id', 'renter_id', 'id', 'increment_id', 'vehicle_id')
            ->selectRaw('NOW(), NOW()')
            ->where('id', $orderId)
            ->where('parent_id', 0);

        DB::table('report_customers')->insertUsing($columns, $query);

        return;
    }
}
