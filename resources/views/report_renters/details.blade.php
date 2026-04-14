{{-- Modal / AJAX fragment: booking detail (Cake `ReportRenters/details.ctp`). --}}
@php
    $o = is_array($csorder ?? null) ? ($csorder['CsOrder'] ?? null) : null;
    $u = is_array($csorder ?? null) ? ($csorder['User'] ?? []) : [];
@endphp
<div class="rowe">
    @if(!empty($o) && is_array($o))
    <form class="form-horizontal">
        <fieldset class="col-lg-10">
            <div class="panel-body">
                <div class="form-group">
                    <h3><div>Booking Details : </div></h3>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 "><strong>Customer :</strong> </label>
                    <div class="col-lg-6">{{ ($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '') }}</div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 "><strong>Phone# :</strong> </label>
                    <div class="col-lg-6">{{ $u['contact_number'] ?? '' }}</div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 "><strong>Vehicle# :</strong> </label>
                    <div class="col-lg-6">{{ $o['vehicle_name'] ?? '' }}</div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 "><strong>Job# :</strong> </label>
                    <div class="col-lg-6">{{ $o['id'] ?? '' }}</div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 "><strong>Start Date Time :</strong> </label>
                    <div class="col-lg-6">
                        @if(!empty($o['start_datetime']))
                            {{ \Carbon\Carbon::parse($o['start_datetime'])->format('m/d/Y h:i A') }}
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 "><strong>End Date Time :</strong> </label>
                    <div class="col-lg-6">
                        @if(!empty($o['end_datetime']))
                            {{ \Carbon\Carbon::parse($o['end_datetime'])->format('m/d/Y h:i A') }}
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 "><strong>Location :</strong> </label>
                    <div class="col-lg-6">{{ $o['pickup_address'] ?? '' }}</div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 "><strong>Actual Start Time :</strong> </label>
                    <div class="col-lg-6">
                        @if(!empty($o['start_timing']))
                            {{ \Carbon\Carbon::parse($o['start_timing'])->format('m/d/Y g:i A') }}
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 "><strong>Actual End Time :</strong></label>
                    <div class="col-lg-6">
                        @php $et = $o['end_timing'] ?? null; @endphp
                        @if($et === '0000-00-00 00:00:00' || $et === null || $et === '')
                            N/A
                        @elseif(!empty($et))
                            {{ \Carbon\Carbon::parse($et)->format('m/d/Y g:i A') }}
                        @else
                            N/A
                        @endif
                    </div>
                </div>
                @if(($o['status'] ?? null) != 2)
                    <div class="form-group">
                        <label class="col-lg-4 "><strong>Total Paid Amount :</strong> </label>
                        <div class="col-lg-6">{{ !empty($o['paid_amount']) ? $o['paid_amount'] : 'N/A' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 "><strong>Rent :</strong> </label>
                        <div class="col-lg-6">{{ !empty($o['rent']) ? $o['rent'] : 'N/A' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 "><strong>Tax :</strong> </label>
                        <div class="col-lg-6">{{ !empty($o['tax']) ? $o['tax'] : 'N/A' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 "><strong>Extra Mileage Fee :</strong> </label>
                        <div class="col-lg-6">{{ !empty($o['extra_mileage_fee']) ? $o['extra_mileage_fee'] : 'N/A' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 "><strong>Lateness Fee :</strong> </label>
                        <div class="col-lg-6">{{ !empty($o['lateness_fee']) ? $o['lateness_fee'] : 'N/A' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 "><strong>Damage Fee :</strong> </label>
                        <div class="col-lg-6">{{ !empty($o['damage_fee']) ? $o['damage_fee'] : 'N/A' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 "><strong>Uncleanness Fee :</strong> </label>
                        <div class="col-lg-6">{{ !empty($o['uncleanness_fee']) ? $o['uncleanness_fee'] : 'N/A' }}</div>
                    </div>
                @else
                    <div class="form-group">
                        <label class="col-lg-4 "><strong>Cancellation Fee :</strong> </label>
                        <div class="col-lg-6">{{ !empty($o['cancellation_fee']) ? $o['cancellation_fee'] : 'N/A' }}</div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 "><strong>Cancellation Note :</strong> </label>
                        <div class="col-lg-6">{{ !empty($o['cancel_note']) ? $o['cancel_note'] : 'N/A' }}</div>
                    </div>
                @endif
                <div class="form-group">
                    <label class="col-lg-4 "><strong>Transaction ID :</strong> </label>
                    <div class="col-lg-6">{{ !empty($o['transaction_id']) ? $o['transaction_id'] : 'N/A' }}</div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 "><strong>Total Miles :</strong> </label>
                    <div class="col-lg-6">
                        @if(($o['status'] ?? null) == 3)
                            {{ (int)($o['end_odometer'] ?? 0) - (int)($o['start_odometer'] ?? 0) }}
                        @else
                            0
                        @endif
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 "><strong>Details :</strong> </label>
                    <div class="col-lg-6">{{ !empty($o['details']) ? $o['details'] : 'N/A' }}</div>
                </div>
            </div>
        </fieldset>
    </form>
    @endif
</div>
