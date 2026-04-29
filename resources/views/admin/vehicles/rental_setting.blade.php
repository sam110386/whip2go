@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Vehicle Fee Setting')

@section('content')
    @php
        $base = $vehicleBasePath ?? '/admin/vehicles';
        $returnUrl = $returnListUrl ?? ($base . '/index');
        $fareType = data_get($vehicle, 'fare_type', '');
        $depositEvent = data_get($depositRule, 'deposit_event', 'P');
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Vehicle</span> - Fee Setting
                </h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <button type="submit" form="frmadmin" class="btn btn-primary">Save</button>
                    <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <form method="POST" action="{{ $base }}/rental_setting/{{ base64_encode((string)($id ?? 0)) }}"
          id="frmadmin" name="frmadmin" class="form-horizontal">
        @csrf

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">Vehicle</h5>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">ID :</label>
                    <div class="col-lg-9">
                        <p class="form-control-static">{{ data_get($vehicle, 'vehicle_unique_id', '-') }}</p>
                        <input type="hidden" name="DepositRule[vehicle_id]" value="{{ data_get($vehicle, 'id', $id) }}">
                        <input type="hidden" name="DepositRule[user_id]" value="{{ data_get($vehicle, 'user_id', 0) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="panel panel-flat">
            <div class="panel-heading">
                <h5 class="panel-title">Fees</h5>
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-3 control-label">Rate :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[rate]" maxlength="15" class="form-control"
                               value="{{ data_get($vehicle, 'rate', '') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Day Rent :</label>
                    <div class="col-lg-9">
                        <input type="text" name="Vehicle[day_rent]" maxlength="10" class="form-control"
                               value="{{ data_get($vehicle, 'day_rent', '') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Fare Type :</label>
                    <div class="col-lg-9">
                        <select name="Vehicle[fare_type]" class="form-control">
                            <option value="S" @selected($fareType === 'S')>Static</option>
                            <option value="D" @selected($fareType === 'D')>Dynamic</option>
                            <option value="L" @selected($fareType === 'L')>Lease Plus Pricing</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Deposit Event :</label>
                    <div class="col-lg-9">
                        <select name="DepositRule[deposit_event]" class="form-control">
                            <option value="P" @selected($depositEvent === 'P')>At Booking</option>
                            <option value="S" @selected($depositEvent === 'S')>Start Event</option>
                            <option value="E" @selected($depositEvent === 'E')>Booking End</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Deposit Amount :</label>
                    <div class="col-lg-9">
                        <input type="text" name="DepositRule[deposit_amt]" class="form-control"
                               value="{{ data_get($depositRule, 'deposit_amt', 0) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Initial Fee :</label>
                    <div class="col-lg-9">
                        <input type="text" name="DepositRule[initial_fee]" class="form-control"
                               value="{{ data_get($depositRule, 'initial_fee', 0) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Tax :</label>
                    <div class="col-lg-9">
                        <input type="text" name="DepositRule[tax]" maxlength="10" class="form-control"
                               value="{{ data_get($depositRule, 'tax', 0) }}">
                        <span class="help-block">% Amount in USD</span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-3 control-label">Insurance Fee :</label>
                    <div class="col-lg-9">
                        <input type="text" name="DepositRule[insurance_fee]" maxlength="10" class="form-control"
                               value="{{ data_get($depositRule, 'insurance_fee', 0) }}">
                        <span class="help-block">Flat Amount in USD (Per day)</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="col-lg-12">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ $returnUrl }}" class="btn btn-default">Return</a>
            </div>
        </div>
    </form>
@endsection
