@extends('layouts.admin')

@section('title', 'Payment Setting Template')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">User</span> - Payment Setting Template
            </h4>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="{{ url('admin/users/index') }}">Users</a></li>
            <li class="active">Payment Setting Template</li>
        </ul>
    </div>
</div>

<div class="content">
    @include('layouts.flash-messages')

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Rental Fee Template</h5>
        </div>

        <div class="panel-body">
            <form method="POST" action="{{ url('admin/deposit_templates/index', $useridB64) }}" class="form-horizontal">
                @csrf
                
                <div class="row">
                    <div class="col-md-6">
                        <fieldset>
                            <legend class="text-semibold">Global Settings</legend>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Deposit Event:</label>
                                <div class="col-lg-9">
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="DepositTemplate[deposit_event]" value="N" @checked(($data['deposit_event'] ?? 'N') == 'N')>
                                            No Deposit
                                        </label>
                                    </div>
                                    <div class="radio">
                                        <label>
                                            <input type="radio" name="DepositTemplate[deposit_event]" value="Y" @checked(($data['deposit_event'] ?? 'N') == 'Y')>
                                            Charge Deposit
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Deposit Amount ($):</label>
                                <div class="col-lg-9">
                                    <input type="number" step="0.01" name="DepositTemplate[deposit_amt]" class="form-control" value="{{ $data['deposit_amt'] ?? 0 }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Initial Fee ($):</label>
                                <div class="col-lg-9">
                                    <input type="number" step="0.01" name="DepositTemplate[initial_fee]" class="form-control" value="{{ $data['initial_fee'] ?? 0 }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Selling Premium ($):</label>
                                <div class="col-lg-9">
                                    <input type="number" step="0.01" name="DepositTemplate[selling_premium]" class="form-control" value="{{ $data['selling_premium'] ?? 0 }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Roadside Assistance ($):</label>
                                <div class="col-lg-9">
                                    <input type="number" step="0.01" name="DepositTemplate[roadside_assistance_included]" class="form-control" value="{{ $data['roadside_assistance_included'] ?? 0 }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Maintenance Fee ($):</label>
                                <div class="col-lg-9">
                                    <input type="number" step="0.01" name="DepositTemplate[maintenance_included_fee]" class="form-control" value="{{ $data['maintenance_included_fee'] ?? 0 }}">
                                </div>
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-md-6">
                        <fieldset>
                            <legend class="text-semibold">Prepaid & Sync</legend>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Prepaid Initial Fee:</label>
                                <div class="col-lg-9">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="DepositTemplate[prepaid_initial_fee]" value="1" @checked(($data['prepaid_initial_fee'] ?? 0) == 1)>
                                            Enable
                                        </label>
                                    </div>
                                </div>
                            </div>

                            @php
                                $prepaidData = !empty($data['prepaid_initial_fee_data']) ? (is_array($data['prepaid_initial_fee_data']) ? $data['prepaid_initial_fee_data'] : json_decode($data['prepaid_initial_fee_data'], true)) : [];
                            @endphp
                            <div class="form-group">
                                <label class="col-lg-3 control-label">Prepaid Amount / Day:</label>
                                <div class="col-lg-4">
                                    <input type="number" step="0.01" name="DepositTemplate[prepaid_initial_fee_data][amount]" class="form-control" placeholder="Amount" value="{{ $prepaidData['amount'] ?? '' }}">
                                </div>
                                <div class="col-lg-5">
                                    <input type="number" name="DepositTemplate[prepaid_initial_fee_data][day]" class="form-control" placeholder="Day (e.g. 7)" value="{{ $prepaidData['day'] ?? '' }}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Default Fare Type:</label>
                                <div class="col-lg-9">
                                    <select name="DepositTemplate[fare_type]" class="form-control">
                                        <option value="D" @selected(($data['fare_type'] ?? 'D') == 'D')>Daily Rent</option>
                                        <option value="L" @selected(($data['fare_type'] ?? '') == 'L')>Lease/Subscription</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label">Buy Fee ($):</label>
                                <div class="col-lg-9">
                                    <input type="number" step="0.01" name="DepositTemplate[buy_fee]" class="form-control" value="{{ $data['buy_fee'] ?? 0 }}">
                                </div>
                            </div>
                        </fieldset>
                    </div>
                </div>

                    <div class="col-md-6">
                        <fieldset>
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
                                            if (empty($deptOpt)) $deptOpt = [[]];
                                        @endphp
                                        @foreach($deptOpt as $idx => $opt)
                                        <tr>
                                            <td><input type="number" name="DepositTemplate[deposit_amt_opt][{{ $idx }}][after_day]" class="form-control" value="{{ $opt['after_day'] ?? '' }}"></td>
                                            <td><input type="number" step="0.01" name="DepositTemplate[deposit_amt_opt][{{ $idx }}][amount]" class="form-control" value="{{ $opt['amount'] ?? '' }}"></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-icon remove-row"><i class="icon-trash"></i></button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-right mt-10">
                                <button type="button" class="btn btn-default btn-xs" id="add-deposit-opt"><i class="icon-plus2"></i> Add Tier</button>
                            </div>
                        </fieldset>
                    </div>

                    <div class="col-md-6">
                        <fieldset>
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
                                            if (empty($initOpt)) $initOpt = [[]];
                                        @endphp
                                        @foreach($initOpt as $idx => $opt)
                                        <tr>
                                            <td><input type="number" name="DepositTemplate[initial_fee_opt][{{ $idx }}][after_day]" class="form-control" value="{{ $opt['after_day'] ?? '' }}"></td>
                                            <td><input type="number" step="0.01" name="DepositTemplate[initial_fee_opt][{{ $idx }}][amount]" class="form-control" value="{{ $opt['amount'] ?? '' }}"></td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-icon remove-row"><i class="icon-trash"></i></button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-right mt-10">
                                <button type="button" class="btn btn-default btn-xs" id="add-initial-opt"><i class="icon-plus2"></i> Add Tier</button>
                            </div>
                        </fieldset>
                    </div>
                </div>

                <div class="row mt-20">
                    <div class="col-md-12">
                        <fieldset>
                            <legend class="text-semibold">Incentives</legend>
                            <p class="text-muted">Apply specific incentive amounts based on vehicle make and model.</p>
                            
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
                                            if (empty($incentives)) $incentives = [[]];
                                        @endphp
                                        @foreach($incentives as $idx => $inc)
                                        <tr>
                                            <td>
                                                <select name="DepositTemplate[incentives][{{ $idx }}][make]" class="form-control select-make">
                                                    <option value="">Any Make</option>
                                                    @foreach($makes as $m)
                                                        <option value="{{ $m }}" @selected(($inc['make'] ?? '') == $m)>{{ $m }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="DepositTemplate[incentives][{{ $idx }}][model]" class="form-control" placeholder="Model" value="{{ $inc['model'] ?? '' }}">
                                            </td>
                                            <td>
                                                <input type="number" name="DepositTemplate[incentives][{{ $idx }}][year]" class="form-control" placeholder="Year" value="{{ $inc['year'] ?? '' }}">
                                            </td>
                                            <td>
                                                <input type="text" name="DepositTemplate[incentives][{{ $idx }}][trim]" class="form-control" placeholder="Trim" value="{{ $inc['trim'] ?? '' }}">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="DepositTemplate[incentives][{{ $idx }}][amount]" class="form-control" placeholder="Amount" value="{{ $inc['amount'] ?? '' }}">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-icon remove-row"><i class="icon-trash"></i></button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-right mt-10">
                                <button type="button" class="btn btn-default" id="add-incentive"><i class="icon-plus2 position-left"></i> Add Incentive</button>
                            </div>
                        </fieldset>
                    </div>
                </div>

                <div class="text-right mt-20">
                    <button type="submit" class="btn btn-primary">Save Settings <i class="icon-arrow-right14 position-right"></i></button>
                    <a href="{{ url('admin/users/index') }}" class="btn btn-default">Cancel</a>
                    <button type="button" class="btn btn-info" id="sync-vehicles">Sync to All Vehicles <i class="icon-sync position-right"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add Incentive Row
    $('#add-incentive').click(function() {
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
    $('#add-deposit-opt').click(function() {
        var idx = $('#deposit-opt-table tbody tr').length;
        var row = `<tr>
            <td><input type="number" name="DepositTemplate[deposit_amt_opt][${idx}][after_day]" class="form-control"></td>
            <td><input type="number" step="0.01" name="DepositTemplate[deposit_amt_opt][${idx}][amount]" class="form-control"></td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-icon remove-row"><i class="icon-trash"></i></button></td>
        </tr>`;
        $('#deposit-opt-table tbody').append(row);
    });

    // Add Initial Fee Tier
    $('#add-initial-opt').click(function() {
        var idx = $('#initial-opt-table tbody tr').length;
        var row = `<tr>
            <td><input type="number" name="DepositTemplate[initial_fee_opt][${idx}][after_day]" class="form-control"></td>
            <td><input type="number" step="0.01" name="DepositTemplate[initial_fee_opt][${idx}][amount]" class="form-control"></td>
            <td class="text-center"><button type="button" class="btn btn-danger btn-icon remove-row"><i class="icon-trash"></i></button></td>
        </tr>`;
        $('#initial-opt-table tbody').append(row);
    });

    // Remove Row
    $(document).on('click', '.remove-row', function() {
        if ($('#incentives-table tbody tr').length > 1) {
            $(this).closest('tr').remove();
        } else {
            $(this).closest('tr').find('input, select').val('');
        }
    });

    // Sync to Vehicles
    $('#sync-vehicles').click(function() {
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
            success: function(response) {
                if (response.status) {
                    alert(response.message);
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function() {
                alert('An error occurred during synchronization.');
            },
            complete: function() {
                btn.html(oldHtml).prop('disabled', false);
            }
        });
    });
});
</script>
@endsection
