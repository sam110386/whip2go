<script type="text/javascript">
    jQuery(document).ready(function() {
        $("#captureForm").validate();
    });
</script>

<form action="" method="POST" name="captureForm" id="captureForm" class="form-horizontal">
    @csrf

    <div class="masonry">
        <div class="item">
            <div class="panel panel-flat">
                <div class="panel-body">
                    <legend class="text-semibold">Payment Information</legend>
                    
                    @foreach ($csReservationPayments as $payment)
                        <div class="form-group row">
                            <label class="col-lg-4 control-label">
                                <strong>{{ $commonService->getPayoutTypeValue(false, data_get($payment, 'type')) }}</strong>
                            </label>
                            <div class="col-lg-8 control-label">
                                {{ data_get($payment, 'amount') }}
                                @if (data_get($payment, 'txntype') == 'P')
                                    &nbsp;&nbsp;
                                    <button type="button" class="btn btn-primary" onclick="capturePaymentVehicleReservation({{ data_get($payment, 'id') }})">
                                        <i class="icon-coin-dollar"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    @php 
                        $totalRentalPaid = data_get($paidRental, 'rent', 0) + data_get($paidRental, 'tax', 0) + data_get($paidRental, 'dia_fee', 0); 
                    @endphp

                    @if ($totalRentalPaid > 0)
                        <div class="form-group row">
                            <label class="col-lg-4 control-label"><strong>Usage - Paid</strong></label>
                            <div class="col-lg-8 control-label">
                                {{ $totalRentalPaid }}
                            </div>
                        </div>
                    @endif

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">
                            <strong>Usage - Unpaid</strong>
                        </label>
                        <div class="col-lg-8 control-label">
                            @php
                                $timeFee = data_get($priceRulesAmt, 'time_fee');
                                $rent = $timeFee ? ($timeFee + data_get($priceRulesAmt, 'tax', 0) + data_get($priceRulesAmt, 'dia_fee', 0)) : 0;
                                $diff = $rent - $totalRentalPaid;
                            @endphp

                            {{ sprintf('%0.2f', $diff) }}
                            
                            @if ($diff > 0)
                                <button type="button" class="btn btn-primary" onclick="authorizePaymentVehicleReservation({{ $diff }}, '{{ $lease_id }}', 2, '{{ json_encode($priceRulesAmt) }}')">
                                    <i class="icon-coin-dollar"></i>
                                </button>
                            @endif

                        </div>
                    </div>

                    @if (data_get($priceRulesAmt, 'discount', 0) > 0)
                        <div class="form-group row">
                            <label class="col-lg-4 control-label"><strong>Usage - Discount</strong></label>
                            <div class="col-lg-8 control-label">
                                {{ data_get($priceRulesAmt, 'discount') }}
                            </div>
                        </div>
                    @endif

                    @if ($paidInsurance > 0)
                        <div class="form-group row">
                            <label class="col-lg-4 control-label"><strong>Insurance - Paid</strong></label>
                            <div class="col-lg-8 control-label">
                                {{ $paidInsurance }}
                            </div>
                        </div>
                    @endif

                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Insurance - Unpaid</strong></label>
                        <div class="col-lg-8 control-label">
                            @php
                                $insu = data_get($priceRulesAmt, 'insurance_amt', 0);
                                $insuDiff = $insu - $paidInsurance;
                            @endphp
                            {{ sprintf('%0.2f', $insuDiff) }}
                            
                            @if ($insuDiff > 0)
                                &nbsp;&nbsp;
                                <button type="button" class="btn btn-primary" onclick="authorizePaymentVehicleReservation({{ $insuDiff }}, '{{ $lease_id }}', 4)">
                                    <i class="icon-coin-dollar"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    
                    @if (data_get($orderDepositRule, 'deposit_amt', 0) > $paidDeposit)
                        <div class="form-group row">
                            <label class="col-lg-4 control-label"><strong>Deposit - Unpaid</strong></label>
                            <div class="col-lg-8 control-label">
                                @php
                                    $depositDiff = data_get($orderDepositRule, 'deposit_amt') - $paidDeposit;
                                @endphp
                                {{ sprintf('%0.2f', $depositDiff) }}
                                
                                @if ($depositDiff > 0)
                                    &nbsp;&nbsp;
                                    <button type="button" class="btn btn-primary" onclick="authorizePaymentVehicleReservation({{ $depositDiff }}, '{{ $lease_id }}', 1)">
                                        <i class="icon-coin-dollar"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="item" id="capturepaypopupprepaidplans">
            @include('admin.vehicle_reservations.elements._prepaidplan')
        </div>

        @if ($promo)
            <div class="item">
                <div class="panel panel-flat">
                    <div class="panel-body">
                        <legend class="text-semibold">Attached Promo Rule To Driver</legend>
                        <div class="col-lg-6 control-label">
                            <strong>Code: </strong>{{ data_get($promo, 'promotionRule.promo', data_get($promo, 'PromotionRule.promo', '')) }}
                        </div>
                        <div class="col-lg-6 control-label">
                            <strong>Title: </strong>{{ data_get($promo, 'promotionRule.title', data_get($promo, 'PromotionRule.title', '')) }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="item">
            <div class="panel panel-flat">
                <div class="panel-body">
                    <legend class="text-bold">Program</legend>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Listed Selling Price:</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($vehicle, 'premium_msrp', '') }}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Selling Price:</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($vehicle, 'msrp', '') }}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Down Payment Goal:</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($orderDepositRule, 'downpayment', '') }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Total Program Cost:</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($orderDepositRule, 'total_program_cost', '') }}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong># of Days:</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($orderDepositRule, 'num_of_days', '') }}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Total Scheduled Fee:</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($orderDepositRule, 'total_initial_fee', '') }}
                        </div>
                    </div>
                    
                    @if (!empty($initialFeeOpt))
                        <div class="form-group row">
                            <label class="col-lg-4 control-label"><strong>Scheduled Fee:</strong></label>
                            <div class="col-lg-8 control-label">
                                <ul class="no-padding">
                                    @foreach ($initialFeeOpt as $val)
                                        <li>
                                            <span>{{ data_get($val, 'after_day_date') }}</span> : <span>$ {{ data_get($val, 'amount') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Scheduled Fee Discount</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($reserveData, 'initial_discount', 0) }}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Base Usage Rate:</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($orderDepositRule, 'base_rent', '') }}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>EMF:</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($orderDepositRule, 'emf', '') }}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Usage Rate Including EMF</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($orderDepositRule, 'rental', '') }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Tax</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ sprintf('%0.2f', (data_get($priceRulesAmt, 'tax', 0) / max(data_get($priceRulesAmt, 'days', 1), 1))) }}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Dia Fee</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($priceRulesAmt, 'dia_fee') ? sprintf('%0.2f', (data_get($priceRulesAmt, 'dia_fee') / max(data_get($priceRulesAmt, 'days', 1), 1))) : 0 }}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Total Daily Usage Rate</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ sprintf('%0.2f', (data_get($priceRulesAmt, 'time_fee', 0) + data_get($priceRulesAmt, 'tax', 0) + data_get($priceRulesAmt, 'dia_fee', 0) + data_get($priceRulesAmt, 'discount', 0)) / max(data_get($priceRulesAmt, 'days', 1), 1)) }}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Weekly Usage Rate</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ sprintf('%0.2f', (data_get($priceRulesAmt, 'time_fee', 0) + data_get($priceRulesAmt, 'tax', 0) + data_get($priceRulesAmt, 'dia_fee', 0) + data_get($priceRulesAmt, 'discount', 0))) }}
                        </div>
                    </div>
                    
                    @if (data_get($priceRulesAmt, 'discount', 0) > 0)
                        <div class="form-group row">
                            <label class="col-lg-4 control-label"><strong>Usage - Discount</strong></label>
                            <div class="col-lg-8 control-label">
                                {{ data_get($priceRulesAmt, 'discount') }}
                            </div>
                        </div>
                    @endif
                    
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Day Miles:</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($orderDepositRule, 'miles', '') }}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Insurance Type:</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ $commonService->getInsurancePayer(data_get($orderDepositRule, 'insurance_payer', '')) }}
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Insurance Rate:</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($orderDepositRule, 'insurance', '') }}
                        </div>
                    </div>

                    @if (!empty($rentalOpt))
                        <div class="form-group row">
                            <label class="col-lg-4 control-label"><strong>Usage Tier:</strong></label>
                            <div class="col-lg-8 control-label">
                                <ul class="no-padding">
                                    @foreach ($rentalOpt as $val)
                                        <li>
                                            <span>
                                                After 
                                                @if (data_get($val, 'after_day_date'))
                                                    {{ date('m/d/Y', strtotime(data_get($val, 'after_day_date'))) }}
                                                @else
                                                    {{ date('m/d/Y', strtotime((data_get($orderDepositRule, 'start_datetime', now())) . " +" . data_get($val, 'after_day', 0) . " days")) }}
                                                @endif
                                            </span> : <span>$ {{ data_get($val, 'amount') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <div class="form-group row">
                        <label class="col-lg-4 control-label"><strong>Deposit:</strong></label>
                        <div class="col-lg-8 control-label">
                            {{ data_get($orderDepositRule, 'deposit_amt', '') }}
                        </div>
                    </div>

                    @if (!empty($depositOpt))
                        <div class="form-group row">
                            <label class="col-lg-4 control-label"><strong>Deposit Schedules:</strong></label>
                            <div class="col-lg-8 control-label">
                                <ul class="no-padding">
                                    @foreach ($depositOpt as $val)
                                        <li>
                                            <span>{{ data_get($val, 'after_day_date') }}</span> : <span>$ {{ data_get($val, 'amount') }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                    
                    @if (!empty($durationOpt))
                        <div class="form-group row">
                            <label class="col-lg-4 control-label"><strong>Renewal Schedules:</strong></label>
                            <div class="col-lg-8 control-label">
                                <ul class="no-padding">
                                    <li>
                                        <span>{{ $startDate }} To {{ $endDate }}</span> : <span> {{ $days }} Days</span>
                                    </li>
                                    @foreach ($durationOpt as $val)
                                        <li>
                                            <span>After {{ data_get($val, 'after_date') }}</span> : <span> {{ data_get($val, 'duration') }} Days</span>
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