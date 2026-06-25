<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class OrderDepositRule extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'cs_order_deposit_rules';

    protected $fillable = [
        'vehicle_reservation_id',
        'cs_order_id',
        'start_datetime',
        'initial_fee',
        'deposit_amt',
        'base_rent',
        'rental',
        'emf',
        'emf_rate',
        'emf_insu_rate',
        'miles',
        'insurance',
        'rental_opt',
        'initial_fee_opt',
        'deposit_opt',
        'duration_opt',
        'duration',
        'tax',
        'totalcost',
        'total_program_cost',
        'downpayment',
        'equityshare',
        'total_equity',
        'num_of_days',
        'total_initial_fee',
        'total_deposit_amt',
        'goal',
        'total_paid',
        'insurance_payer',
        'insurance_lender',
        'write_down_allocation',
        'finance_allocation',
        'maintenance_allocation',
        'disposition_fee',
        'insu_agreed',
        'calculation',
        'financing',
        'msrp',
        'premium_msrp',
        'minimum_payment',
        'minimum_payment_exp_date',
        'selling_option',
        'pickup_data',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'id' => 'integer',
        'cs_order_id' => 'integer',
        'vehicle_reservation_id' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(CsOrder::class, 'cs_order_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(VehicleReservation::class, 'vehicle_reservation_id');
    }

    public function axleStatus()
    {
        return $this->hasOne(AxleStatus::class, 'order_id', 'id');
    }
    public function csOrder()
    {
        return $this->belongsTo(CsOrder::class, 'cs_order_id', 'id');
    }


    public static function nextDuration($orderId, $startDate, $endDate)
    {
        $depositObj = self::where('cs_order_id', $orderId)
            ->where('duration_opt', '!=', '')
            ->select('duration_opt', 'start_datetime')
            ->first();

        if (!$depositObj) {
            return false;
        }

        return self::getFromTierData($depositObj->duration_opt, $startDate, $endDate);
    }
    public static function getFromTierData($tierData, $startDate, $endDate)
    {
        $retrun = false;
        $tierArray = json_decode($tierData, true);

        if (empty($tierArray)) {
            return $retrun;
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        foreach ($tierArray as $rlObj) {

            if (!isset($rlObj['after_date']) || !isset($rlObj['duration'])) {
                continue;
            }

            $afterDate = Carbon::parse($rlObj['after_date'])->startOfDay();

            if ($afterDate->between($start, $end)) {
                $retrun = $rlObj['duration'];
                break;
            }
        }

        return $retrun;
    }
}
