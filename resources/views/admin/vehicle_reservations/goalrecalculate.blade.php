@extends('admin.layouts.app')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Recalculate </span> - Goal
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="row">

                <div class="col-lg-6">
                    <form method="POST" action="#" name="VehicleOfferForm" id="VehicleOfferForm" class="form-horizontal">
                        @csrf

                        <div class="col-lg-12">
                            <legend class="text-size-large text-bold">1. Vehicle</legend>

                            <div class="form-group">
                                <label class="col-lg-4 control-label">
                                    Selling Price:<span class="requiredField">*</span>
                                </label>
                                <div class="col-lg-7">
                                    <input type="text" name="VehicleOffer[totalcost]" maxlength="16"
                                        class="required form-control number"
                                        value="{{ data_get($orderDepositRule, 'totalcost') }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-4 control-label">
                                    Goal:<span class="requiredField">*</span>
                                </label>
                                <div class="col-lg-7">
                                    <select name="VehicleOffer[goal]" class="required form-control" style="width:100%;">
                                        <option value=""></option>
                                        @foreach(['custom' => 'Custom', '20' => '20%', '30' => '30%', '40' => '40%', '50' => '50%', '60' => '60%', '70' => '70%', '80' => '80%', '90' => '90%', '100' => '100%'] as $key => $label)
                                            <option value="{{ $key }}" @selected(data_get($orderDepositRule, 'goal') == $key)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-4 control-label">
                                    Total Down Payment:
                                </label>
                                <div class="col-lg-7">
                                    <input type="text" name="VehicleOffer[downpayment]" maxlength="16"
                                        class="required form-control number" readonly
                                        value="{{ data_get($orderDepositRule, 'downpayment') }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-4 control-label">
                                    Target Program Length (Days):<span class="requiredField">*</span>
                                </label>
                                <div class="col-lg-7">
                                    <input type="text" name="VehicleOffer[target_days]" maxlength="5"
                                        class="required form-control digit"
                                        value="{{ data_get($orderDepositRule, 'num_of_days') }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-4 control-label">
                                    Choose Miles Per Month:<span class="requiredField">*</span>
                                </label>
                                <div class="col-lg-7">
                                    <select name="VehicleOffer[miles]" class="form-control required">
                                        @foreach(data_get($vehicles, 'miles_options', []) as $key => $value)
                                            <option value="{{ $key }}" @selected(data_get($orderDepositRule, 'miles') == $key)>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group hidden">
                                <input type="hidden" name="VehicleOffer[days]" class="form-control digit required" readonly
                                    value="{{ data_get($orderDepositRule, 'days') }}">
                                <input type="hidden" name="VehicleOffer[insurance]"
                                    class="ignore form-control number required" readonly
                                    value="{{ data_get($orderDepositRule, 'insurance') }}">
                                <input type="hidden" name="VehicleOffer[emf]" class="ignore form-control number required"
                                    readonly value="{{ data_get($orderDepositRule, 'emf') }}">
                                <input type="hidden" name="VehicleOffer[program_fee]"
                                    class="ignore form-control number required" readonly
                                    value="{{ data_get($orderDepositRule, 'program_fee') }}">
                                <input type="hidden" name="VehicleOffer[total_insurance]"
                                    class="ignore form-control number required" readonly
                                    value="{{ data_get($orderDepositRule, 'total_insurance') }}">
                                <input type="hidden" name="VehicleOffer[total_program_cost]"
                                    class="ignore form-control number required" readonly
                                    value="{{ data_get($orderDepositRule, 'total_program_cost') }}">
                                <input type="hidden" name="VehicleOffer[vehicle_id]"
                                    class="ignore form-control number required" readonly
                                    value="{{ data_get($vehicles, 'id') }}">
                            </div>
                        </div>

                        <div class="col-lg-12" id="panelbody"
                            rel-rental="{{ is_array(data_get($orderDepositRule, 'rent_opt')) ? count(data_get($orderDepositRule, 'rent_opt')) : 1 }}"
                            rel-deposit="{{ is_array(data_get($orderDepositRule, 'deposit_opt')) ? count(data_get($orderDepositRule, 'deposit_opt')) : 1 }}"
                            rel-initialfee="{{ is_array(data_get($orderDepositRule, 'initial_fee_opt')) ? count(data_get($orderDepositRule, 'initial_fee_opt')) : 1 }}"
                            rel-duration="{{ is_array(data_get($orderDepositRule, 'duration_opt')) ? count(data_get($orderDepositRule, 'duration_opt')) : 1 }}">

                            <legend class="text-size-large text-bold">2. Rental Offer</legend>

                            <div class="form-group">
                                <label class="col-lg-4 control-label">
                                    Pricing Type:<span class="requiredField">*</span>
                                </label>
                                <div class="col-lg-7">
                                    <select name="VehicleOffer[fare_type]" class="required form-control"
                                        style="width:100%;">
                                        <option value="D" @selected(data_get($orderDepositRule, 'fare_type') == 'D')>
                                            Dynamic
                                        </option>
                                        <option value="S" @selected(data_get($orderDepositRule, 'fare_type') == 'S')>
                                            Static
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-4 control-label">
                                    Day Rent:<span class="requiredField">*</span>
                                </label>
                                <div class="col-lg-7">
                                    <input type="text" name="VehicleOffer[day_rent]" class="form-control number"
                                        value="{{ data_get($orderDepositRule, 'rental') }}">
                                </div>
                            </div>

                            @if(data_get($vehicleReservation, 'initial_discount', 0) > 0)
                                <div class="form-group">
                                    <label class="col-lg-4 control-label">
                                        Promo Discount:
                                    </label>
                                    <div class="col-lg-7">
                                        ${{ data_get($vehicleReservation, 'initial_discount') }}
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-4 control-label">
                                        Clear Promo:
                                    </label>
                                    <div class="col-lg-7">
                                        <input type="checkbox" name="VehicleOffer[clear_promo]" id="VehicleOfferClearPromo">
                                    </div>
                                </div>
                            @endif

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Deposit Amount:</label>
                                <div class="col-lg-8">
                                    <input type="text" name="VehicleOffer[deposit_amt]" class="form-control"
                                        placeholder="Deposit" value="{{ data_get($orderDepositRule, 'deposit_amt') }}">
                                </div>
                            </div>

                            <div id="deposit_opt">
                                @php
                                    $depositOpt = data_get($orderDepositRule, 'deposit_opt', []);
                                @endphp

                                @if(is_array($depositOpt) && count($depositOpt) > 0)
                                    @foreach ($depositOpt as $val)
                                        @php
                                            $i = $loop->iteration; 
                                        @endphp
                                        <div class="form-group" id="ele-{{ $i }}">
                                            <label class="col-lg-2 control-label">&nbsp;</label>
                                            <div class="col-lg-2 control-label">After Days</div>
                                            <div class="col-lg-1 controllabel">
                                                <i class="icon-calendar3 icon-2x"
                                                    defaultViewDate="{{ date('m/d/Y', strtotime(data_get($orderDepositRule, 'start_datetime') . ' +' . data_get($val, 'after_day', 0) . ' days')) }}"></i>
                                            </div>
                                            <div class="col-lg-3 calwrap">
                                                <input type="text" name="VehicleOffer[deposit_opt][{{ $i }}][after_day_date]"
                                                    class="calendar form-control" placeholder="days"
                                                    value="{{ !empty(data_get($val, 'after_day_date')) ? date('m/d/Y', strtotime(data_get($val, 'after_day_date'))) : date('m/d/Y', strtotime(data_get($orderDepositRule, 'start_datetime') . ' +' . data_get($val, 'after_day', 0) . ' days')) }}"
                                                    data-date-start-date="{{ (!empty(data_get($val, 'after_day_date')) && strtotime(data_get($val, 'after_day_date')) > time()) ? date('m/d/Y', strtotime(data_get($val, 'after_day_date'))) : (!empty(data_get($val, 'after_day')) ? date('m/d/Y', strtotime(data_get($orderDepositRule, 'start_datetime') . ' +' . data_get($val, 'after_day') . ' days')) : date('m/d/Y', strtotime('+1 day'))) }}">
                                            </div>
                                            <div class="col-lg-1">Amount</div>
                                            <div class="col-lg-2">
                                                <input type="text" name="VehicleOffer[deposit_opt][{{ $i }}][amount]"
                                                    class="form-control" placeholder="amount"
                                                    value="{{ data_get($val, 'amount') }}">
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="form-group" id="ele-1">
                                        <label class="col-lg-2 control-label">&nbsp;</label>
                                        <div class="col-lg-2 control-label">After Days</div>
                                        <div class="col-lg-1 controllabel"><i class="icon-calendar3 icon-2x"></i></div>
                                        <div class="col-lg-3 calwrap">
                                            <input type="text" name="VehicleOffer[deposit_opt][1][after_day_date]"
                                                class="calendar form-control" placeholder="days" value=""
                                                data-date-start-date="{{ date('m/d/Y', strtotime('+1 day')) }}">
                                        </div>
                                        <div class="col-lg-1">Amount</div>
                                        <div class="col-lg-2">
                                            <input type="text" name="VehicleOffer[deposit_opt][1][amount]" class="form-control"
                                                placeholder="amount" value="{{ old('VehicleOffer.deposit_opt.1.amount') }}">
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Scheduled Payments:</label>
                                <div class="col-lg-8">
                                    <input type="text" name="VehicleOffer[initial_fee]" class="form-control"
                                        placeholder="Initial Fee"
                                        value="{{ data_get($orderDepositRule, 'total_initial_fee') }}">
                                </div>
                            </div>

                            <div id="initialfee_opt">
                                @php
                                    $initialFeeOpt = data_get($orderDepositRule, 'initial_fee_opt', []); 
                                @endphp
                                @if(is_array($initialFeeOpt) && count($initialFeeOpt) > 0)
                                    @foreach ($initialFeeOpt as $val)
                                        @php
                                            $i = $loop->iteration; 
                                        @endphp
                                        <div class="form-group" id="ele-{{ $i }}">
                                            <label class="col-lg-2 control-label">&nbsp;</label>
                                            <div class="col-lg-2 control-label">After Days</div>
                                            <div class="col-lg-1 controllabel">
                                                <i class="icon-calendar3 icon-2x"
                                                    defaultViewDate="{{ date('m/d/Y', strtotime(data_get($orderDepositRule, 'start_datetime') . ' +' . data_get($val, 'after_day', 0) . ' days')) }}"></i>
                                            </div>
                                            <div class="col-lg-3 calwrap">
                                                <input type="text" name="VehicleOffer[initial_fee_opt][{{ $i }}][after_day_date]"
                                                    class="calendar form-control" placeholder="days"
                                                    value="{{ !empty(data_get($val, 'after_day_date')) ? date('m/d/Y', strtotime(data_get($val, 'after_day_date'))) : date('m/d/Y', strtotime(data_get($orderDepositRule, 'start_datetime') . ' +' . data_get($val, 'after_day', 0) . ' days')) }}"
                                                    data-date-start-date="{{ (!empty(data_get($val, 'after_day_date')) && strtotime(data_get($val, 'after_day_date')) > time()) ? date('m/d/Y', strtotime(data_get($val, 'after_day_date'))) : (!empty(data_get($val, 'after_day')) ? date('m/d/Y', strtotime(data_get($orderDepositRule, 'start_datetime') . ' +' . data_get($val, 'after_day') . ' days')) : date('m/d/Y', strtotime('+1 day'))) }}">
                                            </div>
                                            <div class="col-lg-1">Amount</div>
                                            <div class="col-lg-2">
                                                <input type="text" name="VehicleOffer[initial_fee_opt][{{ $i }}][amount]"
                                                    class="form-control" placeholder="amount"
                                                    value="{{ data_get($val, 'amount') }}">
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="form-group" id="ele-1">
                                        <label class="col-lg-2 control-label">&nbsp;</label>
                                        <div class="col-lg-2 control-label">After Days</div>
                                        <div class="col-lg-1 controllabel"><i class="icon-calendar3 icon-2x"></i></div>
                                        <div class="col-lg-3 calwrap">
                                            <input type="text" name="VehicleOffer[initial_fee_opt][1][after_day_date]"
                                                class="calendar form-control" placeholder="days" value=""
                                                data-date-start-date="{{ date('m/d/Y', strtotime('+1 day')) }}">
                                        </div>
                                        <div class="col-lg-1">Amount</div>
                                        <div class="col-lg-2">
                                            <input type="text" name="VehicleOffer[initial_fee_opt][1][amount]"
                                                class="form-control" placeholder="amount"
                                                value="{{ old('VehicleOffer.initial_fee_opt.1.amount') }}">
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <div class="form-group">
                                <label class="col-lg-2 control-label">Calculations</label>
                                <div class="col-lg-6" id="calculations">
                                    @if($orderDepositRule)
                                        <ul>
                                            <li>
                                                <strong>Total Program Cost:</strong>
                                                {{ data_get($orderDepositRule, 'total_program_cost') }}
                                            </li>
                                            <li>
                                                <strong>Program Fee:</strong> {{ data_get($orderDepositRule, 'program_fee') }}
                                            </li>
                                            <li>
                                                <strong>Insurance Cost To Driver:</strong>
                                                {{ data_get($orderDepositRule, 'total_insurance') }}
                                            </li>
                                            <li>
                                                <strong>Day Rent:</strong> {{ data_get($orderDepositRule, 'rental') }}
                                            </li>
                                            <li>
                                                <strong>Day EMF:</strong> {{ data_get($orderDepositRule, 'emf') }}
                                            </li>
                                            <li>
                                                <strong>Day Insurance:</strong> {{ data_get($orderDepositRule, 'insurance') }}
                                            </li>
                                            <li>
                                                <strong>Day Miles:</strong>
                                                {{ ceil(data_get($orderDepositRule, 'miles', 0) * 30) }}
                                            </li>
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12">
                            <div class="form-group">
                                <button type="button" class="btn left-margin btn-large"
                                    onClick="vehicleReservationCalculateFareMatrix()">
                                    Re-Calculate
                                </button>
                                <button type="button" id="saveGoalRecalculation" class="btn btn-primary" disabled
                                    onClick="vehicleReservationSaveRecalculation()">
                                    Update
                                </button>
                            </div>
                        </div>

                        <input type="hidden" name="VehicleOffer[id]" value="{{ data_get($orderDepositRule, 'id') }}">
                        <input type="hidden" name="VehicleOffer[tempdatetime]"
                            value="{{ date('m/d/Y', strtotime('+1 day')) }}">
                        <input type="hidden" name="VehicleOffer[json]" value="">
                        <input type="hidden" name="VehicleOffer[renter_id]"
                            value="{{ data_get($vehicleReservation, 'renter_id') }}">
                    </form>
                </div>

                <div class="col-lg-6">
                    <form method="POST" action="{{ url('admin/vehicle_reservations/savemanualcalculation') }}"
                        name="VehicleOfferEditForm" id="VehicleOfferEditForm" class="form-horizontal">
                        @csrf

                        <legend class="text-size-large text-bold">Or Edit Calculations Manually</legend>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Miles Per Day:<span class="requiredField">*</span>
                            </label>
                            <div class="col-lg-7">
                                <input type="text" name="VehicleOffer[miles]" class="form-control required"
                                    value="{{ data_get($orderDepositRule, 'miles') }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Insurance Per Day:<span class="requiredField">*</span>
                            </label>
                            <div class="col-lg-7">
                                <input type="text" name="VehicleOffer[insurance]" class="form-control required"
                                    value="{{ data_get($orderDepositRule, 'insurance') }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                EMF:<span class="requiredField">*</span>
                            </label>
                            <div class="col-lg-7">
                                <input type="text" name="VehicleOffer[emf]" class="form-control required"
                                    value="{{ data_get($orderDepositRule, 'emf') }}">
                            </div>
                        </div>

                        @foreach (data_get($orderDepositRule, 'calculation', []) as $key => $calculate)
                            @if (is_array($calculate) || str_contains($key, '_opt'))
                                @continue
                            @endif
                            <div class="form-group">
                                <label class="col-lg-4 control-label">
                                    {{ ucwords(str_replace('_', ' ', $key)) }}:<span class="requiredField">*</span>
                                </label>
                                <div class="col-lg-7">
                                    <input type="text" name="VehicleOffer[calculation][{{ $key }}]"
                                        class="required form-control" value="{{ $calculate }}">
                                </div>
                            </div>
                        @endforeach

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Manually Update & Save</button>
                        </div>

                        <input type="hidden" name="VehicleOffer[id]" value="{{ data_get($orderDepositRule, 'id') }}">
                    </form>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">

        jQuery(document).ready(function () {

            jQuery(".start_datetime").datetimepicker();

            jQuery("#VehicleOfferForm").validate({
                ignore: 'input[type=hidden], .select2-input, .select2-focusser'
            });

            jQuery("#VehicleOfferClearPromo").change(function () {
                if (jQuery(this).is(':checked')) {
                    var conf = confirm("Please note that user promo will also be cleared if you check this checkbox. Are you sure to proceed?");
                    if (!conf) {
                        jQuery(this).prop('checked', false);
                    }
                }
            });

        });

    </script>
    <script src="{{ legacy_asset('js/admin_booking.js') }}"></script>
@endpush