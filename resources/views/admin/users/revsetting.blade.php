@extends('admin.layouts.app')

@php
    $title ??= 'Revenue Setting';
    $revSetting ??= collect();
    $user_id ??= '';
@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Revenue</span> Setting
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form action="{{ url('admin/users/revsetting', base64_encode($user_id)) }}" method="POST" id="frmadmin"
                name="frmadmin" class="form-horizontal">
                @csrf

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Revenue :<span class="text-danger">*</span>
                    </label>
                    <div class="col-lg-4">
                        <input type="number" name="rev" value="{{ old('rev', data_get($revSetting, 'rev', '')) }}"
                            class="digit form-control required">
                    </div>
                    <em>This will be used to transfer the dealer part</em>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Transfer Revenue :<span class="text-danger">*</span>
                    </label>
                    <div class="col-lg-4">
                        <select type="number" name="transfer_rev" class="form-control required">
                            <option value="1" @selected(old('transfer_rev', data_get($revSetting, 'transfer_rev', 1)) == 1)>
                                Yes
                            </option>
                            <option value="0" @selected(old('transfer_rev', data_get($revSetting, 'transfer_rev', 1)) == 0)>
                                No
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Transfer Insurance :<span class="text-danger">*</span>
                    </label>
                    <div class="col-lg-4">
                        <select name="transfer_insu" class="form-control">
                            <option value="1" @selected(old('transfer_insu', data_get($revSetting, 'transfer_insu', 1)) == 1)>
                                Yes
                            </option>
                            <option value="0" @selected(old('transfer_insu', data_get($revSetting, 'transfer_insu', 1)) == 0)>
                                No
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Rental Revenue :<span class="text-danger">*</span>
                    </label>
                    <div class="col-lg-4">
                        <input type="number" name="rental_rev"
                            value="{{ old('rental_rev', data_get($revSetting, 'rental_rev', '')) }}"
                            class="digit form-control required">
                    </div>
                    <em>This will be used to Rental Calculation</em>
                </div>

                <div class="form-group" id="ssn_noblk">
                    <label class="col-lg-2 control-label">
                        Tax Included :<span class="text-danger">*</span>
                    </label>
                    <div class="col-lg-4">
                        <select name="tax_included" class="form-control required">
                            <option value="1" @selected(old('tax_included', data_get($revSetting, 'tax_included', 1)) == 1)>
                                Yes
                            </option>
                            <option value="0" @selected(old('tax_included', data_get($revSetting, 'tax_included', 1)) == 0)>
                                No
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        DIA Fee :<span class="text-danger">*</span>
                    </label>
                    <div class="col-lg-4">
                        <input type="number" name="dia_fee"
                            value="{{ old('dia_fee', data_get($revSetting, 'dia_fee', '')) }}"
                            class="digit form-control required">
                        <em>Enter value to apply DIA fee on booking</em>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">&nbsp;</label>
                    <div class="col-lg-6">
                        <button type="submit" class="btn">
                            Save
                        </button>
                        <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/users/index')">
                            Return
                        </button>
                    </div>
                </div>

                <input type="hidden" name="id" value="{{ data_get($revSetting, 'id', '') }}">
                <input type="hidden" name="user_id" value="{{ $user_id }}">

            </form>
        </div>
    </div>

@endsection

@push('scripts')
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#frmadmin").validate();
        });
    </script>
@endpush