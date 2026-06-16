@if(!empty($notification))
    <legend class="text-size-medium text-samibold text-center">
        <em class="text-danger">{{ $notification }}</em>
    </legend>
@endif

<form action="{{url('admin/lease/create')}}" method="POST" name="triplogForm" id="triplogForm" class="form-horizontal">
    @csrf

    <div class="masonry">
        @if ($validateVehicle && empty($missingChecklists))
            <div class="item">
                <div class="panel panel-flat">
                    <div class="panel-body">
                        <legend class="text-semibold">Accept Booking</legend>
                        <input type="hidden" name="lease_id" value="{{ $lease_id }}">

                        <div class="row form-group">
                            <div class="col-md-2">From Date </div>
                            <div class="col-md-3">
                                <input type="text" name="daterangefrom" id="daterangefrom"
                                    class="form-control required date" value="{{ old('daterangefrom', $startDate) }}"
                                    rel-date="{{ $startDate }}" minDate="{{ $startDate }}">
                            </div>
                            <div class="col-md-2"> Time</div>
                            <div class="col-md-3">
                                <input type="text" name="start_time" id="start-time" class="form-control timeClass"
                                    value="{{ old('start_time', \Carbon\Carbon::parse($reservation['start_datetime'], $reservation['timezone'])->format('h:i A')) }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-2">To Date</div>
                            <div class="col-md-3">
                                <input type="text" class="form-control required date" name="daterangeto" id="daterangeto"
                                    value="{{ old('daterangeto', $endDate) }}">
                            </div>
                            <div class="col-md-2"> Time</div>
                            <div class="col-md-3">
                                <input type="text" name="end_time" id="end-time" class="form-control timeClass"
                                    value="{{ old('end_time', \Carbon\Carbon::parse($reservation['end_datetime'], $reservation['timezone'])->format('h:i A')) }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">&nbsp;</label>
                            <div class="col-lg-8">
                                <button type="button" class="focus_text btn no-margin" onclick="SaveBooking()"
                                    style="float: right;">
                                    Book
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        @endif

        @if (!$validateVehicle || !empty($missingChecklists))
            <div class="item">
                <div class="panel panel-flat">
                    <div class="panel-body">

                        @if(!empty($flagfailed))
                            <legend class="text-semibold">Failed Flags</legend>
                            <div class="formgroup row">
                                <div class="col-lg-8 control-label">
                                    <ul class="no-padding list-circle">
                                        @foreach ($flagfailed as $failed)
                                            <li>
                                                {{ $failed }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                        @if(!empty($missingChecklists))
                            <legend class="text-semibold">Failed Checklist</legend>
                            <div class="formgroup row">
                                <div class="col-lg-8 control-label">
                                    <ul class="no-padding list-circle">
                                        @foreach ($missingChecklists as $chklst)
                                            <li>
                                                {{ $chklst }}
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        @endif


        <div class="item">
            <div class="panel panel-flat">
                <div class="panel-body">
                    <legend class="text-semibold">Payment Information</legend>

                    @foreach ($csReservationPayments as $payment)
                        <div class="formgroup row">
                            <label class="col-lg-4 control-label">
                                <strong>{{ $commonService->getPayoutTypeValue(false, data_get($payment, 'type', false)) }}</strong>
                            </label>
                            <div class="col-lg-8 control-label">
                                {{ data_get($payment, 'amount', 0) }}
                            </div>
                        </div>
                    @endforeach

                    @php
                        $totalRentalPaid = $paidRental['rent'] + $paidRental['tax'] + $paidRental['dia_fee']; 
                    @endphp

                    <div class="formgroup row">
                        <label class="col-lg-4 control-label">
                            <strong>Rental</strong>
                        </label>
                        <div class="col-lg-8 control-label">
                            @php
                                $rent = isset($priceRulesAmt['time_fee'])
                                    ? ($priceRulesAmt['time_fee'] + $priceRulesAmt['tax'] + $priceRulesAmt['dia_fee'] + $priceRulesAmt['extra_mileage_fee'])
                                    : 0;
                            @endphp
                            {{ $totalRentalPaid }}/<b>{{ sprintf('%0.2f', $rent) }}</b>
                        </div>
                    </div>

                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Insurance</strong></label>
                        <div class="col-lg-8 control-label">
                            @php
                                $insu = $priceRulesAmt['insurance_amt'] ?? 0;
                                $insuDiff = $insu - $paidInsurance;
                            @endphp
                            {{ $paidInsurance }}/<b>{{ sprintf('%0.2f', $insu) }}</b>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="item">

            <div class="panel panel-flat">
                <div class="panel-body">
                    <legend class="text-bold">Program</legend>
                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Listed Selling Price:</strong></label>
                        <div class="col-lg-8 control-label">{{ data_get($vehicle, 'premium_msrp', '') }}</div>
                    </div>

                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Selling Price:</strong></label>
                        <div class="col-lg-8 control-label">{{ data_get($vehicle, 'msrp', '') }}</div>
                    </div>

                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Write Down Allocation:</strong></label>
                        <div class="col-lg-8 control-label">{{ data_get($orderDepositRule, 'downpayment', '') }}</div>
                    </div>

                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Total Program Cost:</strong></label>
                        <div class="col-lg-8 control-label">{{ data_get($orderDepositRule, 'total_program_cost', '') }}
                        </div>
                    </div>

                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong># of Days:</strong></label>
                        <div class="col-lg-8 control-label">{{ data_get($orderDepositRule, 'num_of_days', '') }}</div>
                    </div>

                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Total Scheduled Fee:</strong></label>
                        <div class="col-lg-8 control-label">{{ data_get($orderDepositRule, 'total_initial_fee', '') }}
                        </div>
                    </div>

                    @if (!empty($initialFeeOpt))
                        <div class="formgroup row">
                            <label class="col-lg-4 control-label"><strong>Scheduled Fee:</strong></label>
                            <div class="col-lg-8 control-label">
                                <ul class="no-padding">
                                    @foreach ($initialFeeOpt as $val)
                                        <li>
                                            <span>{{ $val['after_day_date'] }}</span> : <span>$ {{ $val['amount'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Scheduled Fee Discount</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ $reservation['initial_discount'] ?: 0 }}
                        </div>
                    </div>
                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Base Usage Rate:</strong></label>
                        <div class="col-lg-8 control-label">{{ data_get($orderDepositRule, 'base_rent', '') }}</div>
                    </div>

                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>EMF:</strong></label>
                        <div class="col-lg-8 control-label">{{ data_get($orderDepositRule, 'emf', '') }}</div>
                    </div>

                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Usage Rate Including EMF</strong></label>
                        <div class="col-lg-8 control-label">{{ data_get($orderDepositRule, 'rental', '') }}</div>
                    </div>

                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Tax</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ sprintf('%0.2f', (($priceRulesAmt['tax'] ?? 0) / ($priceRulesAmt['days'] ?? 1))) }}
                        </div>
                    </div>
                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Dia Fee</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ !empty($priceRulesAmt['dia_fee']) ? sprintf('%0.2f', ($priceRulesAmt['dia_fee'] / $priceRulesAmt['days'])) : 0 }}
                        </div>
                    </div>
                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Total Daily Usage Rate</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ sprintf('%0.2f', (($priceRulesAmt['time_fee'] ?? 0) + ($priceRulesAmt['tax'] ?? 0) + ($priceRulesAmt['dia_fee'] ?? 0) + ($priceRulesAmt['discount'] ?? 0)) / ($priceRulesAmt['days'] ?? 1)) }}
                        </div>
                    </div>
                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Cycle Usage Rate</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ sprintf('%0.2f', (($priceRulesAmt['time_fee'] ?? 0) + ($priceRulesAmt['tax'] ?? 0) + ($priceRulesAmt['dia_fee'] ?? 0) + ($priceRulesAmt['discount'] ?? 0))) }}
                        </div>
                    </div>

                    @if (($priceRulesAmt['discount'] ?? 0) > 0)
                        <div class="formgroup row">
                            <label class="col-lg-4 control-label"><strong>Usage - Discount</strong></label>
                            <div class="col-lg-8 control-label">
                                {{ $priceRulesAmt['discount'] }}
                            </div>
                        </div>
                    @endif
                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Day Miles:</strong></label>
                        <div class="col-lg-8 control-label">{{ data_get($orderDepositRule, 'miles', '') }}</div>
                    </div>

                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Insurance Type:</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ $commonService->getInsurancePayer(data_get($orderDepositRule, 'insurance_payer', '')) }}
                        </div>
                    </div>
                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Insurance Rate:</strong></label>
                        <div class="col-lg-8 control-label">{{ data_get($orderDepositRule, 'insurance', null) }}</div>
                    </div>
                    @if (!empty($rentalOpt))
                        <div class="formgroup row">
                            <label class="col-lg-4 control-label"><strong>Usage Tier:</strong></label>
                            <div class="col-lg-8 control-label">
                                <ul class="no-padding">
                                    @foreach ($rentalOpt as $val)
                                        <li>
                                            <span>
                                                After
                                                @if(isset($val['after_day_date']))
                                                    {{ \Carbon\Carbon::parse($val['after_day_date'])->format('m/d/Y') }}
                                                @else
                                                    {{ \Carbon\Carbon::parse(data_get($orderDepositRule, 'start_datetime'))->addDays($val['after_day'])->format('m/d/Y') }}
                                                @endif
                                            </span> : <span>$ {{ $val['amount'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="formgroup row">
                        <label class="col-lg-4 control-label"><strong>Deposit:</strong></label>
                        <div class="col-lg-8 control-label">{{ data_get($orderDepositRule, 'deposit_amt', '') }}</div>
                    </div>

                    @if (!empty($depositOpt))
                        <div class="formgroup row">
                            <label class="col-lg-4 control-label">
                                <strong>Deposit Schedules:</strong>
                            </label>
                            <div class="col-lg-8 control-label">
                                <ul class="no-padding">
                                    @foreach ($depositOpt as $val)
                                        <li>
                                            <span>{{ $val['after_day_date'] }}</span> : <span>$ {{ $val['amount'] }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @if (!empty($durationOpt))
                        <div class="formgroup row">
                            <label class="col-lg-4 control-label">
                                <strong>Renewal Schedules:</strong>
                            </label>
                            <div class="col-lg-8 control-label">
                                <ul class="no-padding">
                                    <li>
                                        <span>{{ $startDate }} To {{ $endDate }}</span> : <span> {{ $days }} Days</span>
                                    </li>
                                    @foreach ($durationOpt as $val)
                                        <li>
                                            <span>After {{ $val['after_date'] }}</span> : <span> {{ $val['duration'] }}
                                                Days</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @if (!empty($durations))
                        <div class="formgroup row">
                            <label class="col-lg-4 control-label text-danger">
                                <strong>Requested Renewal Schedules:</strong>
                            </label>
                            <div class="col-lg-8 control-label">
                                <ul class="no-padding">
                                    <li>
                                        <span>{{ $startDate }} To {{ $endDate }}</span> : <span> {{ $days }} Days</span>
                                    </li>
                                    @foreach ($durations as $val)
                                        <li>
                                            <span>After {{ $val['after_date'] }}</span> : <span> {{ $val['duration'] }}
                                                Days</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>

    </div>
</form>


<script type="text/javascript">
    jQuery(document).ready(function () {
        $('.timeClass').timepicki();
        $("#triplogForm").validate();
    });
</script>