<?php

namespace App\Models\Legacy;

use App\Services\Legacy\Report\ReportCustomerlibService;
use App\Services\Legacy\SalesforceClient;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CsOrderStatuslog extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_order_statuslogs';

    protected $fillable = [
        'cs_order_id',
        'vehicle_id',
        'user_id',
        'status',
        'request',
        'requestStatus',
        'response',
        'responseStatus',
        'target',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

    public function csOrder()
    {
        return $this->belongsTo(CsOrder::class, 'cs_order_id');
    }


    public static function saveBookingStartEvent($bookingId, $userId): void
    {
        self::create([
            'cs_order_id' => $bookingId,
            'user_id' => $userId,
            'status' => 0,
            'requestStatus' => 1,
            'target' => 'SF'
        ]);
    }
    public static function saveBookingCancelEvent($bookingId, $userId): void
    {
        self::create([
            'cs_order_id' => $bookingId,
            'user_id' => $userId,
            'status' => 0,
            'requestStatus' => 2,
            'target' => 'SF'
        ]);
    }
    public static function saveBookingCompleteEvent($bookingId, $userId): void
    {
        self::create([
            'cs_order_id' => $bookingId,
            'user_id' => $userId,
            'status' => 0,
            'requestStatus' => 3,
            'target' => 'SF'
        ]);
    }
    public static function saveBookingCloseEvent($bookingId, $userId): void
    {
        self::create([
            'cs_order_id' => $bookingId,
            'user_id' => $userId,
            'status' => 0,
            'requestStatus' => 4,
            'target' => 'SF'
        ]);
    }
    public static function saveVehicleSyncEvent($vehicleId, $userId): void
    {
        // Kept commented out / placeholder matching your original code snippet
    }
    public static function saveBookingPendingToActiveEvent($bookingId, $userId): void
    {
        self::create([
            'cs_order_id' => $bookingId,
            'user_id' => $userId,
            'status' => 0,
            'requestStatus' => 0,
            'target' => 'SF'
        ]);
    }
    public static function processBookingStatusLog(): void
    {
        $records = self::where('status', 0)->limit(5)->get();

        if ($records->isEmpty()) {
            DB::table('cs_order_statuslogs')->truncate();
            return;
        }

        $reportCustomerLib = new ReportCustomerlibService();
        $caRiteDealers = AdminUserAssociation::where('admin_id', 5270)
            ->pluck('user_id')
            ->toArray();

        foreach ($records as $record) {
            $record->update(['status' => 1]);

            if (!empty($record->cs_order_id)) {
                $reportCustomerLib->saveReportQueue($record->cs_order_id);
            }

            if ($record->target === 'SF' && $record->requestStatus == 0 && in_array($record->user_id, $caRiteDealers)) {
                self::updateToVendor($record);
            }
        }
    }
    public static function salesforceBooking(self $record): void
    {
        $originalOrderData = CsOrder::with(['renter:id,email', 'owner:id,email'])
            ->where('id', $record->cs_order_id)
            ->first();

        if (empty($originalOrderData)) {
            return;
        }

        if (!empty($originalOrderData->parent_id)) {
            $allSiblings = CsOrder::where('id', $originalOrderData->parent_id)
                ->orWhere('parent_id', $originalOrderData->parent_id)
                ->pluck('id')
                ->toArray();
        } else {
            $allSiblings = [$record->cs_order_id];
        }

        $rental = CsOrderPayment::whereIn('cs_order_id', $allSiblings)
            ->where('type', 2)
            ->where('status', 1)
            ->selectRaw('SUM(amount) as amount, SUM(rent) as rent, SUM(tax) as tax, SUM(dia_fee) as dia_fee')
            ->first();

        $insurance = CsOrderPayment::whereIn('cs_order_id', $allSiblings)
            ->whereIn('type', [4, 14])
            ->where('status', 1)
            ->sum('amount');

        $initialFee = CsOrderPayment::whereIn('cs_order_id', $allSiblings)
            ->where('type', 3)
            ->where('status', 1)
            ->sum('amount');

        $deposits = CsOrderPayment::whereIn('cs_order_id', $allSiblings)
            ->where('type', 1)
            ->where('status', 1)
            ->sum('amount');

        $status = match ((int) $record->requestStatus) {
            1 => 'New',
            2 => 'Canceled',
            3 => 'Completed',
            4 => 'Review Completed',
            default => null
        };

        if ($record->requestStatus == 3 && $originalOrderData->auto_renew == 0) {
            $status = 'Active';
        }

        $data = [];
        if (empty($originalOrderData->parent_id)) {
            $data['Name'] = $originalOrderData->increment_id;
        }

        $bookingId = $originalOrderData->parent_id ?: $originalOrderData->id;
        $tz = $originalOrderData->timezone;

        $data["Booking_ID__c"] = $bookingId;

        if (empty($originalOrderData->parent_id)) {
            $data['StartDateTime__c'] = Carbon::parse($originalOrderData->start_datetime, $tz)->toDateTimeString();
        }

        $data['EndDateTime__c'] = Carbon::parse($originalOrderData->end_datetime, $tz)->toDateTimeString();
        $data['DealerId__c'] = $originalOrderData->user_id;
        $data["DealerEmail__c"] = $originalOrderData->owner->email ?? '';
        $data["RenterId__c"] = $originalOrderData->renter_id;
        $data["RenterEmail__c"] = $originalOrderData->renter->email ?? '';
        $data["Vehicle_Name__c"] = $originalOrderData->vehicle_name;

        if (empty($originalOrderData->parent_id)) {
            $data["StartTiming__c"] = $originalOrderData->start_timing ? Carbon::parse($originalOrderData->start_timing, $tz)->toDateTimeString() : "";
        }

        $data["EndTiming__c"] = $originalOrderData->end_timing
            ? Carbon::parse($originalOrderData->end_timing, $tz)->toDateTimeString()
            : "";
        $data["Rent__c"] = $rental->rent ?? 0;
        $data["Tax__c"] = $rental->tax ?? 0;
        $data["Discount__c"] = $originalOrderData->discount;
        $data["Insurance__c"] = $insurance;
        $data["Deposit__c"] = $deposits;
        $data["InitialFee__c"] = $initialFee;
        $data["Toll__c"] = $originalOrderData->toll;
        $data["PTO__c"] = $originalOrderData->pto;
        $data["Status__c"] = $status;
        $data["Program_Cost__c"] = 0;
        $data["Down_Payment_Goal__c"] = 0;
        $data["Towards_Goal__c"] = 0;

        if ($originalOrderData->pto == 1) {
            $orderDepositRule = OrderDepositRule::where('cs_order_id', $bookingId)->first();

            if ($orderDepositRule) {
                $data["Program_Cost__c"] = $orderDepositRule->total_program_cost ?? 0;
                $data["Down_Payment_Goal__c"] = $orderDepositRule->downpayment ?? 0;

                $totalPaid = $rental->rent ?? 0;
                $paidInitialFee = $initialFee;
                $downpaymentPaid = sprintf('%0.2f', ($totalPaid + $paidInitialFee));

                if (($orderDepositRule->total_program_cost ?? 0) > 0) {
                    $data["Towards_Goal__c"] = sprintf('%d', ($downpaymentPaid / $orderDepositRule->total_program_cost) * 100);
                } elseif (($orderDepositRule->downpayment ?? 0) > 0) {
                    $data["Towards_Goal__c"] = sprintf('%d', ($downpaymentPaid / $orderDepositRule->downpayment) * 100);
                }
            }
        }

        $data["ParentBookingID__c"] = $originalOrderData->parent_id ?: $originalOrderData->id;

        $salesforce = new SalesforceClient();
        $salesforce->createBooking($data);
    }
    public static function updateToVendor(self $record): void
    {
        $orderData = CsOrder::with(['renter', 'owner', 'vehicle'])
            ->where('id', $record->cs_order_id)
            ->first();

        if (empty($orderData)) {
            return;
        }

        $requestDateStr = gmdate('Y-m-d\TH:i:s\Z', strtotime($orderData->start_datetime));

        $body = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <adf>
                <prospect>
                    <requestdate>' . $requestDateStr . '</requestdate>
                    <vehicle status="used">
                        <year>' . htmlspecialchars($orderData->vehicle->year ?? '') . '</year>
                        <make>' . htmlspecialchars($orderData->vehicle->make ?? '') . '</make>
                        <model>' . htmlspecialchars($orderData->vehicle->model ?? '') . '</model>
                        <vin>' . htmlspecialchars($orderData->vehicle->vin_no ?? '') . '</vin>
                        <trim>' . htmlspecialchars($orderData->vehicle->trim ?? '') . '</trim>
                        <bodystyle>' . htmlspecialchars($orderData->vehicle->cab_type ?? '') . '</bodystyle>
                        <finance>
                            <method>finance</method>
                        </finance>
                    </vehicle>
                    <customer>
                        <contact>
                            <name part="first" type="individual">' . htmlspecialchars($orderData->renter->first_name ?? '') . '</name>
                            <name part="last" type="individual">' . htmlspecialchars($orderData->renter->last_name ?? '') . '</name>
                            <email preferredcontact="0">' . htmlspecialchars($orderData->renter->email ?? '') . '</email>
                            <phone type="phone" preferredcontact="0">' . htmlspecialchars($orderData->renter->contact_number ?? '') . '</phone>
                        </contact>
                    </customer>
                    <vendor>
                        <vendorname>' . htmlspecialchars($orderData->owner->business_name ?? '') . '</vendorname>
                        <contact>
                            <name part="full" type="business">' . htmlspecialchars($orderData->owner->business_name ?? '') . '</name>
                            <phone>' . htmlspecialchars($orderData->owner->contact_number ?? '') . '</phone>
                            <address>
                                <street line="1">' . htmlspecialchars($orderData->owner->address ?? '') . '</street>
                                <city>' . htmlspecialchars($orderData->owner->city ?? '') . '</city>
                                <regioncode>' . htmlspecialchars($orderData->owner->state ?? '') . '</regioncode>
                                <postalcode>' . htmlspecialchars($orderData->owner->zip ?? '') . '</postalcode>
                            </address>
                        </contact>
                    </vendor>
                    <provider>
                        <id>capitalOneDigitalRetailId</id>
                        <name part="full">Capital One Digital Retail</name>
                        <service>Digital Retail</service>
                    </provider>
                </prospect>
            </adf>';

        echo $body;
    }

}
