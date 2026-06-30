@extends('admin.layouts.app')

@php
    $title ??= "View";
    $timezone ??= config('app.timezone', 'UTC');
    $offer ??= [];
@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ $title }}</span> - Offer
                </h4>
            </div>
        </div>
    </div>

    <div class="row ">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="row">
                <div class="col-lg-6">
                    <legend class="text-size-large text-bold">1. Vehicle</legend>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">PTO/Misc :</label>
                        <div class="col-lg-7 control-label">
                            {{ data_get($offer, 'pto') == 1 ? "PTO" : "Misc" }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Vehicle :</label>
                        <div class="col-lg-7 control-label">
                            {{ data_get($offer, 'vehicle.vehicle_name', '') }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Selling Price :</label>
                        <div class="col-lg-7 control-label">
                            {{ number_format(data_get($offer, 'totalcost', 0), 2) }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Goal :</label>
                        <div class="col-lg-7 control-label">
                            {{ data_get($offer, 'goal', 0) }} (%)
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Total Down Payment :</label>
                        <div class="col-lg-7 control-label">
                            {{ number_format(data_get($offer, 'downpayment', 0), 2) }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Target Program Length (Days):</label>
                        <div class="col-lg-7 control-label">
                            {{ data_get($offer, 'target_days', 0) }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Driver Phone :</label>
                        <div class="col-lg-7 control-label">
                            {{ data_get($offer, 'driver_phone', '') }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Start DateTime :</label>
                        <div class="col-lg-7 control-label">
                            {{ \Carbon\Carbon::parse(data_get($offer, 'start_datetime'))->setTimezone($timezone)->format('m/d/Y h:i A') }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Status :</label>
                        <div class="col-lg-7 control-label">
                            @if (data_get($offer, 'status') == 1)
                                Accepted
                            @elseif (data_get($offer, 'status') == 2)
                                Canceled
                            @else
                                New
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <legend class="text-size-large text-bold">2. Rental Offer</legend>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Duration :</label>
                        <div class="col-lg-7 control-label">
                            {{ data_get($offer, 'duration', 0) }} day(s)
                        </div>
                    </div>

                    @if(!empty(data_get($offer, 'duration_opt', [])))
                        @foreach(data_get($offer, 'duration_opt', []) as $idx => $val)
                            <div class="form-group row" id="ele-{{$idx}}">
                                <label class="col-lg-4 control-label">Duration change After :</label>
                                <div class="col-lg-3 control-label">{{ $val['after_date'] ?? '' }}</div>
                                <label class="col-lg-2 control-label">Duration :</label>
                                <div class="col-lg-2 control-label">{{ $val['duration'] ?? '' }} days</div>
                            </div>
                        @endforeach
                    @endif

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Pricing Type :</label>
                        <div class="col-lg-7 control-label">
                            {{ data_get($offer, 'fare_type') == 'D' ? "Dynamic" : "Static" }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Day Rent :</label>
                        <div class="col-lg-7 control-label">
                            {{ number_format((data_get($offer, 'day_rent', 0)), 2) }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Deposit Amount :</label>
                        <div class="col-lg-7 control-label">
                            {{ number_format(data_get($offer, 'deposit_amt', 0), 2) }}
                        </div>
                    </div>
                    <div id="deposit_opt">
                        @if(!empty(data_get($offer, 'deposit_opt', [])))
                            @foreach(data_get($offer, 'deposit_opt', []) as $idx => $val)
                                <div class="form-group row" id="ele-{{$idx}}">
                                    <label class="col-lg-2 control-label">&nbsp;</label>
                                    <div class="col-lg-2 control-label">After Days</div>
                                    <div class="col-lg-1 controllabel">
                                        <i class="icon-calendar3 icon-2x calendar"></i>
                                    </div>
                                    <div class="col-lg-3 calwrap">
                                        {{!empty($val['after_day_date']) ? $val['after_day_date'] : (!empty($val['after_day']) ? date('m/d/Y', strtotime($offer['VehicleOffer']['start_datetime'] . "+" . $val['after_day'] . " days")) : "")}}
                                    </div>
                                    <div class="col-lg-1">Amount</div>
                                    <div class="col-lg-2">
                                        {{$val['amount']}}
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="form-group row" id="ele-1">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-2 control-label">After Days</div>
                                <div class="col-lg-1 controllabel">
                                    <i class="icon-calendar3 icon-2x calendar"></i>
                                </div>
                                <div class="col-lg-3 calwrap">
                                    N/A
                                </div>
                                <div class="col-lg-1">Amount</div>
                                <div class="col-lg-2">
                                    N/A
                                </div>
                                <div class="col-lg-1"></div>
                            </div>
                        @endif
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Scheduled Payments :</label>
                        <div class="col-lg-7 control-label">
                            {{ number_format(data_get($offer, 'initial_fee', 0), 2) }}
                        </div>
                    </div>
                    <div id="initialfee_opt">
                        @if(!empty(data_get($offer, 'initial_fee_opt', [])))
                            @foreach(data_get($offer, 'initial_fee_opt', []) as $idx => $val)
                                <div class="form-group row" id="ele-{{ $idx }}">
                                    <label class="col-lg-2 control-label">&nbsp;</label>
                                    <div class="col-lg-2 control-label">After Days</div>
                                    <div class="col-lg-1 controllabel">
                                        <i class="icon-calendar3 icon-2x calendar"></i>
                                    </div>
                                    <div class="col-lg-3 calwrap">
                                        {{ !empty($val['after_day_date']) ? $val['after_day_date'] : (!empty($val['after_day']) ? date('m/d/Y', strtotime($offer['VehicleOffer']['start_datetime'] . "+" . $val['after_day'] . " days")) : "") }}
                                    </div>
                                    <div class="col-lg-1">Amount</div>
                                    <div class="col-lg-2">
                                        {{$val['amount']}}
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="form-group row" id="ele-1">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-2 control-label">After Days</div>
                                <div class="col-lg-1 controllabel">
                                    <i class="icon-calendar3 icon-2x calendar"></i>
                                </div>
                                <div class="col-lg-3 calwrap">N/A</div>
                                <div class="col-lg-1">Amount</div>
                                <div class="col-lg-2">N/A</div>
                                <div class="col-lg-1"></div>
                            </div>
                        @endif
                    </div>
                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Calculations :</label>
                        <div class="col-lg-7 control-label" id="calculations">
                            <ul>
                                <li>
                                    <strong>Adjusted Program Length:</strong> {{ data_get($offer, 'days', '0') }} (days)
                                </li>
                                <li>
                                    <strong>Total Program Cost:</strong> {{ data_get($offer, 'total_program_cost', '0') }}
                                </li>
                                <li>
                                    <strong>Program Fee:</strong> {{ data_get($offer, 'program_fee', '0') }}
                                </li>
                                <li>
                                    <strong>Insurance Cost To Driver:</strong>
                                    {{ data_get($offer, 'total_insurance', '0') }}
                                </li>
                                <li>
                                    <strong>Day Insurance:</strong> {{ data_get($offer, 'insurance', '0') }}
                                </li>
                                <li>
                                    <strong>Day Rent:</strong> {{ data_get($offer, 'day_rent', '0') }}
                                </li>
                                <li>
                                    <strong>EMF Per Day:</strong> {{ data_get($offer, 'emf', '0') }}
                                </li>
                                <li>
                                    <strong>Monthly Miles:</strong> {{ ceil(data_get($offer, 'miles', '0')) }}
                                </li>
                                <li>
                                    <strong>Deposit:</strong> {{ data_get($offer, 'total_deposit_amt', '0') }}
                                </li>
                                <li>
                                    <strong>Scheduled Payment:</strong> {{ data_get($offer, 'total_initial_fee', '0') }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <button type="button" class="btn left-margin btn-cancel"
                            onclick="goBack('/admin/vehicle_offers/index')">
                            Back
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection