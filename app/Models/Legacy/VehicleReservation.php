<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class VehicleReservation extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'vehicle_reservations';

    protected $fillable = [
        'user_id',
        'renter_id',
        'vehicle_id',
        'start_datetime',
        'end_datetime',
        'status',
        'accepted_time',
        'details',
        'note',
        'notified',
        'timezone',
        'user_ip',
        'pto',
        'insurance',
        'sub_insu_frequency',
        'initial_fee',
        'initial_discount',
        'discount_desc',
        'buy',
        'delivery',
        'checkr_status',
        'income_threshold',
        'gps',
        'gps2',
        'clue_report',
        'docusign',
        'cancel_note',
        'checklists',
        'ready_for_dealer',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'id' => 'integer',
        'vehicle_id' => 'integer',
        'user_id' => 'integer',
        'renter_id' => 'integer',
        'status' => 'integer',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function depositRule(): HasOne
    {
        return $this->hasOne(OrderDepositRule::class, 'vehicle_reservation_id');
    }

    public static function updatePendingBooking($renterId, $type = 1): void
    {
        $pendingBooking = self::where('status', 0)
            ->where('renter_id', $renterId)
            ->where('created', '>', Carbon::now()->subDays(7))
            ->orderBy('id', 'desc')
            ->first(['id', 'income_threshold']);

        if ($pendingBooking) {
            if ($type == 1) {
                // $pendingBooking->uber_lyft = 1;
                // $pendingBooking->save();
            }

            if ($type == 2) {
                // $pendingBooking->bank_linked = 1;
            }

            if ($type == 3) {
                // $pendingBooking->income_linked = 1;
            }

            if ($type == 4) {
                $pendingBooking->income_threshold = 1;
                $pendingBooking->save();
            }
        }
    }
}
