@extends('admin.layouts.app')

@php
    $title ??= 'Update Rental Fee Template';
    $userid ??= null;
    $depositTemplateData ??= [];
    $makes ??= [];
    $models ??= [];
    $insurancePayers ??= [];
@endphp

@section('title', $title)

@section('content')

    <form method="POST" action="{{ url('admin/deposit_templates/index', base64_encode($userid)) }}" class="form-horizontal">
        @csrf

        <div class="page-header">
            <div class="page-header-content">
                <div class="page-title">
                    <h4>
                        <i class="icon-arrow-left52 position-left"></i>
                        <span class="text-semibold">Pricing Template</span>
                    </h4>
                </div>
                <div class="heading-elements">
                    <button type="submit" class="btn btn-success">
                        {{ empty(data_get($depositTemplateData, 'id', '')) ? 'Save' : 'Update' }}
                    </button>
                    <a href="{{ url('admin/users/index') }}" class="btn-danger btn" escape="false"
                        title="Back To User Listing">
                        Back To User Listing
                    </a>
                    @if (!empty(data_get($depositTemplateData, 'id', '')))
                        <button type="button" class="btn btn-danger" onclick="syncToVehicle()">
                            Sync To All Vehicles
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            @include('partials.flash')
        </div>

        <div class="masonry">
            <div class="item">
                <div class="panel">
                    <div class="panel-heading">
                        <h5 class="panel-title">Common Setting</h5>
                    </div>
                    <div class="panel-body">

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Extra Usage Fee :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" name="DepositTemplate[emf]" class="form-control"
                                    value="{{ data_get($depositTemplateData, 'emf', 0) }}"
                                    placeholder="Per Mile Amount in USD">
                                <span class="help-block">Per Mile Amount in USD</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Insurance Extra Usage Fee :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" name="DepositTemplate[emf_insu]" class="form-control"
                                    value="{{ $data['emf_insu'] ?? 0 }}" placeholder="Per Mile Amount in USD">
                                <span class="help-block">Per Mile Amount in USD</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                TAX :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="DepositTemplate[tax]" class="form-control"
                                    value="{{ $data['tax'] ?? 0 }}" placeholder="% Amount">
                                <span class="help-block">% Amount</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Lateness Fee :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="DepositTemplate[lateness_fee]" class="form-control"
                                    value="{{ $data['lateness_fee'] ?? 0 }}" placeholder="Per Hour Amount in USD">
                                <span class="help-block">Per Hour Amount in USD</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Cancellation Fee :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="DepositTemplate[cancellation_fee]"
                                    class="form-control" value="{{ $data['cancellation_fee'] ?? 0 }}"
                                    placeholder="Flat Amount in USD">
                                <span class="help-block">Flat Amount in USD</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Insurance Rate :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="DepositTemplate[insurance_fee]" class="form-control"
                                    value="{{ $data['insurance_fee'] ?? 0 }}" placeholder="Flat Amount in USD (Per Day)">
                                <span class="help-block">Flat Amount in USD (Per Day)</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Buying Fee ($) :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="DepositTemplate[buy_fee]" class="form-control"
                                    value="{{ $data['buy_fee'] ?? 0 }}" placeholder="Flat Amount in USD">
                                <span class="help-block">Flat Amount in USD</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Insurance Charge :
                            </label>
                            <div class="col-lg-8">
                                <select name="DepositTemplate[insurance_event]" class="form-control">
                                    <option value="P" @selected(($data['insurance_event'] ?? 'P') == 'P')>At Booking</option>
                                    <option value="S" @selected(($data['insurance_event'] ?? '') == 'S')>Start Event</option>
                                    <option value="E" @selected(($data['insurance_event'] ?? '') == 'E')>Booking End</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Max Extra Usage Fee : <span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="DepositTemplate[max_extramile_fee]"
                                    class="form-control" value="{{ $data['max_extramile_fee'] ?? 0 }}"
                                    placeholder="Max Extra Usage Amount For Booking">
                                <span class="help-block">Max Extra Usage Amount For Booking</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Write Down Allocation Rate (%) : <span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="DepositTemplate[write_down_allocation]"
                                    class="form-control" value="{{ $data['write_down_allocation'] ?? 0 }}"
                                    placeholder="Vehicle Write Down Allocation Rate">
                                <span class="help-block">Example like : 23.43, 20.00 etc</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Depreciation Rate (%) :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="DepositTemplate[depreciation_rate]"
                                    class="form-control" value="{{ $data['depreciation_rate'] ?? 0 }}"
                                    placeholder="Vehicle Depreciation Rate">
                                <span class="help-block">Example like : 23.43, 20.00 etc</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Finance Fee (per month) :
                            </label>
                            <div class="col-lg-4">
                                <select name="DepositTemplate[financing_type]" class="form-control">
                                    <option value="F" @selected(($data['financing_type'] ?? 'F') == 'F')>Fixed</option>
                                    <option value="P" @selected(($data['financing_type'] ?? '') == 'P')>Percentile</option>
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <input type="number" step="0.01" name="DepositTemplate[financing]" class="form-control"
                                    value="{{ $data['financing'] ?? 0 }}" placeholder="Financing Cost">
                                <em>Example like : 23.43%, 20.00 etc</em>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Capitalize Starting Fee :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <select name="DepositTemplate[capitalize_starting_fee]" class="form-control">
                                    <option value="0" @selected(($data['capitalize_starting_fee'] ?? '0') == '0')>No</option>
                                    <option value="1" @selected(($data['capitalize_starting_fee'] ?? '') == '1')>Yes</option>
                                </select>
                                <em>Setup a value, it will be used to calculate vehicle rental fee</em>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Lender Cost (per month) :
                            </label>
                            <div class="col-lg-4">
                                <select name="DepositTemplate[lender_type]" class="form-control">
                                    <option value="F" @selected(($data['lender_type'] ?? 'F') == 'F')>Fixed</option>
                                    <option value="P" @selected(($data['lender_type'] ?? '') == 'P')>Percentile</option>
                                </select>
                                <em>It will be used to calculate the finance cost on the cost side, in P&amp;L report.</em>
                            </div>
                            <div class="col-lg-4">
                                <input type="number" step="0.01" name="DepositTemplate[lender_fee]" class="form-control"
                                    value="{{ $data['lender_fee'] ?? 0 }}" placeholder="Lender Fee">
                                <em>Example like : 23.43%, 20.00 etc</em>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Maintenance Budget (Monthly) :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="DepositTemplate[monthly_maintenance]"
                                    class="form-control" value="{{ $data['monthly_maintenance'] ?? 0 }}"
                                    placeholder="Vehicle Monthly Maintenance">
                                <span class="help-block">Example like : 23.43, 20.00 etc</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Flat Fixed Fee (1 time cost) :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="DepositTemplate[disposition_fee]"
                                    class="form-control" value="{{ $data['disposition_fee'] ?? 0 }}"
                                    placeholder="Vehicle Disposition Fee">
                                <span class="help-block">Example like : 23.43, 20.00 etc</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Return Fee :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="DepositTemplate[return_fee]" class="form-control"
                                    value="{{ $data['return_fee'] ?? 0 }}" placeholder="Vehicle Return Fee">
                                <span class="help-block">Example like : 23.43, 20.00 etc</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Insurance Setting :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <select name="DepositTemplate[insurance_payer]" class="form-control"
                                    id="insurance_payer_select" required>
                                    @foreach($insurancePayers as $key => $label)
                                        <option value="{{ $key }}" @selected(($data['insurance_payer'] ?? '') == $key)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <em>This setting will be applied to Insurance. Insurance will be charged according to this
                                    setting</em>
                            </div>
                        </div>

                        <div class="form-group {{ ($data['insurance_payer'] ?? '') == 4 ? '' : 'hide' }}"
                            id="insurance_lender">
                            <label class="col-lg-4 control-label">
                                Choose Insurance Lender :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositTemplate[insurance_lender]" class="form-control"
                                    value="{{ $data['insurance_lender'] ?? '' }}" style="width:100%;">
                                <em>This setting will be applied to Insurance transfer.</em>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Program Length :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" name="DepositTemplate[program_length]" class="form-control"
                                    value="{{ $data['program_length'] ?? '' }}" required>
                                <em>Default program length in days</em>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Fixed Program Cost :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="DepositTemplate[fixed_program_cost]"
                                    class="form-control" value="{{ $data['fixed_program_cost'] ?? 0 }}" required>
                                <em>Fixed Program Cost</em>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Selling Price Premium :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="DepositTemplate[selling_premium]"
                                    class="form-control" value="{{ $data['selling_premium'] ?? 0 }}" required>
                                <em>Setup a value, it will be used to set vehicle premium selling price</em>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="item">
                <div class="panel">
                    <div class="panel-heading">
                        <h5 class="panel-title">Deposits &amp; Scheduled Fees</h5>
                    </div>
                    <div class="panel-body">

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Deposit Title:</label>
                            <div class="col-lg-9">
                                <input type="text" name="DepositTemplate[deposit_title]" class="form-control" maxlength="30"
                                    placeholder="Deposit Amt" value="{{ $data['deposit_title'] ?? '' }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Deposit Refundable?:</label>
                            <div class="col-lg-9">
                                <select name="DepositTemplate[is_deposit_refundable]" class="form-control">
                                    <option value="1" @selected(($data['is_deposit_refundable'] ?? '1') == '1')>Yes</option>
                                    <option value="0" @selected(($data['is_deposit_refundable'] ?? '') == '0')>No</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Deposit Description:</label>
                            <div class="col-lg-9">
                                <textarea name="DepositTemplate[deposit_amt_des]" class="form-control"
                                    rows="3">{{ $data['deposit_amt_des'] ?? '' }}</textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Charge Event:</label>
                            <div class="col-lg-9">
                                <select name="DepositTemplate[deposit_event]" class="form-control">
                                    <option value="P" @selected(($data['deposit_event'] ?? 'P') == 'P')>At Booking</option>
                                    <option value="S" @selected(($data['deposit_event'] ?? '') == 'S')>Start Event</option>
                                    <option value="E" @selected(($data['deposit_event'] ?? '') == 'E')>Booking End</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Deposit Amount ($):</label>
                            <div class="col-lg-9">
                                <input type="number" step="0.01" name="DepositTemplate[deposit_amt]" class="form-control"
                                    value="{{ $data['deposit_amt'] ?? 0 }}" placeholder="Deposit">
                            </div>
                        </div>

                        {{-- Deposit Optional Tiers --}}
                        <fieldset class="mt-10">
                            <legend class="text-semibold">Deposit Optional Tiers</legend>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="deposit-opt-table">
                                    <thead>
                                        <tr>
                                            <th>After Day</th>
                                            <th>Amount ($)</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $deptOpt = !empty($data['deposit_amt_opt']) ? (is_array($data['deposit_amt_opt']) ? $data['deposit_amt_opt'] : json_decode($data['deposit_amt_opt'], true)) : [];
                                            if (empty($deptOpt))
                                                $deptOpt = [[]];
                                        @endphp
                                        @foreach($deptOpt as $idx => $opt)
                                            <tr>
                                                <td><input type="number"
                                                        name="DepositTemplate[deposit_amt_opt][{{ $idx }}][after_day]"
                                                        class="form-control" value="{{ $opt['after_day'] ?? '' }}"></td>
                                                <td><input type="number" step="0.01"
                                                        name="DepositTemplate[deposit_amt_opt][{{ $idx }}][amount]"
                                                        class="form-control" value="{{ $opt['amount'] ?? '' }}"></td>
                                                <td class="text-center"><button type="button"
                                                        class="btn btn-danger btn-icon remove-row"><i
                                                            class="icon-trash"></i></button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-right mt-10">
                                <button type="button" class="btn btn-default btn-xs" id="add-deposit-opt"><i
                                        class="icon-plus2"></i> Add Tier</button>
                            </div>
                        </fieldset>

                        <div class="form-group mt-15">
                            <label class="col-lg-3 control-label">Initial Fee ($):</label>
                            <div class="col-lg-9">
                                <input type="number" step="0.01" name="DepositTemplate[initial_fee]" class="form-control"
                                    value="{{ $data['initial_fee'] ?? 0 }}" placeholder="Initial Fee">
                            </div>
                        </div>

                        {{-- Initial Fee Optional Tiers --}}
                        <fieldset class="mt-10">
                            <legend class="text-semibold">Initial Fee Optional Tiers</legend>
                            <div class="table-responsive">
                                <table class="table table-bordered" id="initial-opt-table">
                                    <thead>
                                        <tr>
                                            <th>After Day</th>
                                            <th>Amount ($)</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $initOpt = !empty($data['initial_fee_opt']) ? (is_array($data['initial_fee_opt']) ? $data['initial_fee_opt'] : json_decode($data['initial_fee_opt'], true)) : [];
                                            if (empty($initOpt))
                                                $initOpt = [[]];
                                        @endphp
                                        @foreach($initOpt as $idx => $opt)
                                            <tr>
                                                <td><input type="number"
                                                        name="DepositTemplate[initial_fee_opt][{{ $idx }}][after_day]"
                                                        class="form-control" value="{{ $opt['after_day'] ?? '' }}"></td>
                                                <td><input type="number" step="0.01"
                                                        name="DepositTemplate[initial_fee_opt][{{ $idx }}][amount]"
                                                        class="form-control" value="{{ $opt['amount'] ?? '' }}"></td>
                                                <td class="text-center"><button type="button"
                                                        class="btn btn-danger btn-icon remove-row"><i
                                                            class="icon-trash"></i></button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-right mt-10">
                                <button type="button" class="btn btn-default btn-xs" id="add-initial-opt"><i
                                        class="icon-plus2"></i> Add Tier</button>
                            </div>
                        </fieldset>

                        {{-- Allow Initial Fee Plan (Prepaid) --}}
                        @php
                            $prepaidData = !empty($data['prepaid_initial_fee_data']) ? (is_array($data['prepaid_initial_fee_data']) ? $data['prepaid_initial_fee_data'] : json_decode($data['prepaid_initial_fee_data'], true)) : [];
                        @endphp
                        <div class="form-group mt-15">
                            <label class="col-lg-3 control-label">Allow Initial Fee Plan:</label>
                            <div class="col-lg-1">
                                <input type="checkbox" name="DepositTemplate[prepaid_initial_fee]" value="1"
                                    @checked(($data['prepaid_initial_fee'] ?? 0) == 1) class="form-control choice">
                            </div>
                            <div class="col-lg-4">
                                <select name="DepositTemplate[prepaid_initial_fee_data][day]"
                                    class="form-control subchoice">
                                    <option value="">Schedule</option>
                                    <option value="1" @selected(($prepaidData['day'] ?? '') == '1')>Everyday</option>
                                    <option value="2" @selected(($prepaidData['day'] ?? '') == '2')>2 Days</option>
                                    <option value="7" @selected(($prepaidData['day'] ?? '') == '7')>Weekly</option>
                                    <option value="14" @selected(($prepaidData['day'] ?? '') == '14')>Bi-Weekly</option>
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <input type="number" step="0.01" name="DepositTemplate[prepaid_initial_fee_data][amount]"
                                    class="form-control subchoice" placeholder="Schedule Amount"
                                    value="{{ $prepaidData['amount'] ?? '' }}">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ======================================================= --}}
            {{-- PANEL 3: Dealer Payout Fee --}}
            {{-- ======================================================= --}}
            <div class="item">
                <div class="panel">
                    <div class="panel-heading">
                        <h5 class="panel-title">Dealer Payout Fee</h5>
                    </div>
                    <div class="panel-body">

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Vehicle Day Fare Calculation:</label>
                            <div class="col-lg-7">
                                <select name="DepositTemplate[fare_type]" class="form-control">
                                    <option value="S" @selected(($data['fare_type'] ?? '') == 'S')>Static</option>
                                    <option value="D" @selected(($data['fare_type'] ?? 'D') == 'D')>Dynamic</option>
                                    <option value="L" @selected(($data['fare_type'] ?? '') == 'L')>Lease Plus Pricing</option>
                                </select>
                            </div>
                            <div class="col-lg-2">
                                <button type="button" class="btn btn-warning pull-right"
                                    onclick="updateFareType('fare_type')">Sync <i
                                        class="icon-sync position-left"></i></button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Roadside Assistance ($):</label>
                            <div class="col-lg-7">
                                <input type="number" step="0.01" name="DepositTemplate[roadside_assistance_included]"
                                    id="DepositTemplateRoadsideAssistanceIncluded" class="form-control"
                                    value="{{ $data['roadside_assistance_included'] ?? 0 }}">
                            </div>
                            <div class="col-lg-2">
                                <button type="button" class="btn btn-warning pull-right"
                                    onclick="updateFareType('roadside_assistance_included')">Sync <i
                                        class="icon-sync position-left"></i></button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label">Maintenance Included Fee ($):</label>
                            <div class="col-lg-7">
                                <input type="number" step="0.01" name="DepositTemplate[maintenance_included_fee]"
                                    id="DepositTemplateMaintenanceIncludedFee" class="form-control"
                                    value="{{ $data['maintenance_included_fee'] ?? 0 }}">
                            </div>
                            <div class="col-lg-2">
                                <button type="button" class="btn btn-warning pull-right"
                                    onclick="updateFareType('maintenance_included_fee')">Sync <i
                                        class="icon-sync position-left"></i></button>
                            </div>
                        </div>

                        {{-- Incentives --}}
                        <div class="row mt-20">
                            <div class="col-md-12">
                                <fieldset>
                                    <legend class="text-semibold">Incentives</legend>
                                    <p class="text-muted">Apply specific incentive amounts based on vehicle make and model.
                                    </p>
                                    <div class="table-responsive">
                                        <table class="table table-bordered" id="incentives-table">
                                            <thead>
                                                <tr>
                                                    <th>Make</th>
                                                    <th>Model</th>
                                                    <th>Year</th>
                                                    <th>Trim</th>
                                                    <th>Amount ($)</th>
                                                    <th class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $incentives = !empty($data['incentives']) ? (is_array($data['incentives']) ? $data['incentives'] : json_decode($data['incentives'], true)) : [];
                                                    if (empty($incentives))
                                                        $incentives = [[]];
                                                @endphp
                                                @foreach($incentives as $idx => $inc)
                                                    <tr>
                                                        <td>
                                                            <select name="DepositTemplate[incentives][{{ $idx }}][make]"
                                                                class="form-control select-make">
                                                                <option value="">Any Make</option>
                                                                @foreach($makes as $m)
                                                                    <option value="{{ $m }}" @selected(($inc['make'] ?? '') == $m)>
                                                                        {{ $m }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                name="DepositTemplate[incentives][{{ $idx }}][model]"
                                                                class="form-control" placeholder="Model"
                                                                value="{{ $inc['model'] ?? '' }}">
                                                        </td>
                                                        <td>
                                                            <input type="number"
                                                                name="DepositTemplate[incentives][{{ $idx }}][year]"
                                                                class="form-control" placeholder="Year"
                                                                value="{{ $inc['year'] ?? '' }}">
                                                        </td>
                                                        <td>
                                                            <input type="text"
                                                                name="DepositTemplate[incentives][{{ $idx }}][trim]"
                                                                class="form-control" placeholder="Trim"
                                                                value="{{ $inc['trim'] ?? '' }}">
                                                        </td>
                                                        <td>
                                                            <input type="number" step="0.01"
                                                                name="DepositTemplate[incentives][{{ $idx }}][amount]"
                                                                class="form-control" placeholder="Amount"
                                                                value="{{ $inc['amount'] ?? '' }}">
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-danger btn-icon remove-row"><i
                                                                    class="icon-trash"></i></button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-right mt-10">
                                        <button type="button" class="btn btn-warning w-100" id="sync-incentives"
                                            onclick="updateVehicleSetting('incentive')">Sync Incentives <i
                                                class="icon-sync position-left"></i></button>
                                    </div>
                                    <div class="text-right mt-10">
                                        <button type="button" class="btn btn-default" id="add-incentive"><i
                                                class="icon-plus2 position-left"></i> Add Incentive</button>
                                    </div>
                                </fieldset>
                            </div>
                        </div>

                        <div class="form-group mt-10">
                            <label class="col-lg-3 control-label">Doc Fee:</label>
                            <div class="col-lg-7">
                                <input type="number" step="0.01" name="DepositTemplate[doc_fee]" id="DepositTemplateDocFee"
                                    class="form-control" value="{{ $data['doc_fee'] ?? 0 }}" placeholder="Doc Fee">
                            </div>
                            <div class="col-lg-2">
                                <button type="button" class="btn btn-warning pull-right"
                                    onclick="updateVehicleSetting('doc_fee')">Sync <i
                                        class="icon-sync position-left"></i></button>
                            </div>
                        </div>

                        <div class="text-right mt-20">
                            <button type="submit" class="btn btn-primary">Save Settings <i
                                    class="icon-arrow-right14 position-right"></i></button>
                            <a href="{{ url('admin/users/index') }}" class="btn btn-default">Cancel</a>
                            <button type="button" class="btn btn-info" id="sync-vehicles">Sync to All Vehicles <i
                                    class="icon-sync position-right"></i></button>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </form>

@endsection


@push('scripts')
    <script>
        $(document).ready(function () {
            // Add Incentive Row
            $('#add-incentive').click(function () {
                var idx = $('#incentives-table tbody tr').length;
                var row = `<tr>
                                                                                                                                                                                <td>
                                                                                                                                                                                    <select name="DepositTemplate[incentives][${idx}][make]" class="form-control">
                                                                                                                                                                                        <option value="">Any Make</option>
                                                                                                                                                                                        @foreach($makes as $m)
                                                                                                                                                                                            <option value="{{ $m }}">{{ $m }}</option>
                                                                                                                                                                                        @endforeach
                                                                                                                                                                                    </select>
                                                                                                                                                                                </td>
                                                                                                                                                                                <td><input type="text" name="DepositTemplate[incentives][${idx}][model]" class="form-control" placeholder="Model"></td>
                                                                                                                                                                                <td><input type="number" name="DepositTemplate[incentives][${idx}][year]" class="form-control" placeholder="Year"></td>
                                                                                                                                                                                <td><input type="text" name="DepositTemplate[incentives][${idx}][trim]" class="form-control" placeholder="Trim"></td>
                                                                                                                                                                                <td><input type="number" step="0.01" name="DepositTemplate[incentives][${idx}][amount]" class="form-control" placeholder="Amount"></td>
                                                                                                                                                                                <td class="text-center">
                                                                                                                                                                                    <button type="button" class="btn btn-danger btn-icon remove-row"><i class="icon-trash"></i></button>
                                                                                                                                                                                </td>
                                                                                                                                                                            </tr>`;
                $('#incentives-table tbody').append(row);
            });

            // Add Deposit Tier
            $('#add-deposit-opt').click(function () {
                var idx = $('#deposit-opt-table tbody tr').length;
                var row = `<tr>
                                                                                                                                                                                <td><input type="number" name="DepositTemplate[deposit_amt_opt][${idx}][after_day]" class="form-control"></td>
                                                                                                                                                                                <td><input type="number" step="0.01" name="DepositTemplate[deposit_amt_opt][${idx}][amount]" class="form-control"></td>
                                                                                                                                                                                <td class="text-center"><button type="button" class="btn btn-danger btn-icon remove-row"><i class="icon-trash"></i></button></td>
                                                                                                                                                                            </tr>`;
                $('#deposit-opt-table tbody').append(row);
            });

            // Add Initial Fee Tier
            $('#add-initial-opt').click(function () {
                var idx = $('#initial-opt-table tbody tr').length;
                var row = `<tr>
                                                                                                                                                                                <td><input type="number" name="DepositTemplate[initial_fee_opt][${idx}][after_day]" class="form-control"></td>
                                                                                                                                                                                <td><input type="number" step="0.01" name="DepositTemplate[initial_fee_opt][${idx}][amount]" class="form-control"></td>
                                                                                                                                                                                <td class="text-center"><button type="button" class="btn btn-danger btn-icon remove-row"><i class="icon-trash"></i></button></td>
                                                                                                                                                                            </tr>`;
                $('#initial-opt-table tbody').append(row);
            });

            // Remove Row
            $(document).on('click', '.remove-row', function () {
                if ($('#incentives-table tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                } else {
                    $(this).closest('tr').find('input, select').val('');
                }
            });

            // Sync to Vehicles
            $('#sync-vehicles').click(function () {
                if (!confirm('Are you sure you want to sync these settings to all existing vehicles for this dealer? This will overwrite individual vehicle settings.')) {
                    return;
                }

                var btn = $(this);
                var oldHtml = btn.html();
                btn.html('<i class="icon-spinner2 spinner position-left"></i> Syncing...').prop('disabled', true);

                $.ajax({
                    url: "{{ url('admin/deposit_templates/syncToVehicle') }}",
                    method: "POST",
                    data: $('form').serialize(),
                    success: function (response) {
                        if (response.status) {
                            alert(response.message);
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function () {
                        alert('An error occurred during synchronization.');
                    },
                    complete: function () {
                        btn.html(oldHtml).prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endpush