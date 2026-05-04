@extends('admin.layouts.app')

@section('title', 'Vehicle Offer Details')

@section('content')
    @php
        $listTitle ??= "View";
        $timezone ??= session('default_timezone', 'UTC');
        $getFinancing = function ($id) {
            return match ((int) $id) {
                1 => 'Rent',
                2 => 'Rent To Own',
                3 => 'Buy',
                4 => 'Lease',
                default => 'None',
            };
        };
        $calculations = !empty($offer->calculation) ? json_decode($offer->calculation, true) : [];
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{$listTitle}}</span> - Offer
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
                            {{ $offer->pto == 1 ? "PTO" : "Misc" }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Vehicle :</label>
                        <div class="col-lg-7 control-label">
                            {{ $offer->vehicle_name }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Selling Price :</label>
                        <div class="col-lg-7 control-label">
                            {{ number_format((float) ($offer->totalcost ?? 0), 2) }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Goal :</label>
                        <div class="col-lg-7 control-label">
                            {{ $offer->goal }} (%)
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Total Down Payment :</label>
                        <div class="col-lg-7 control-label">
                            {{ number_format((float) ($offer->downpayment ?? 0), 2) }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Target Program Length (Days):</label>
                        <div class="col-lg-7 control-label">
                            {{ $offer->target_days }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Driver Phone :</label>
                        <div class="col-lg-7 control-label">
                            {{ $offer->driver_phone }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Start DateTime :</label>
                        <div class="col-lg-7 control-label">
                            {{ \Carbon\Carbon::parse($offer->start_datetime)->setTimezone($timezone)->format('m/d/Y h:i A') }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Status :</label>
                        <div class="col-lg-7 control-label">
                            @if ($offer->status == 1) Accepted
                            @elseif ($offer->status == 2) Canceled
                            @else New
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <legend class="text-size-large text-bold">2. Rental Offer</legend>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Duration :</label>
                        <div class="col-lg-7 control-label">
                            {{ $offer->duration }} day(s)
                        </div>
                    </div>

                    @if(!empty($offer->duration_opt))
                        @foreach($offer->duration_opt as $idx => $val)
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
                            {{ $offer->fare_type == 'D' ? "Dynamic" : "Static" }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Day Rent :</label>
                        <div class="col-lg-7 control-label">
                            {{ number_format((float) ($offer->day_rent ?? 0), 2) }}
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 control-label">Deposit Amount :</label>
                        <div class="col-lg-7 control-label">
                            {{ number_format((float) ($offer->deposit_amt ?? 0), 2) }}
                        </div>
                    </div>
                    <div id="deposit_opt">
                        @if(!empty($offer->deposit_opt))
                            @foreach($offer->deposit_opt as $idx => $val)
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
                            {{ number_format((float) ($offer->initial_fee ?? 0), 2) }}
                        </div>
                    </div>
                    <div id="initialfee_opt">
                        @if(!empty($offer->initial_fee_opt))
                            @foreach($offer->initial_fee_opt as $idx => $val)
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
                            @if(!empty($calculations))
                                <ul>
                                    <li>
                                        <strong>Adjusted Program Length:</strong>
                                        {{ $calculations['days'] ?? $offer->days }} (days)
                                    </li>
                                    <li>
                                        <strong>Total Program Cost:</strong>
                                        {{ $calculations['total_program_cost'] ?? $offer->total_program_cost }}
                                    </li>
                                    <li>
                                        <strong>Program Fee:</strong>
                                        {{ $calculations['program_fee'] ?? $offer->program_fee }}
                                    </li>
                                    <li>
                                        <strong>Insurance Cost To Driver:</strong>
                                        {{ $calculations['total_insurance'] ?? $offer->total_insurance }}
                                    </li>
                                    <li>
                                        <strong>Day Insurance:</strong>
                                        {{ $calculations['dayInsurance'] ?? $offer->insurance }}
                                    </li>
                                    <li>
                                        <strong>Day Rent:</strong>
                                        {{ $calculations['dayRent'] ?? $offer->day_rent }}
                                    </li>
                                    <li>
                                        <strong>EMF Per Day:</strong>
                                        {{ $calculations['emf'] ?? $offer->emf }}
                                    </li>
                                    <li>
                                        <strong>Monthly Miles:</strong>
                                        {{ $calculations['month_miles'] ?? ceil($offer->miles ?? 0) }}
                                    </li>
                                    <li>
                                        <strong>Deposit:</strong>
                                        {{ $offer->total_deposit_amt }}
                                    </li>
                                    <li>
                                        <strong>Scheduled Payment:</strong>
                                        {{ $offer->total_initial_fee }}
                                    </li>
                                </ul>
                            @else
                                <p>No calculation summary available.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <hr>
                    <a href="{{url('admin/vehicle_offers/index')}}" class="btn btn-default">
                        <i class="icon-arrow-left8 position-left"></i> Return to List
                    </a>
                    @if($offer->status == 0)
                        <a href="{{ url('admin/vehicle_offers/index/add', base64_encode($offer->id)) }}"
                            class="btn btn-primary">
                            <i class="glyphicon glyphicon-edit"></i> Edit Offer
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection