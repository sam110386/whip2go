@extends('layouts.admin')
@section('content')
<div class="panel">
    <div class="panel-body">
        <div class="row">
            @if (!empty($csorder))
                <div class="{{ $payouts->isEmpty() ? 'col-lg-10' : 'col-lg-7' }}">
                    <div class="formgroup">
                        <legend class="text-semibold">Booking Details</legend>
                    </div>
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
                        <div class="col-lg-6">{{ \Carbon\Carbon::parse($csorder->end_datetime)->timezone($csorder->timezone ?? 'UTC')->format('m/d/Y h:i A') }}</div>
                    </div>

                    @if ($csorder->status != 2)
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Driver Bad Debt :</strong></label>
                            <div class="col-lg-6">{{ !empty($csorder->bad_debt) ? $csorder->bad_debt : 'N/A' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Total Paid Amount :</strong></label>
                            <div class="col-lg-6">{{ !empty($csorder->paid_amount) ? $csorder->paid_amount : 'N/A' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Insurance Amount :</strong></label>
                            <div class="col-lg-6">{{ $csorder->insurance_amt }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>DIA Ins Add On fee :</strong></label>
                            <div class="col-lg-6">{{ $csorder->dia_insu }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Initial Fee :</strong></label>
                            <div class="col-lg-6">{{ $csorder->initial_fee }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Rent :</strong></label>
                            <div class="col-lg-6">{{ !empty($csorder->rent) ? $csorder->rent : 'N/A' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Tax :</strong></label>
                            <div class="col-lg-6">{{ !empty($csorder->tax) ? $csorder->tax : 'N/A' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>DIA Fee :</strong></label>
                            <div class="col-lg-6">{{ !empty($csorder->dia_fee) ? $csorder->dia_fee : '0.0' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Discount/Credit :</strong></label>
                            <div class="col-lg-6">{{ !empty($csorder->credit_amt) ? $csorder->credit_amt : '0.0' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Extra Mileage Fee :</strong></label>
                            <div class="col-lg-6">{{ !empty($csorder->extra_mileage_fee) ? $csorder->extra_mileage_fee : 'N/A' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Lateness Fee :</strong></label>
                            <div class="col-lg-6">{{ !empty($csorder->lateness_fee) ? $csorder->lateness_fee : 'N/A' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Damage Fee :</strong></label>
                            <div class="col-lg-6">{{ !empty($csorder->damage_fee) ? $csorder->damage_fee : 'N/A' }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4"><strong>Uncleanness Fee :</strong></label>
                            <div class="col-lg-6">{{ !empty($csorder->uncleanness_fee) ? $csorder->uncleanness_fee : 'N/A' }}</div>
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
                        <div class="col-lg-6">{{ $csorder->status == 3 ? $csorder->end_odometer - $csorder->start_odometer : 0 }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>Details :</strong></label>
                        <div class="col-lg-6">{{ !empty($csorder->details) ? $csorder->details : 'N/A' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>Start Odometer :</strong></label>
                        <div class="col-lg-6">{{ !empty($csorder->start_odometer) ? $csorder->start_odometer : 0 }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4"><strong>End Odometer :</strong></label>
                        <div class="col-lg-6">{{ !empty($csorder->end_odometer) ? $csorder->end_odometer : 0 }}</div>
                    </div>
                </div>

                @if ($payouts->isNotEmpty())
                    <div class="col-lg-5">
                        <div class="formgroup">
                            <legend class="text-semibold">Payout Details</legend>
                        </div>
                        <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                            <thead>
                                <tr>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Payout#</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payouts as $payt)
                                    <tr>
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
