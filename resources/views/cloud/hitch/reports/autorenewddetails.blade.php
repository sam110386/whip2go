@extends('layouts.admin')
@section('content')
<div class="panel">
    <div class="panel-body">
        <div class="row">
            @if (!empty($csorder))
                <div class="{{ empty($payouts ?? null) ? 'col-lg-12' : 'col-lg-7' }}">
                    @if ($conversionBooking ?? false)
                        <legend>Conversion Equity Details</legend>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Selling Price :</strong></label>
                            <div class="col-lg-6">{{ $sellingprice ?? '' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Conversion Start Date :</strong></label>
                            <div class="col-lg-6">{{ $startConversionDate ?? '' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Target Conversion Date :</strong></label>
                            <div class="col-lg-6">{{ $target_conversion_date ?? '' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Down Payment Goal :</strong></label>
                            <div class="col-lg-6">{{ $totalDownpayment ?? '' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Total Paid :</strong></label>
                            <div class="col-lg-6">{{ $downpaymentPaid ?? '' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Total Program Cost :</strong></label>
                            <div class="col-lg-6">{{ $totalprogramcost ?? '' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Paid % :</strong></label>
                            <div class="col-lg-6">{{ $goal_percent ?? '' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Total Remaining :</strong></label>
                            <div class="col-lg-6">{{ $down_payment_remaining ?? '' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Total Days:</strong></label>
                            <div class="col-lg-6">{{ $total_payments_till ?? '' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Days till Conversion:</strong></label>
                            <div class="col-lg-6">{{ $payment_till_conversion ?? '' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Day Rent Choosen :</strong></label>
                            <div class="col-lg-6">{{ $DayFee ?? '' }}</div>
                        </div>
                    @endif

                    <legend>Booking Details</legend>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>Customer :</strong></label>
                        <div class="col-lg-6">{{ $csorder->first_name }} {{ $csorder->last_name }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>Phone# :</strong></label>
                        <div class="col-lg-6">{{ $csorder->contact_number }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>Vehicle# :</strong></label>
                        <div class="col-lg-6">{{ $csorder->vehicle_name }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>Booking# :</strong></label>
                        <div class="col-lg-6">{{ $csorder->increment_id }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>Start Date Time :</strong></label>
                        <div class="col-lg-6">{{ \Carbon\Carbon::parse($csorder->start_datetime)->timezone($csorder->timezone ?? 'UTC')->format('m/d/Y h:i A') }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>End Date Time :</strong></label>
                        <div class="col-lg-6">{{ !empty($subOrders->end_datetime) ? \Carbon\Carbon::parse($subOrders->end_datetime)->timezone($csorder->timezone ?? 'UTC')->format('m/d/Y h:i A') : '' }}</div>
                    </div>

                    @if ($csorder->status != 2)
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Driver Bad Debt :</strong></label>
                            <div class="col-lg-6">{{ !empty($csorder->bad_debt) ? $csorder->bad_debt : 'N/A' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Total Paid Amount :</strong></label>
                            <div class="col-lg-6">{{ !empty($subOrders->paid_amount) ? $subOrders->paid_amount : $csorder->paid_amount }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Insurance Amount :</strong></label>
                            <div class="col-lg-6">{{ $subOrders->insurance_amt ?? 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>DIA Ins Add On fee :</strong></label>
                            <div class="col-lg-6">{{ $subOrders->dia_insu ?? 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Initial Fee :</strong></label>
                            <div class="col-lg-6">{{ $subOrders->initial_fee ?? 0 }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Rent :</strong></label>
                            <div class="col-lg-6">{{ !empty($subOrders->rent) ? $subOrders->rent : $csorder->rent }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Tax :</strong></label>
                            <div class="col-lg-6">{{ !empty($subOrders->tax) ? $subOrders->tax : $csorder->tax }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Extra Mileage Fee :</strong></label>
                            <div class="col-lg-6">{{ !empty($subOrders->extra_mileage_fee) ? $subOrders->extra_mileage_fee : $csorder->extra_mileage_fee }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Lateness Fee :</strong></label>
                            <div class="col-lg-6">{{ !empty($subOrders->lateness_fee) ? $subOrders->lateness_fee : $csorder->lateness_fee }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Damage Fee :</strong></label>
                            <div class="col-lg-6">{{ !empty($subOrders->damage_fee) ? $subOrders->damage_fee : $csorder->damage_fee }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Uncleanness Fee :</strong></label>
                            <div class="col-lg-6">{{ !empty($subOrders->uncleanness_fee) ? $subOrders->uncleanness_fee : $csorder->uncleanness_fee }}</div>
                        </div>
                    @else
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Cancellation Fee :</strong></label>
                            <div class="col-lg-6">{{ !empty($csorder->cancellation_fee) ? $csorder->cancellation_fee : 'N/A' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Cancellation Note :</strong></label>
                            <div class="col-lg-6">{{ !empty($csorder->cancel_note) ? $csorder->cancel_note : 'N/A' }}</div>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="col-lg-4"><strong>Total Miles :</strong></label>
                        <div class="col-lg-6">{{ $csorder->status == 3 ? ($csorder->end_odometer - $csorder->start_odometer) : 0 }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>Details :</strong></label>
                        <div class="col-lg-6">{{ !empty($csorder->details) ? $csorder->details : 'N/A' }}</div>
                    </div>
                </div>

                @if (!empty($payouts) && $payouts->count())
                    <div class="col-lg-5">
                        <div class="formgroup">
                            <legend class="text-semibold">Payout Details</legend>
                        </div>
                        <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                            <thead>
                                <tr>
                                    <th>Booking#</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Payout#</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payouts as $payt)
                                    <tr>
                                        <td>{{ $Siblingbooking[$payt->cs_order_id] ?? '' }}</td>
                                        <td>{{ $payt->refund > 0 ? $payt->refund : $payt->amount }}</td>
                                        <td>{{ $payt->type }}</td>
                                        <td>{{ $payt->cs_payout_id }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
