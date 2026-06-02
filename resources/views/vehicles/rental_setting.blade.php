@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Vehicle Fee Setting')

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
    <style type="text/css">
        .form-group {
            margin-bottom: 5px;
        }
    </style>
@endpush

@section('content')

    @includeif('partials.flash')

    <form method="POST" action="{{ url('/admin/vehicles/rental_setting/' . base64_encode($id)) }}" id="frmadmin"
        name="frmadmin" class="form-horizontal">
        @csrf

        <div class="page-header">
            <div class="page-header-content">
                <div class="page-title">
                    <h4>
                        <i class="icon-arrow-left52 position-left"></i>
                        <span class="text-semibold">Vehicle Fee Setting</span>
                    </h4>
                </div>
                <div class="heading-elements">
                    <div class="heading-btn-group">
                        <button type="submit" class="btn btn-primary">
                            {{ empty(data_get($vehicle, 'depositRule.id', '')) ? 'Save' : 'Update' }}
                        </button>
                        <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/vehicles/index')">
                            Return
                        </button>
                    </div>
                </div>
            </div>
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
                                Vehicle :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                {{ data_get($vehicle, 'vehicle_unique_id') }}
                                <input type="hidden" name="DepositRule[vehicle_id]" id="DepositRuleVehicleId"
                                    value="{{ data_get($vehicle, 'id') }}">
                                <input type="hidden" name="DepositRule[user_id]" id="DepositRuleUserId"
                                    value="{{ data_get($vehicle, 'user_id') }}">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Extra Usage Fee :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[emf]" id="DepositRuleEmf" maxlength="10"
                                    class="digit required form-control"
                                    value="{{ data_get($vehicle, 'depositRule.emf', '') }}">
                                <span class="help-block">Per Mile Amount in USD</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Insurance Extra Usage Fee :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[emf_insu]" id="DepositRuleEmfInsu" maxlength="10"
                                    class="digit required form-control"
                                    value="{{ data_get($vehicle, 'depositRule.emf_insu', '') }}">
                                <span class="help-block">Per Mile Amount in USD</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                TAX :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[tax]" id="DepositRuleTax" maxlength="10"
                                    class="digit required form-control"
                                    value="{{ data_get($vehicle, 'depositRule.tax', '') }}">
                                <span class="help-block">% Amount in USD</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Lateness Fee :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[lateness_fee]" id="DepositRuleLatenessFee"
                                    maxlength="10" class="digit required form-control"
                                    value="{{ data_get($vehicle, 'depositRule.lateness_fee', '') }}">
                                <span class="help-block">Per Hour Amount in USD</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Cancellation Fee :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[cancellation_fee]" id="DepositRuleCancellationFee"
                                    maxlength="10" class="digit required form-control"
                                    value="{{ data_get($vehicle, 'depositRule.cancellation_fee', '') }}">
                                <span class="help-block">Flat Amount in USD</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Insurance Rate :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[insurance_fee]" id="DepositRuleInsuranceFee"
                                    maxlength="10" class="digit required form-control"
                                    value="{{ data_get($vehicle, 'depositRule.insurance_fee', '') }}">
                                <span class="help-block">Flat Amount in USD (Per day)</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Insurance Charge :
                            </label>
                            <div class="col-lg-8">
                                <select name="DepositRule[insurance_event]" id="DepositRuleInsuranceEvent"
                                    class="form-control">
                                    <option value="P" @selected(data_get($vehicle, 'depositRule.insurance_event') === 'P')>At
                                        Booking
                                    </option>
                                    <option value="S" @selected(data_get($vehicle, 'depositRule.insurance_event') === 'S')>
                                        Start Event
                                    </option>
                                    <option value="E" @selected(data_get($vehicle, 'depositRule.insurance_event') === 'E')>
                                        Booking End
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Write Down Allocation Rate (%) :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[write_down_allocation]"
                                    id="DepositRuleWriteDownAllocation" maxlength="10" class="required form-control"
                                    placeholder="Vehicle Write Down Allocation Rate"
                                    value="{{ data_get($vehicle, 'depositRule.write_down_allocation', '') }}">
                                <span class="help-block">Example like : 23.43, 20.00 etc</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Vehicle Depreciation Rate (%) :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[depreciation_rate]" id="DepositRuleDepreciationRate"
                                    maxlength="10" class="required form-control" placeholder="Vehicle Depreciation Rate"
                                    value="{{ data_get($vehicle, 'depositRule.depreciation_rate', '') }}">
                                <span class="help-block">Example like : 23.43, 20.00 etc (Monthly)</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Finance Cost (per month) :
                            </label>
                            <div class="col-lg-4">
                                <select name="DepositRule[financing_type]" id="DepositRuleFinancingType"
                                    class="form-control">
                                    <option value="F" @selected(data_get($vehicle, 'depositRule.financing_type') === 'F')>
                                        Fixed
                                    </option>
                                    <option value="P" @selected(data_get($vehicle, 'depositRule.financing_type') === 'P')>
                                        Percentile
                                    </option>
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <input type="text" name="DepositRule[financing]" id="DepositRuleFinancing"
                                    class="form-control digit" placeholder="Financing Cost"
                                    value="{{ data_get($vehicle, 'depositRule.financing', '') }}">
                                <em>Example like : 23.43%, 20.00 etc</em>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Capitalize Starting Fee :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <select name="DepositRule[capitalize_starting_fee]" id="DepositRuleCapitalizeStartingFee"
                                    class="form-control">
                                    <option value="0" @selected(data_get($vehicle, 'depositRule.capitalize_starting_fee') == 0)>No
                                    </option>
                                    <option value="1" @selected(data_get($vehicle, 'depositRule.capitalize_starting_fee') == 1)>Yes
                                    </option>
                                </select>
                                <em>Setup a value, it will be used to calculate vehicle rental fee</em>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Monthly Maintenance ($):<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[monthly_maintenance]"
                                    id="DepositRuleMonthlyMaintenance" maxlength="10" class="required form-control"
                                    placeholder="Vehicle Monthly Maintenance"
                                    value="{{ data_get($vehicle, 'depositRule.monthly_maintenance', '') }}">
                                <span class="help-block">Example like : 23.43, 20.00 etc</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Flat Fixed Fee (1 time cost) :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[disposition_fee]" id="DepositRuleDispositionFee"
                                    maxlength="10" class="required form-control" placeholder="Vehicle Disposition Fee"
                                    value="{{ data_get($vehicle, 'depositRule.disposition_fee', '') }}">
                                <span class="help-block">Example like : 23.43, 20.00 etc</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Return Fee :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[return_fee]" id="DepositRuleReturnFee" maxlength="10"
                                    class="required form-control" placeholder="Vehicle return fee"
                                    value="{{ data_get($vehicle, 'depositRule.return_fee', '') }}">
                                <span class="help-block">Example like : 23.43, 20.00 etc</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Lender Fee (per month) :
                            </label>
                            <div class="col-lg-4">
                                <select name="DepositRule[lender_type]" id="DepositRuleLenderType" class="form-control">
                                    <option value="F" @selected(data_get($vehicle, 'depositRule.lender_type') === 'F')>Fixed
                                    </option>
                                    <option value="P" @selected(data_get($vehicle, 'depositRule.lender_type') === 'P')>
                                        Percentile
                                    </option>
                                </select>
                                <em>It will be used to calculate the finance cost on the cost side, in P&L report.</em>
                            </div>
                            <div class="col-lg-4">
                                <input type="text" name="DepositRule[lender_fee]" id="DepositRuleLenderFee"
                                    class="form-control digit" placeholder="Lender Fee"
                                    value="{{ data_get($vehicle, 'depositRule.lender_fee', '') }}">
                                <em>Example like : 23.43%, 20.00 etc</em>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Lender Anticipated Date :
                            </label>
                            <div class="col-lg-8">
                                <input type="date" name="DepositRule[lender_anticipated_date]"
                                    id="DepositRuleLenderAnticipatedDate" class="form-control"
                                    placeholder="Lender Anticipated Date"
                                    value="{{ data_get($vehicle, 'depositRule.lender_anticipated_date', '') }}">
                                <span class="help-block">Date will be used in Cash Flow Report</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Insurance Setting :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <select name="DepositRule[insurance_payer]" id="DepositRuleInsurancePayer"
                                    class="form-control" required>
                                    @foreach ($commonService->getInsurancePayer() as $key => $val)
                                        <option value="{{ $key }}" @selected(data_get($vehicle, 'depositRule.insurance_payer') == $key)>
                                            {{ $val }}
                                        </option>
                                    @endforeach
                                </select>
                                <em>This setting will be applied to Insurance. Insurance will be charged according to this
                                    setting</em>
                            </div>
                        </div>

                        <div class="form-group {{ (data_get($vehicle, 'depositRule.insurance_payer') == 4) ? 'show' : 'hide' }}"
                            id="insurance_lender">
                            <label class="col-lg-4 control-label">
                                Choose Insurance Lender :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[insurance_lender]" id="DepositRuleInsuranceLender"
                                    class="form-control" required style="width:100%;"
                                    value="{{ data_get($vehicle, 'depositRule.insurance_lender', '') }}">
                                <em>This setting will be applied to Insurance transfer. Charged Insurance will be
                                    transferred according to this setting</em>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Program Length :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[program_length]" id="DepositRuleProgramLength"
                                    class="form-control digit" required
                                    value="{{ data_get($vehicle, 'depositRule.program_length', '') }}">
                                <em>Default program length in days</em>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <div class="item">
                <div class="panel">
                    <div class="panel-heading">
                        <h5 class="panel-title">Deposits & Scheduled Fees</h5>
                    </div>
                    <div class="panel-body"
                        rel-deposit="{{ count(data_get($vehicle, 'depositRule.deposit_amt_opt')) ?: 1 }}"
                        rel-initialfee="{{ count(data_get($vehicle, 'depositRule.initial_fee_opt')) ?: 1 }}">
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Deposit Charge Event:</label>
                            <div class="col-lg-8">
                                <select name="DepositRule[deposit_event]" id="DepositRuleDepositEvent" class="form-control">
                                    <option value="P" @selected(data_get($vehicle, 'depositRule.deposit_event') === 'P')>At
                                        Booking
                                    </option>
                                    <option value="S" @selected(data_get($vehicle, 'depositRule.deposit_event') === 'S')>Start
                                        Event
                                    </option>
                                    <option value="E" @selected(data_get($vehicle, 'depositRule.deposit_event') === 'E')>
                                        Booking End
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Deposit Amount:</label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[deposit_amt]" id="DepositRuleDepositAmt"
                                    class="form-control" placeholder="Deposit"
                                    value="{{ data_get($vehicle, 'depositRule.deposit_amt', '') }}">
                            </div>
                            <div class="col-lg-1"></div>
                        </div>
                        <div id="deposit">
                            @php $i = 1; @endphp
                            @if (count(data_get($vehicle, 'depositRule.deposit_amt_opt')))
                                @foreach (data_get($vehicle, 'depositRule.deposit_amt_opt') as $val)
                                    <div class="form-group" id="ele-{{ $i }}">
                                        <label class="col-lg-3 control-label">&nbsp;</label>
                                        <div class="col-lg-2">After Days</div>
                                        <div class="col-lg-2">
                                            <input type="text" name="DepositRule[deposit_amt_opt][{{ $i }}][after_day]"
                                                class="form-control" placeholder="days" value="{{ data_get($val, 'after_day') }}">
                                        </div>
                                        <div class="col-lg-2">Amount</div>
                                        <div class="col-lg-2">
                                            <input type="text" name="DepositRule[deposit_amt_opt][{{ $i }}][amount]"
                                                class="form-control" placeholder="amount" value="{{ data_get($val, 'amount') }}">
                                        </div>
                                        @if ($i++ == 1)
                                            <div class="col-lg-1">
                                                <a href="javascript:;" onclick="deposit(true)">
                                                    <i class="icon-plus-circle2 icon-2x"></i>
                                                </a>
                                            </div>
                                        @else
                                            <div class="col-lg-1">
                                                <a href="javascript:;" onclick="deposit(false)">
                                                    <i class="icon-minus-circle2 icon-2x"></i>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="form-group" id="ele-1">
                                    <label class="col-lg-3 control-label">&nbsp;</label>
                                    <div class="col-lg-2">After Days</div>
                                    <div class="col-lg-2">
                                        <input type="text" name="DepositRule[deposit_amt_opt][1][after_day]"
                                            class="form-control" placeholder="days" value="">
                                    </div>
                                    <div class="col-lg-2">Amount</div>
                                    <div class="col-lg-2">
                                        <input type="text" name="DepositRule[deposit_amt_opt][1][amount]" class="form-control"
                                            placeholder="amount" value="">
                                    </div>
                                    <div class="col-lg-1">
                                        <a href="javascript:;" onclick="deposit(true)">
                                            <i class="icon-plus-circle2 icon-2x"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Initial Fee:</label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[initial_fee]" id="DepositRuleInitialFee"
                                    class="form-control" placeholder="Initial Fee"
                                    value="{{ data_get($vehicle, 'depositRule.initial_fee', '') }}">
                            </div>
                            <div class="col-lg-1"></div>
                        </div>
                        <div id="initialfee">
                            @php $i = 1; @endphp
                            @if (count(data_get($vehicle, 'depositRule.initial_fee_opt')))
                                @foreach (data_get($vehicle, 'depositRule.initial_fee_opt') as $val)
                                    <div class="form-group" id="ele-{{ $i }}">
                                        <label class="col-lg-3 control-label">&nbsp;</label>
                                        <div class="col-lg-2">After Days</div>
                                        <div class="col-lg-2">
                                            <input type="text" name="DepositRule[initial_fee_opt][{{ $i }}][after_day]"
                                                class="form-control" placeholder="days" value="{{ data_get($val, 'after_day') }}">
                                        </div>
                                        <div class="col-lg-2">Amount</div>
                                        <div class="col-lg-2">
                                            <input type="text" name="DepositRule[initial_fee_opt][{{ $i }}][amount]"
                                                class="form-control" placeholder="amount" value="{{ data_get($val, 'amount') }}">
                                        </div>
                                        @if ($i++ == 1)
                                            <div class="col-lg-1">
                                                <a href="javascript:;" onclick="initialfee(true)">
                                                    <i class="icon-plus-circle2 icon-2x"></i>
                                                </a>
                                            </div>
                                        @else
                                            <div class="col-lg-1">
                                                <a href="javascript:;" onclick="initialfee(false)">
                                                    <i class="icon-minus-circle2 icon-2x"></i>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <div class="form-group" id="ele-1">
                                    <label class="col-lg-3 control-label">&nbsp;</label>
                                    <div class="col-lg-2">After Days</div>
                                    <div class="col-lg-2">
                                        <input type="text" name="DepositRule[initial_fee_opt][1][after_day]"
                                            class="form-control" placeholder="days" value="">
                                    </div>
                                    <div class="col-lg-2">Amount</div>
                                    <div class="col-lg-2">
                                        <input type="text" name="DepositRule[initial_fee_opt][1][amount]" class="form-control"
                                            placeholder="amount" value="">
                                    </div>
                                    <div class="col-lg-1">
                                        <a href="javascript:;" onclick="initialfee(true)">
                                            <i class="icon-plus-circle2 icon-2x"></i>
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Allow Initial Fee Plan:</label>
                            <div class="col-lg-1">
                                <input type="checkbox" name="DepositRule[prepaid_initial_fee]"
                                    id="DepositRulePrepaidInitialFee" class="form-control choice" value="1"
                                    @checked(data_get($vehicle, 'depositRule.prepaid_initial_fee') == 1)>
                            </div>
                            <div class="col-lg-4">
                                <select name="DepositRule[prepaid_initial_fee_data][day]" id="DepositRulePrepaidSchedule"
                                    class="form-control subchoice">
                                    <option value="">Schedule</option>
                                    <option value="1" @selected(data_get($vehicle, 'depositRule.prepaid_initial_fee_data.day') == 1)>Everyday</option>
                                    <option value="2" @selected(data_get($vehicle, 'depositRule.prepaid_initial_fee_data.day') == 2)>2 Days</option>
                                    <option value="7" @selected(data_get($vehicle, 'depositRule.prepaid_initial_fee_data.day') == 7)>Weekly</option>
                                    <option value="14" @selected(data_get($vehicle, 'depositRule.prepaid_initial_fee_data.day') == 14)>Bi-Weekly</option>
                                </select>
                            </div>
                            <div class="col-lg-4">
                                <input type="text" name="DepositRule[prepaid_initial_fee_data][amount]"
                                    id="DepositRulePrepaidAmount" class="form-control subchoice"
                                    placeholder="Schedule Amount"
                                    value="{{ data_get($vehicle, 'depositRule.prepaid_initial_fee_data.amount', '') }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="item">
                <div class="panel">
                    <div class="panel-heading">
                        <h5 class="panel-title">Dealer Payout Fee</h5>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Incentives:</label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[incentive]" id="DepositRuleIncentive"
                                    class="form-control digit" placeholder="Incentives"
                                    value="{{ data_get($vehicle, 'depositRule.incentive', '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Doc Fee:</label>
                            <div class="col-lg-8">
                                <input type="text" name="DepositRule[doc_fee]" id="DepositRuleDocFee"
                                    class="form-control digit" placeholder="Doc Fee"
                                    value="{{ data_get($vehicle, 'depositRule.doc_fee', '') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-3 control-label">Free2Move Response:</label>
                            <div class="col-lg-8">
                                <pre>{{ data_get($vehicle, 'depositRule.free_two_move') ? print_r(json_decode(data_get($vehicle, 'depositRule.free_two_move'), true)) : '' }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="item">
                <div class="panel">
                    <div class="panel-heading">
                        <h5 class="panel-title">Rental Pricing</h5>
                    </div>
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Day Rent Style :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <select name="Vehicle[fare_type]" id="VehicleFareType" class="required form-control"
                                    rel_day="{{ data_get($vehicle, 'day_rent', 0) }}">
                                    <option value="S" @selected(data_get($vehicle, 'fare_type') === 'S')>Static</option>
                                    <option value="D" @selected(data_get($vehicle, 'fare_type') === 'D')>Dynamic</option>
                                    <option value="L" @selected(data_get($vehicle, 'fare_type') === 'L')>Lease Plus Pricing
                                    </option>
                                </select>
                                <em>If Dynamic fare is setting then please click "GET DYNAMIC FARE" button in bottom. It
                                    will visible after save setting.</em>
                            </div>
                        </div>
                        <div class="form-group" id="dynamicfarebuttonblock"
                            style="{{ (data_get($vehicle, 'fare_type') === 'D' || data_get($vehicle, 'fare_type') === 'L') ? '' : 'display:none;' }}">
                            <label class="col-lg-4 control-label">
                            </label>
                            <div class="col-lg-8">
                                <button type="button" id="dynamicfarebutton"
                                    class="btn btn-danger btn-rounded {{ data_get($vehicle, 'fare_type') !== 'D' ? 'hidden' : '' }}"
                                    onclick="getVehicleDynamicFare()">GET DYNAMIC FARE</button>
                                <button type="button" id="leasefarebutton"
                                    class="btn btn-danger btn-rounded {{ data_get($vehicle, 'fare_type') !== 'L' ? 'hidden' : '' }}"
                                    onclick="getVehicleDynamicFare('L')">GET LEASE FARE</button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Rental per :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <select name="Vehicle[rental]" id="VehicleRental" class="required form-control"
                                    rel_hr="{{ data_get($vehicle, 'rate', 0) }}"
                                    rel_day="{{ data_get($vehicle, 'day_rent', 0) }}">
                                    <option value="hr" @selected(data_get($vehicle, 'rate', 0) > 0)>Hour</option>
                                    <option value="day" @selected(data_get($vehicle, 'rate', 0) <= 0)>Day</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group" id="hrblk"
                            style="{{ data_get($vehicle, 'rate', 0) == 0 ? 'display:none;' : '' }}">
                            <label class="col-lg-4 control-label">
                                Rate (per hour) :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="Vehicle[rate]" id="VehicleRate" maxlength="15"
                                    class="required number form-control" value="{{ data_get($vehicle, 'rate', '') }}">
                            </div>
                        </div>
                        <div class="form-group" id="dayblk"
                            style="{{ (data_get($vehicle, 'rate') !== null && data_get($vehicle, 'day_rent', 0) == 0) ? 'display:none;' : '' }}">
                            <label class="col-lg-4 control-label">
                                Day Rent :<span class="text-danger">*</span>
                            </label>
                            <div class="col-lg-8">
                                <input type="text" name="Vehicle[day_rent]" id="VehicleDayRent" maxlength="10"
                                    class="number required form-control" value="{{ data_get($vehicle, 'day_rent', '') }}">
                                <span class="help-block">Min/Max Rent Per Day (if you setup this then flat amount per day
                                    will be applied)</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-12 control-label text-danger">
                                <input type="checkbox" name="Vehicle[updatebooking]" class="control-warning" value="1" />
                                Update Day Rental to Active booking (Rate will be applied to next extension of booking)
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Day Rent Tier 1
                            </label>
                            <div class="col-lg-2">After Days</div>
                            <div class="col-lg-2">
                                <input type="text" name="Vehicle[rent_opt][1][after_day]" class="form-control digit"
                                    placeholder="days"
                                    value="{{ data_get($vehicle, 'rent_opt.1.after_day', data_get($vehicle, 'rent_opt.0.after_day', '')) }}">
                            </div>
                            <div class="col-lg-2">Amount</div>
                            <div class="col-lg-2">
                                <input type="text" name="Vehicle[rent_opt][1][amount]" class="form-control number"
                                    placeholder="amount"
                                    value="{{ data_get($vehicle, 'rent_opt.1.amount', data_get($vehicle, 'rent_opt.0.amount', '')) }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Day Rent Tier 2
                            </label>
                            <div class="col-lg-2">After Days</div>
                            <div class="col-lg-2">
                                <input type="text" name="Vehicle[rent_opt][2][after_day]" class="form-control digit"
                                    placeholder="days"
                                    value="{{ data_get($vehicle, 'rent_opt.2.after_day', data_get($vehicle, 'rent_opt.1.after_day', '')) }}">
                            </div>
                            <div class="col-lg-2">Amount</div>
                            <div class="col-lg-2">
                                <input type="text" name="Vehicle[rent_opt][2][amount]" class="form-control number"
                                    placeholder="amount"
                                    value="{{ data_get($vehicle, 'rent_opt.2.amount', data_get($vehicle, 'rent_opt.2.amount', '')) }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Authorize Payment :
                            </label>
                            <div class="col-lg-8">
                                <select name="Vehicle[auth_require]" id="VehicleAuthRequire" class="form-control">
                                    <option value="0" @selected(data_get($vehicle, 'auth_require') == 0)>Disable</option>
                                    <option value="1" @selected(data_get($vehicle, 'auth_require') == 1)>Enable</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12" style="margin-top: 20px;">
            <div class="form-group">
                <div class="col-lg-2">
                    <button type="submit" class="btn btn-primary w-100">
                        {{ empty(data_get($vehicle, 'depositRule.id')) ? 'Save' : 'Update' }}
                    </button>
                </div>
                <div class="col-lg-2">
                    <button type="button" class="btn left-margin btn-cancel w-100"
                        onclick="goBack('/admin/vehicles/index')">
                        Return
                    </button>
                </div>
            </div>
        </div>

        <input type="hidden" name="DepositRule[id]" id="DepositRuleId" value="{{ data_get($vehicle, 'depositRule.id') }}">
    </form>
@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/select2.js') }}"></script>

    <script type="text/javascript">

        function format(item) {
            return item.tag;
        }

        jQuery(document).ready(function () {

            jQuery("#DepositRuleInsurancePayer").change(function () {
                if (jQuery(this).val() == '4') {
                    jQuery("#insurance_lender").removeClass('hide').addClass('show');
                } else {
                    jQuery("#insurance_lender").addClass('hide').removeClass('show');
                }
            });

            jQuery("#DepositRuleInsuranceLender").select2({
                data: {
                    results: {},
                    text: 'tag'
                },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Customer ",
                minimumInputLength: 1,
                ajax: {
                    url: "{{ url('admin/bookings/customerautocomplete') }}",
                    dataType: "json",
                    type: "GET",
                    data: function (params) {
                        return {
                            term: params,
                            "is_dealer": true
                        }
                    },
                    processResults: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return {
                                    tag: item.tag,
                                    id: item.id
                                }
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    var dealer_id = "{{ data_get($vehicle, 'depositRule.insurance_lender', '') }}";
                    if (dealer_id.length > 0) {
                        jQuery.ajax({
                            url: "{{ url('admin/bookings/customerautocomplete') }}",
                            dataType: "json",
                            type: "GET",
                            data: {
                                "id": dealer_id
                            }
                        }).done(function (data) {
                            callback(data[0]);
                        });
                    }
                }
            });

            jQuery("#frmadmin").validate();

            jQuery("#DepositRuleDepositEvent").change(function () {
                if (jQuery(this).val() == 'N') {
                    jQuery("#depositamnt").hide();
                    jQuery("#deposittype").hide();
                } else {
                    jQuery("#depositamnt").show();
                    jQuery("#deposittype").show();
                }
            });

            jQuery("#DepositRuleInitialEvent").change(function () {
                if (jQuery(this).val() == 'N') {
                    jQuery("#initialamnt").hide();
                } else {
                    jQuery("#initialamnt").show();
                }
            });

            jQuery("#VehicleRental").change(function () {
                if (jQuery(this).val() == 'hr') {
                    jQuery("#dayblk").hide();
                    jQuery("#dayblk").find("input").val(0);
                    jQuery("#hrblk").find("input").val(jQuery(this).attr('rel_hr'));
                    jQuery("#hrblk").show();
                } else {
                    jQuery("#hrblk").hide();
                    jQuery("#hrblk").find("input").val(0);
                    jQuery("#dayblk").find("input").val(jQuery(this).attr('rel_day'));
                    jQuery("#dayblk").show();
                }
            });

            jQuery("#VehicleFareType").change(function () {
                if (jQuery(this).val() == 'D') {
                    jQuery("#dynamicfarebuttonblock").show();
                    jQuery("#dynamicfarebutton").removeClass('hidden').addClass('show');
                    jQuery("#leasefarebutton").removeClass('show').addClass('hidden');
                    jQuery("#hrblk").hide();
                    jQuery("#hrblk").find("input").val(0);
                    jQuery("#dayblk").find("input").val(jQuery(this).attr('rel_day'));
                    jQuery("#dayblk").show();
                    return false;
                }
                if (jQuery(this).val() == 'L') {
                    jQuery("#dynamicfarebuttonblock").show();
                    jQuery("#leasefarebutton").removeClass('hidden').addClass('show');
                    jQuery("#dynamicfarebutton").removeClass('show').addClass('hidden');
                    jQuery("#hrblk").hide();
                    jQuery("#hrblk").find("input").val(0);
                    jQuery("#dayblk").find("input").val(jQuery(this).attr('rel_day'));
                    jQuery("#dayblk").show();
                    return false;
                }

                jQuery("#dynamicfarebuttonblock").hide();
            });

            jQuery(".choice").change(function () {
                if (jQuery(this).is(":checked")) {
                    jQuery(this).closest('.form-group').find('.subchoice').addClass('required');
                } else {
                    jQuery(this).closest('.form-group').find('.subchoice').removeClass('required');
                }
            });
        });

        function deposit(v) {
            var elem = parseInt($("#deposit").parent(".panel-body").attr('rel-deposit'));
            if (v) {
                if (elem === 10) {
                    alert("Sorry, you cant add more than 10 reccords");
                    return;
                }
                elem++;
                var element = '<div class="form-group" id="ele-' + elem + '">' +
                    '<label class="col-lg-3 control-label">&nbsp;</label>' +
                    '<div class="col-lg-2">After Days</div>' +
                    '<div class="col-lg-2">' +
                    '<input name="DepositRule[deposit_amt_opt][' + elem + '][after_day]" class="form-control" placeholder="days" value="0" type="text"></div>' +
                    '<div class="col-lg-2">Amount</div>' +
                    '<div class="col-lg-2"><input name="DepositRule[deposit_amt_opt][' + elem + '][amount]" class="form-control" placeholder="amount" value="0" type="text"></div>' +
                    '<div class="col-lg-1"><a href="javascript:;" onclick="deposit(false)"><i class=" icon-minus-circle2 icon-2x"></i></a></div></div>';
                $("#deposit").append(element);
            } else {
                $("#deposit #ele-" + elem).remove();
                elem--;
            }
            $("#deposit").parent(".panel-body").attr('rel-deposit', elem);
        }

        function initialfee(v) {
            var elem = parseInt($("#initialfee").parent(".panel-body").attr('rel-initialfee'));
            if (v) {
                if (elem === 10) {
                    alert("Sorry, you cant add more than 10 reccords");
                    return;
                }
                elem++;
                var element = '<div class="form-group" id="ele-' + elem + '">' +
                    '<label class="col-lg-3 control-label">&nbsp;</label>' +
                    '<div class="col-lg-2">After Days</div>' +
                    '<div class="col-lg-2">' +
                    '<input name="DepositRule[initial_fee_opt][' + elem + '][after_day]" class="form-control" placeholder="days" value="0" type="text"></div>' +
                    '<div class="col-lg-2">Amount</div>' +
                    '<div class="col-lg-2"><input name="DepositRule[initial_fee_opt][' + elem + '][amount]" class="form-control" placeholder="amount" value="0" type="text"></div>' +
                    '<div class="col-lg-1"><a href="javascript:;" onclick="initialfee(false)"><i class=" icon-minus-circle2 icon-2x"></i></a></div></div>';
                $("#initialfee").append(element);
            } else {
                $("#initialfee #ele-" + elem).remove();
                elem--;
            }
            $("#initialfee").parent(".panel-body").attr('rel-initialfee', elem);
        }

        function getVehicleDynamicFare(tag = 'D') {
            var vehicleID = $("#DepositRuleVehicleId").val();
            jQuery.blockUI({
                message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Sending...</h1>',
                css: { "z-index": "9999" },
            });
            $.post("{{ url('admin/vehicles/getVehicleDynamicFare') }}", {
                vehicleid: vehicleID,
                tag: tag,
                _token: "{{ csrf_token() }}"
            }, function (resp) {
                jQuery.unblockUI();
                if (resp.status == 'error') {
                    alert(resp.msg);
                    return;
                }
                $("#VehicleRental").val('day');
                $("#VehicleDayRent").val(resp.data.day_rent);
                $("#frmadmin input[name='Vehicle[rent_opt][1][after_day]']").val(resp.data.rent_opt[0].after_day);
                $("#frmadmin input[name='Vehicle[rent_opt][1][amount]']").val(resp.data.rent_opt[0].amount);
                $("#frmadmin input[name='Vehicle[rent_opt][2][after_day]']").val('');
                $("#frmadmin input[name='Vehicle[rent_opt][2][amount]']").val('');
            }, 'json');
        }

        $(function () {
            $(".switch").bootstrapSwitch();
        });

    </script>
@endpush