@extends('admin.layouts.app')

@php
    $title ??= 'Add';
    $offer ??= [];
    $timezone ??= config('app.timezone', 'UTC');
    $startDatetime = !empty(data_get($offer, 'start_datetime'))
        ? \Carbon\Carbon::parse(data_get($offer, 'start_datetime'))->setTimezone($timezone)->format('m/d/Y h:i A')
        : \Carbon\Carbon::now()->addDay()->format('m/d/Y h:i A');
    $rent_opt = data_get($offer, 'rent_opt', []);
    $deposit_opt = data_get($offer, 'deposit_opt', []);
    $initial_fee_opt = data_get($offer, 'initial_fee_opt', []);
    $duration_opt = data_get($offer, 'duration_opt', []);
    $financingOpts = $commonService->financingOptions(false);
@endphp

@section('title', ($title . " Vehicle Offer"))

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ $title }}</span> Vehicle Offer
                </h4>
            </div>
        </div>
    </div>

    <div class="row ">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form method="POST"
                action="{{ url('admin/vehicle_offers/add' . (!empty(data_get($offer, 'id', '')) ? '/' . base64_encode(data_get($offer, 'id', '')) : ''))}}"
                id="VehicleOfferForm" name="VehicleOfferForm" class="form-horizontal">
                @csrf

                <input type="hidden" name="VehicleOffer[id]" value="{{ data_get($offer, 'id', '') }}">
                <input type="hidden" id="VehicleOfferTempdatetime" value="{{ date('m/d/Y', strtotime('+1 day')) }}">

                <div class="col-lg-6">
                    <legend class="text-size-large text-bold">1. Vehicle</legend>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">PTO/Misc</label>
                        <div class="col-lg-7">
                            <select name="VehicleOffer[pto]" id="VehicleOfferPto" class="form-control">
                                <option value="1" @selected((data_get($offer, 'pto', '')) == 1)>PTO</option>
                                <option value="0" @selected(data_get($offer, 'pto', '') == 0)>Misc</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Financing :</label>
                        <div class="col-lg-7">
                            <select name="VehicleOffer[financing]" id="VehicleOfferFinancing" class="form-control">
                                @foreach ($financingOpts as $key => $value)
                                    <option value="{{ $value }}" @selected(data_get($offer, 'financing', '') == $value)>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Vehicle :<span class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <input type="text" id="VehicleOfferVehicleId" name="VehicleOffer[vehicle_id]" class="required"
                                style="width:100%;" value="{{ data_get($offer, 'vehicle_id', '') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Selling Price:<span class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <input type="text" id="VehicleOfferTotalcost" name="VehicleOffer[totalcost]"
                                class="required form-control number" value="{{ data_get($offer, 'totalcost', '') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Goal:<span class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <select name="VehicleOffer[goal]" id="VehicleOfferGoal" class="required form-control">
                                <option value="">Select..</option>
                                <option value="custom" @selected(data_get($offer, 'goal', '') == 'custom')>Custom</option>
                                @foreach([20, 30, 40, 50, 60, 70, 80, 90, 100] as $g)
                                    <option value="{{ $g }}" @selected(data_get($offer, 'goal', '') == $g)>{{ $g }}%</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Total Down Payment:</label>
                        <div class="col-lg-7">
                            <input type="text" id="VehicleOfferDownpayment" name="VehicleOffer[downpayment]"
                                class="required form-control number" value="{{ data_get($offer, 'downpayment', '') }}"
                                readonly="true">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Target Program Length (Days):<span
                                class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <input type="text" id="VehicleOfferTargetDays" name="VehicleOffer[target_days]"
                                class="required form-control digit" value="{{ data_get($offer, 'target_days', '') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Driver Phone:<span class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <input type="text" id="VehicleOfferDriverPhone" name="VehicleOffer[driver_phone]"
                                class="required form-control" value="{{ data_get($offer, 'driver_phone', '') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Start DateTime:<span class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <input type="text" name="VehicleOffer[start_datetime]"
                                class="required form-control start_datetime" value="{{ $startDatetime }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Status:</label>
                        <div class="col-lg-7">
                            <select name="VehicleOffer[status]" class="form-control">
                                <option value="0" @selected(data_get($offer, 'status', '') == 0)>New</option>
                                <option value="1" @selected(data_get($offer, 'status', '') == 1)>Accepted</option>
                                <option value="2" @selected(data_get($offer, 'status', '') == 2)>Canceled</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Choose Miles Per Month:<span
                                class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <select name="VehicleOffer[miles]" id="VehicleOfferMiles" class="form-control required">
                                <option value="">Select..</option>
                                @if(!empty(data_get($offer, 'miles', '')))
                                    <option value="{{ data_get($offer, 'miles', '') }}" selected>
                                        {{ data_get($offer, 'miles', '') }}
                                    </option>
                                @endif
                            </select>
                        </div>
                    </div>

                    <div class="hidden">
                        <input type="hidden" name="VehicleOffer[days]" id="VehicleOfferDays"
                            value="{{ data_get($offer, 'days', '') }}">
                        <input type="hidden" name="VehicleOffer[insurance]" id="VehicleOfferInsurance"
                            value="{{ data_get($offer, 'insurance', '') }}">
                        <input type="hidden" name="VehicleOffer[emf]" id="VehicleOfferEmf"
                            value="{{ data_get($offer, 'emf', '') }}">
                        <input type="hidden" name="VehicleOffer[program_fee]" id="VehicleOfferProgramFee"
                            value="{{ data_get($offer, 'program_fee', '') }}">
                        <input type="hidden" name="VehicleOffer[total_insurance]" id="VehicleOfferTotalInsurance"
                            value="{{ data_get($offer, 'total_insurance', '') }}">
                        <input type="hidden" name="VehicleOffer[total_program_cost]" id="VehicleOfferTotalProgramCost"
                            value="{{ data_get($offer, 'total_program_cost', '') }}">
                        <input type="hidden" name="VehicleOffer[equityshare]" id="VehicleOfferEquityshare"
                            value="{{ data_get($offer, 'equityshare', '') }}">
                        <input type="hidden" name="VehicleOffer[write_down_allocation]" id="VehicleOfferWriteDownAllocation"
                            value="{{ data_get($offer, 'write_down_allocation', '') }}">
                        <input type="hidden" name="VehicleOffer[finance_allocation]" id="VehicleOfferFinanceAllocation"
                            value="{{ data_get($offer, 'finance_allocation', '') }}">
                        <input type="hidden" name="VehicleOffer[maintenance_allocation]"
                            id="VehicleOfferMaintenanceAllocation"
                            value="{{ data_get($offer, 'maintenance_allocation', '') }}">
                        <input type="hidden" name="VehicleOffer[depreciation_rate]" id="VehicleOfferDepreciationRate"
                            value="{{ data_get($offer, 'depreciation_rate', '') }}">
                        <input type="hidden" name="VehicleOffer[disposition_fee]" id="VehicleOfferDispositionFee"
                            value="{{ data_get($offer, 'disposition_fee', '') }}">
                        <input type="hidden" name="VehicleOffer[calculation]" id="VehicleOfferCalculation"
                            value="{{ data_get($offer, 'calculation', '') }}">
                    </div>
                </div>

                <div class="col-lg-6" id="panelbody" data-rel-rental="{{ count($rent_opt) ?: 1 }}"
                    data-rel-deposit="{{ count($deposit_opt) ?: 1 }}"
                    data-rel-initialfee="{{ count($initial_fee_opt) ?: 1 }}"
                    data-rel-duration="{{ count($duration_opt) ?: 1 }}">

                    <legend class="text-size-large text-bold">2. Rental Offer</legend>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Duration:<span class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <select name="VehicleOffer[duration]" id="VehicleOfferDuration" class="form-control">
                                @foreach([1, 2, 3, 4, 5, 6, 7, 14, 30] as $d)
                                    <option value="{{ $d }}" @selected((data_get($offer, 'duration', 7)) == $d)>
                                        {{ $d }} day{{ $d > 1 ? 's' : '' }}
                                    </option>
                                @endforeach
                                <option value="custom" @selected(!in_array(data_get($offer, 'duration', 7), [1, 2, 3, 4, 5, 6, 7, 14, 30]))>
                                    Custom
                                </option>
                            </select>
                            <input type="text" name="VehicleOffer[duration1]" id="VehicleOfferDuration1"
                                class="form-control hidden digit" placeholder="Enter days value"
                                value="{{ !in_array(data_get($offer, 'duration', 7), [1, 2, 3, 4, 5, 6, 7, 14, 30]) ? data_get($offer, 'duration', '') : '' }}">
                            <em>Please note new duration will be only applied on auto renew event</em>
                        </div>
                        <div class="col-lg-1">
                            <a href="javascript:void(0)" onclick="duration_opt(true)">
                                <i class="icon-plus-circle2 icon-2x"></i>
                            </a>
                        </div>
                    </div>

                    <div id="duration_opt">
                        @foreach($duration_opt as $idx => $val)
                            <div class="form-group" id="ele-duration-{{ $idx + 1 }}">
                                <label class="col-lg-4 control-label">Duration change After:</label>
                                <div class="col-lg-3">
                                    <input name="VehicleOffer[duration_opt][{{ $idx + 1 }}][after_date]" type="text"
                                        class="date form-control" value="{{ $val['after_date'] ?? '' }}">
                                </div>
                                <label class="col-lg-1 control-label">Duration:</label>
                                <div class="col-lg-3">
                                    <select name="VehicleOffer[duration_opt][{{ $idx + 1 }}][duration]" class="form-control">
                                        @foreach([1, 2, 3, 4, 5, 6, 7, 14, 30] as $d)
                                            <option value="{{ $d }}" @selected(($val['duration'] ?? 7) == $d)>
                                                {{ $d }} day{{ $d > 1 ? 's' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-1">
                                    <a href="javascript:void(0)" onclick="duration_opt(false)">
                                        <i class="icon-minus-circle2 icon-2x"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Pricing Type:<span class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <select name="VehicleOffer[fare_type]" id="VehicleOfferFareType" class="required form-control">
                                <option value="D" @selected(data_get($offer, 'fare_type', '') == 'D')>Dynamic</option>
                                <option value="S" @selected(data_get($offer, 'fare_type', '') == 'S')>Static</option>
                                <option value="L" @selected(data_get($offer, 'fare_type', '') == 'L')>Lease Plus Pricing
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Day Rent:<span class="text-danger">*</span></label>
                        <div class="col-lg-7">
                            <input type="text" id="VehicleOfferDayRent" name="VehicleOffer[day_rent]"
                                class="form-control number" value="{{ data_get($offer, 'day_rent', '') }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">Deposit Amount:</label>
                        <div class="col-lg-8">
                            <input type="text" name="VehicleOffer[deposit_amt]" id="VehicleOfferDepositAmt"
                                class="form-control" placeholder="Deposit"
                                value="{{ data_get($offer, 'deposit_amt', '') }}">
                        </div>
                    </div>

                    <div id="deposit_opt">
                        @foreach($deposit_opt as $idx => $val)
                            <div class="form-group" id="ele-deposit-{{ $idx + 1 }}">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-2 control-label">After Days</div>
                                <div class="col-lg-1 controllabel"><i class="icon-calendar3 icon-2x"></i></div>
                                <div class="col-lg-3 calwrap">
                                    <input name="VehicleOffer[deposit_opt][{{ $idx + 1 }}][after_day_date]" type="text"
                                        class="calendar form-control" value="{{ $val['after_day_date'] ?? '' }}">
                                </div>
                                <div class="col-lg-1">Amount</div>
                                <div class="col-lg-2">
                                    <input name="VehicleOffer[deposit_opt][{{ $idx + 1 }}][amount]" type="text"
                                        class="form-control" value="{{ $val['amount'] ?? '' }}">
                                </div>
                                <div class="col-lg-1">
                                    @if($loop->first)
                                        <a href="javascript:void(0)" onclick="deposit_opt(true)">
                                            <i class="icon-plus-circle2 icon-2x"></i>
                                        </a>
                                    @else
                                        <a href="javascript:void(0)" onclick="deposit_opt(false)">
                                            <i class="icon-minus-circle2 icon-2x"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if(empty($deposit_opt))
                            <div class="form-group" id="ele-deposit-1">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-2 control-label">After Days</div>
                                <div class="col-lg-1 controllabel"><i class="icon-calendar3 icon-2x"></i></div>
                                <div class="col-lg-3 calwrap">
                                    <input name="VehicleOffer[deposit_opt][1][after_day_date]" type="text"
                                        class="calendar form-control" value="">
                                </div>
                                <div class="col-lg-1">Amount</div>
                                <div class="col-lg-2">
                                    <input name="VehicleOffer[deposit_opt][1][amount]" type="text" class="form-control"
                                        value="0">
                                </div>
                                <div class="col-lg-1">
                                    <a href="javascript:void(0)" onclick="deposit_opt(true)">
                                        <i class="icon-plus-circle2 icon-2x"></i>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label class="col-lg-3 control-label">Scheduled Payments:</label>
                        <div class="col-lg-8">
                            <input type="text" name="VehicleOffer[initial_fee]" id="VehicleOfferInitialFee"
                                class="form-control" placeholder="Initial Fee"
                                value="{{ data_get($offer, 'initial_fee', '') }}">
                        </div>
                    </div>

                    <div id="initialfee_opt">
                        @foreach($initial_fee_opt as $idx => $val)
                            <div class="form-group" id="ele-initial-{{ $idx + 1 }}">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-2 control-label">After Days</div>
                                <div class="col-lg-1 controllabel"><i class="icon-calendar3 icon-2x"></i></div>
                                <div class="col-lg-3 calwrap">
                                    <input name="VehicleOffer[initial_fee_opt][{{ $idx + 1 }}][after_day_date]" type="text"
                                        class="calendar form-control" value="{{ $val['after_day_date'] ?? '' }}">
                                </div>
                                <div class="col-lg-1">Amount</div>
                                <div class="col-lg-2">
                                    <input name="VehicleOffer[initial_fee_opt][{{ $idx + 1 }}][amount]" type="text"
                                        class="form-control" value="{{ $val['amount'] ?? '' }}">
                                </div>
                                <div class="col-lg-1">
                                    @if($loop->first)
                                        <a href="javascript:void(0)" onclick="initialfee_opt(true)">
                                            <i class="icon-plus-circle2 icon-2x"></i>
                                        </a>
                                    @else
                                        <a href="javascript:void(0)" onclick="initialfee_opt(false)">
                                            <i class="icon-minus-circle2 icon-2x"></i>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if(empty($initial_fee_opt))
                            <div class="form-group" id="ele-initial-1">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-2 control-label">After Days</div>
                                <div class="col-lg-1 controllabel"><i class="icon-calendar3 icon-2x"></i></div>
                                <div class="col-lg-3 calwrap">
                                    <input name="VehicleOffer[initial_fee_opt][1][after_day_date]" type="text"
                                        class="calendar form-control" value="">
                                </div>
                                <div class="col-lg-1">Amount</div>
                                <div class="col-lg-2">
                                    <input name="VehicleOffer[initial_fee_opt][1][amount]" type="text" class="form-control"
                                        value="0">
                                </div>
                                <div class="col-lg-1">
                                    <a href="javascript:void(0)" onclick="initialfee_opt(true)">
                                        <i class="icon-plus-circle2 icon-2x"></i>
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="form-group">
                        <label class="col-lg-2 control-label">Calculations</label>
                        <div class="col-lg-10" id="calculations">
                            @if (!empty(data_get($offer, 'id', '')) && data_get($offer, 'pto') == 1)
                                <ul>
                                    <li>
                                        <strong>Total Program Cost:</strong> {{ data_get($offer, 'total_program_cost') }}
                                    </li>
                                    <li>
                                        <strong>Program Fee:</strong> {{ data_get($offer, 'program_fee')}}
                                    </li>
                                    <li>
                                        <strong>Insurance Cost To Dealer:</strong> {{ data_get($offer, 'total_insurance') }}
                                    </li>
                                    <li>
                                        <strong>Day Rent:</strong> {{ data_get($offer, 'day_rent') }}
                                    </li>
                                    <li>
                                        <strong>Day EMF:</strong> {{ data_get($offer, 'emf') }}
                                    </li>
                                    <li>
                                        <strong>Day Insurance:</strong> {{ data_get($offer, 'insurance') }}
                                    </li>
                                    <li>
                                        <strong>Day Miles:</strong> {{ data_get($offer, 'miles') }}
                                    </li>
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-12">
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button type="button" id="calculateButton" class="btn btn-info" onclick="calculateFareMatrix()">
                                Calculate
                            </button>
                            <button type="submit" class="btn btn-primary" id="saveButton" disabled>
                                Save <i class="icon-database-insert position-right"></i>
                            </button>
                            <a href="{{ url('admin/vehicle_offers/index') }}" class="btn btn-default">
                                Cancel
                            </a>
                            <button type="button" class="btn btn-warning" onclick="qualifyCheckr()">
                                Qualify Driver <i class="glyphicon glyphicon-question-sign"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')

    <script src="{{ legacy_asset('js/select2.js') }}"></script>
    <script src="{{ legacy_asset('js/jquery.validate.js') }}"></script>
    <script src="{{ legacy_asset('js/admin_offers.js') }}"></script>

    <script type="text/javascript">
        function format(item) {
            return item.tag;
        }

        jQuery(document).ready(function () {
            $("#panelbody").attr('rel-rental', $("#panelbody").data('rel-rental'));
            $("#panelbody").attr('rel-deposit', $("#panelbody").data('rel-deposit'));
            $("#panelbody").attr('rel-initialfee', $("#panelbody").data('rel-initialfee'));
            $("#panelbody").attr('rel-duration', $("#panelbody").data('rel-duration'));

            jQuery(".start_datetime").datetimepicker();

            jQuery("#VehicleOfferForm").validate({
                ignore: 'input[type=hidden], .select2-input, .select2-focusser'
            });

            jQuery("#VehicleOfferVehicleId").select2({
                data: { results: {}, text: 'tag' },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Vehicle ",
                minimumInputLength: 1,
                ajax: {
                    url: "{{ url('admin/vehicle_offers/vehicleautocomplete') }}",
                    dataType: "json",
                    type: "GET",
                    data: function (params) { return { term: params }; },
                    processResults: function (data) {
                        return {
                            results: jQuery.map(data, function (item) {
                                return {
                                    tag: item.tag,
                                    id: item.id,
                                    msrp: item.msrp,
                                    miles_options: item.miles_options
                                };
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    var vehicle_id = "{{ data_get($offer, 'vehicle_id', '')}}";
                    var mile_opts = "{{ data_get($offer, 'miles', '')}}";
                    if (vehicle_id.length > 0) {
                        jQuery.ajax({
                            url: "{{ url('admin/vehicle_offers/vehicleautocomplete') }}",
                            dataType: "json",
                            type: "GET",
                            data: { "vehicle_id": vehicle_id },
                            success: function (res) {
                                var miles = '<option value="">Select..</option>';
                                var miles_options = res.miles_options;
                                $.each(miles_options, function (index, val) {
                                    miles += '<option value="' + index + '"' + (mile_opts == index ? " selected" : "") + '>' + val + '</option>';
                                });
                                $("#VehicleOfferMiles").html(miles);
                                callback(res);
                            }
                        });
                    }
                }
            });

            jQuery("#VehicleOfferVehicleId").on('change', function (e) {
                var data = $(this).select2('data');
                if (data) {
                    jQuery("#VehicleOfferTotalcost").val(data.msrp);
                    var miles = '<option value="">Select..</option>';
                    var miles_options = data.miles_options;
                    $.each(miles_options, function (index, val) {
                        miles += '<option value="' + index + '">' + val + '</option>';
                    });
                    $("#VehicleOfferMiles").html(miles);
                }
            });
        });

        @if (!empty(data_get($offer, 'id', '')) && data_get($offer, 'pto') == 0)
            jQuery(document).ready(function () {
                $("#VehicleOfferDayRent").attr("readonly", false);
                $("#VehicleOfferGoal").removeClass('required');
                $("#VehicleOfferDownpayment").removeClass('required');
                $("#VehicleOfferDays").removeClass('required');
            });
        @endif

        @if (!empty(data_get($offer, 'id', '')) && !in_array(data_get($offer, 'duration'), [1, 2, 3, 4, 5, 6, 7, 14, 30]))
            jQuery(document).ready(function () {
                $("#VehicleOfferDuration1").show();
                $("#VehicleOfferDuration1").removeClass('hidden');
                $("#VehicleOfferDuration1").addClass('required');
                $("#VehicleOfferDuration").val('custom');
                $("#VehicleOfferDuration1").val("{{ data_get($offer, 'duration', '') }}");
            });
        @endif

    </script>
@endpush