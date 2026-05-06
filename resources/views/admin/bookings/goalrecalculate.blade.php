@extends('admin.layouts.app')

@section('title', 'Recalculate Goal')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Recalculate </span> - Goal</h4>
        </div>
    </div>
</div>

<div class="row">
    @include('partials.flash')
</div>

<div class="panel">
    <div class="panel-body">
        <div class="row">
            <div class="col-lg-6">
                <form id="VehicleOfferForm" class="form-horizontal" method="POST">
                    @csrf
                    <input type="hidden" name="VehicleOffer[id]" value="{{ $depositRule->id }}">
                    <input type="hidden" name="VehicleOffer[vehicle_id]" value="{{ $vehicle->id ?? '' }}">
                    <input type="hidden" name="VehicleOffer[json]" id="VehicleOfferJson" value="">
                    
                    <div class="col-lg-12">
                        <legend class="text-size-large text-bold">1. Vehicle</legend>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Selling Price:<font class="requiredField">*</font></label>
                            <div class="col-lg-7">
                                <input type="text" name="VehicleOffer[totalcost]" class="required form-control number" value="{{ $depositRule->totalcost }}" maxlength="16">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Goal:<font class="requiredField">*</font></label>
                            <div class="col-lg-7">
                                <select name="VehicleOffer[goal]" class="required form-control select2">
                                    <option value=""></option>
                                    <option value="custom" {{ $depositRule->goal == 'custom' ? 'selected' : '' }}>Custom</option>
                                    @foreach(['20', '30', '40', '50', '60', '70', '80', '90', '100'] as $val)
                                        <option value="{{ $val }}" {{ $depositRule->goal == $val ? 'selected' : '' }}>{{ $val }}%</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Total Down Payment:</label>
                            <div class="col-lg-7">
                                <input type="text" name="VehicleOffer[downpayment]" class="required form-control number" value="{{ $depositRule->downpayment }}" readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Target Program Length (Days):<font class="requiredField">*</font></label>
                            <div class="col-lg-7">
                                <input type="text" name="VehicleOffer[target_days]" class="required form-control digit" value="{{ $depositRule->num_of_days }}" maxlength="5">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Choose Miles Per Month:<font class="requiredField">*</font></label>
                            <div class="col-lg-7">
                                <select name="VehicleOffer[miles]" class="form-control required">
                                    @foreach($milesOptions as $key => $val)
                                        <option value="{{ $key }}" {{ $depositRule->miles == $key ? 'selected' : '' }}>{{ $val }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <legend class="text-size-large text-bold">2. Rental Offer</legend>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Pricing Type:<font class="requiredField">*</font></label>
                            <div class="col-lg-7">
                                <select name="VehicleOffer[fare_type]" class="required form-control">
                                    <option value="D" {{ $depositRule->fare_type == 'D' ? 'selected' : '' }}>Dynamic</option>
                                    <option value="S" {{ $depositRule->fare_type == 'S' ? 'selected' : '' }}>Static</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Day Rent:<font class="requiredField">*</font></label>
                            <div class="col-lg-7">
                                <input type="text" name="VehicleOffer[day_rent]" class="form-control number" value="{{ $depositRule->rental }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Deposit Amount:</label>
                            <div class="col-lg-8">
                                <input type="text" name="VehicleOffer[deposit_amt]" class="form-control" placeholder="Deposit" value="{{ $depositRule->deposit_amt }}">
                            </div>
                        </div>

                        <div id="deposit_opt">
                            @php $i = 1; @endphp
                            @if(!empty($depositOpt))
                                @foreach($depositOpt as $val)
                                    <div class="form-group" id="ele-dep-{{ $i }}">
                                        <label class="col-lg-2 control-label">&nbsp;</label>
                                        <div class="col-lg-2 control-label">After Days</div>
                                        <div class="col-lg-1 controllabel"><i class="icon-calendar3 icon-2x"></i></div>
                                        <div class="col-lg-3 calwrap">
                                            <input type="text" name="VehicleOffer[deposit_opt][{{ $i }}][after_day_date]" class="calendar form-control" value="{{ !empty($val['after_day_date']) ? date('m/d/Y', strtotime($val['after_day_date'])) : '' }}">
                                        </div>
                                        <div class="col-lg-1">Amount</div>
                                        <div class="col-lg-2">
                                            <input type="text" name="VehicleOffer[deposit_opt][{{ $i }}][amount]" class="form-control" placeholder="amount" value="{{ $val['amount'] ?? '' }}">
                                        </div>
                                    </div>
                                    @php $i++; @endphp
                                @endforeach
                            @endif
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Scheduled Payments:</label>
                            <div class="col-lg-8">
                                <input type="text" name="VehicleOffer[initial_fee]" class="form-control" placeholder="Initial Fee" value="{{ $depositRule->total_initial_fee }}">
                            </div>
                        </div>

                        <div id="initialfee_opt">
                            @php $j = 1; @endphp
                            @if(!empty($initialFeeOpt))
                                @foreach($initialFeeOpt as $val)
                                    <div class="form-group" id="ele-ini-{{ $j }}">
                                        <label class="col-lg-2 control-label">&nbsp;</label>
                                        <div class="col-lg-2 control-label">After Days</div>
                                        <div class="col-lg-1 controllabel"><i class="icon-calendar3 icon-2x"></i></div>
                                        <div class="col-lg-3 calwrap">
                                            <input type="text" name="VehicleOffer[initial_fee_opt][{{ $j }}][after_day_date]" class="calendar form-control" value="{{ !empty($val['after_day_date']) ? date('m/d/Y', strtotime($val['after_day_date'])) : '' }}">
                                        </div>
                                        <div class="col-lg-1">Amount</div>
                                        <div class="col-lg-2">
                                            <input type="text" name="VehicleOffer[initial_fee_opt][{{ $j }}][amount]" class="form-control" placeholder="amount" value="{{ $val['amount'] ?? '' }}">
                                        </div>
                                    </div>
                                    @php $j++; @endphp
                                @endforeach
                            @endif
                        </div>

                        <div class="form-group">
                            <label class="col-lg-2 control-label">Calculations</label>
                            <div class="col-lg-6" id="calculations">
                                <ul>
                                    <li><strong>Total Program Cost:</strong> {{ $depositRule->total_program_cost }}</li>
                                    <li><strong>Program Fee:</strong> {{ $depositRule->program_fee }}</li>
                                    <li><strong>Insurance Cost To Driver:</strong> {{ $depositRule->total_insurance }}</li>
                                    <li><strong>Day Rent:</strong> {{ $depositRule->rental }}</li>
                                    <li><strong>Day EMF:</strong> {{ $depositRule->emf }}</li>
                                    <li><strong>Day Insurance:</strong> {{ $depositRule->insurance }}</li>
                                    <li><strong>Day Miles:</strong> {{ ceil(($depositRule->miles ?? 0) * 30) }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12">
                        <div class="form-group">
                            <button type="button" class="btn left-margin btn-large" onClick="calculateFareMatrix()" id="calculateButton">Calculate</button>
                            <button type="button" class="btn btn-primary" disabled onClick="saveRecalculation()" id="saveGoalRecalculation">Update</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-lg-6">
                <form action="{{ url('admin/bookings/savemanualcalculation') }}" method="POST" id="VehicleOfferEditForm" class="form-horizontal">
                    @csrf
                    <input type="hidden" name="VehicleOffer[id]" value="{{ $depositRule->id }}">
                    
                    <legend class="text-size-large text-bold">Or Edit Calculations Manually</legend>
                    
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Miles Per Day:<font class="requiredField">*</font></label>
                        <div class="col-lg-7">
                            <input type="text" name="VehicleOffer[miles]" class="form-control required" value="{{ $depositRule->miles }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">Insurance Per Day:<font class="requiredField">*</font></label>
                        <div class="col-lg-7">
                            <input type="text" name="VehicleOffer[insurance]" class="form-control required" value="{{ $depositRule->insurance }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-lg-4 control-label">EMF:<font class="requiredField">*</font></label>
                        <div class="col-lg-7">
                            <input type="text" name="VehicleOffer[emf]" class="form-control required" value="{{ $depositRule->emf }}">
                        </div>
                    </div>

                    @foreach($calculation as $key => $calculate)
                        @if(is_array($calculate) || str_contains($key, '_opt'))
                            @continue
                        @endif
                        <div class="form-group">
                            <label class="col-lg-4 control-label">{{ ucwords(str_replace('_', ' ', $key)) }}:<font class="requiredField">*</font></label>
                            <div class="col-lg-7">
                                <input type="text" name="VehicleOffer[calculation][{{ $key }}]" class="required form-control" value="{{ $calculate }}">
                            </div>
                        </div>
                    @endforeach

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update & Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script type="text/javascript">
        jQuery(document).ready(function() {
            jQuery(".calendar").datepicker({
                format: 'mm/dd/yyyy',
                autoclose: true
            });
            jQuery("#VehicleOfferForm").validate({
                ignore: 'input[type=hidden], .select2-input, .select2-focusser'
            });
        });
    </script>
    <script src="{{ asset('js/admin_booking.js') }}"></script>
@endpush
