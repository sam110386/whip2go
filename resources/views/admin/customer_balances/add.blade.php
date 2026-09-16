@extends('admin.layouts.app')
@php
    $title ??= 'Add Credit & Debit Charge';
    $csUserBalance ??= collect();
    $balanceTypes ??= [];
    $weekdays ??= [];
@endphp
@section('title', $title)

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold"> </span> {{ $title }}
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="row">
                <form method="POST"
                    action="{{ data_get($csUserBalance, 'id', false) ? url('admin/customer_balances/add/' . base64_encode(data_get($csUserBalance, 'id'))) : url('admin/customer_balances/add') }}"
                    class="form-horizontal" id="frmadmin" name="frmadmin">
                    @csrf

                    <div class="col-lg-12">
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="col-lg-4 control-label">
                                    Driver :
                                </label>
                                <div class="col-lg-8">
                                    <input type="text" name="CsUserBalance[user_id]" id="CsUserBalanceUserId"
                                        class="number required" style="width:100%"
                                        value="{{ old('CsUserBalance.user_id', data_get($csUserBalance, 'user_id', '')) }}">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label">
                                    Status :
                                </label>
                                <div class="col-lg-8">
                                    <select name="CsUserBalance[status]" class="form-control">
                                        <option value="1" @selected(old('CsUserBalance.status', data_get($csUserBalance, 'status', 1)) == 1)>
                                            Active
                                        </option>
                                        <option value="0" @selected(old('CsUserBalance.status', data_get($csUserBalance, 'status', 1)) == 0)>
                                            Inactive
                                        </option>
                                        <option value="2" @selected(old('CsUserBalance.status', data_get($csUserBalance, 'status', 1)) == 2)>
                                            Completed
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="form-group">
                                <label class="col-lg-4 control-label text-bold">
                                    Charge To Driver Amount :
                                </label>
                                <div class="col-lg-8">
                                    {{ data_get($csUserBalance, 'credit', 0) }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label text-bold">
                                    Debit :
                                </label>
                                <div class="col-lg-8">
                                    {{ data_get($csUserBalance, 'debit', 0) }}
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-4 control-label text-bold">
                                    Balance :
                                </label>
                                <div class="col-lg-8">
                                    {{ data_get($csUserBalance, 'balance', 0) }}
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <legend>
                                <center>Update Balance</center>
                            </legend>

                            <div class="form-group">
                                <label class="col-lg-4 control-label">
                                    Credit/Debit :
                                </label>
                                <div class="col-lg-8">
                                    <select name="CsUserBalance[creditdebit]" class="form-control">
                                        <option value="credit">Charge To Driver</option>
                                    </select>
                                    <em>Credit : Charge to Driver, Debit : Give Refund to Customer</em>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-4 control-label">
                                    Type :
                                </label>
                                <div class="col-lg-8">
                                    <select name="CsUserBalance[type]" class="form-control">
                                        @foreach ($balanceTypes as $k => $label)
                                            <option value="{{ $k }}" @selected(old('CsUserBalance.type', data_get($csUserBalance, 'type', '')) == $k)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-4 control-label">
                                    Amount :
                                </label>
                                <div class="col-lg-8">
                                    <input type="number" step="0.01" name="CsUserBalance[balance]"
                                        value="{{ old('CsUserBalance.balance', data_get($csUserBalance, 'balance', '')) }}"
                                        class="number form-control required">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <legend>
                                <center>Balance Capture Setting</center>
                            </legend>

                            <div class="form-group">
                                <label class="col-lg-4 control-label text-right">
                                    Capture As :
                                </label>
                                <div class="col-lg-8">
                                    <select name="CsUserBalance[chargetype]" id="CsUserBalanceChargetype"
                                        class="form-control">
                                        <option value="lumpsum" @selected(old('CsUserBalance.chargetype', data_get($csUserBalance, 'chargetype', '')) === 'lumpsum')>
                                            Lumpsum
                                        </option>
                                        <option value="installment" @selected(old('CsUserBalance.chargetype', data_get($csUserBalance, 'chargetype', '')) === 'installment')>
                                            Installment
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group installment" @if(old('CsUserBalance.chargetype', data_get($csUserBalance, 'chargetype', '')) !== 'installment') style="display:none;" @endif>
                                <label class="col-lg-4 control-label text-right">
                                    Installment Type :
                                </label>
                                <div class="col-lg-8">
                                    <select name="CsUserBalance[installment_type]" class="form-control">
                                        <option value="daily" @selected(old('CsUserBalance.installment_type', data_get($csUserBalance, 'installment_type', '')) === 'daily')>
                                            Daily
                                        </option>
                                        <option value="weekly" @selected(old('CsUserBalance.installment_type', data_get($csUserBalance, 'installment_type', '')) === 'weekly')>
                                            Weekly
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-4 control-label text-right">
                                    Week Day :
                                </label>
                                <div class="col-lg-8">
                                    <select name="CsUserBalance[installment_day]" class="form-control">
                                        @foreach ($weekdays as $k => $label)
                                            <option value="{{ $k }}" @selected(old('CsUserBalance.installment_day', data_get($csUserBalance, 'installment_day', '')) === $k)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group installment" @if(old('CsUserBalance.chargetype', data_get($csUserBalance, 'chargetype', '')) !== 'installment') style="display:none;" @endif>
                                <label class="col-lg-4 control-label text-right">
                                    Installment :
                                </label>
                                <div class="col-lg-8">
                                    <input type="number" step="0.01" name="CsUserBalance[installment]"
                                        value="{{ old('CsUserBalance.installment', data_get($csUserBalance, 'installment', '')) }}"
                                        class="digit form-control">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <div class="form-group">
                                <label class="col-lg-2 control-label">
                                    Note :
                                </label>
                                <div class="col-lg-8">
                                    <textarea name="CsUserBalance[note]" rows="3" class="form-control"
                                        maxlength="255">{{ old('CsUserBalance.note', data_get($csUserBalance, 'note', '')) }}</textarea>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-lg-2 control-label">&nbsp;</label>
                                <div class="col-lg-3">
                                    <button type="submit" class="btn left-margin btn-warning btn-block">
                                        Save
                                    </button>
                                </div>
                                <div class="col-lg-3">
                                    <button type="button" onclick="goBack('/admin/customer_balances/index')"
                                        class="btn bg-pink btn-block">
                                        Return
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="CsUserBalance[id]" value="{{ data_get($csUserBalance, 'id', '')}}">
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/select2.js') }}"></script>
    <script type="text/javascript">

        function format(item) {
            return item.tag;
        }

        jQuery(document).ready(function () {
            jQuery("#CsUserBalanceUserId").select2({
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
                            term: params
                        }
                    },
                    processResults: function (data) {
                        return {
                            results: jQuery.map(data, function (item) {
                                return {
                                    tag: item.tag,
                                    id: item.id
                                }
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    var renter_id = "{{ data_get($csUserBalance, 'user_id', '') }}";
                    if (renter_id.length > 0) {
                        jQuery.ajax({
                            url: "{{ url('admin/bookings/customerautocomplete') }}",
                            dataType: "json",
                            type: "GET",
                            data: { "id": renter_id }
                        }).done(function (data) {
                            callback(data[0]);
                        });
                    }
                }
            });



            $("#frmadmin").validate();
            $("#CsUserBalanceChargetype").change(function () {
                if ($(this).val() === 'installment') {
                    $(".installment").show();
                    $(".subscription").show();
                } else if ($(this).val() === 'subscription') {
                    $(".installment").hide();
                    $(".subscription").hide();
                } else {
                    $(".installment").hide();
                    $(".subscription").show();
                }
            });
        });   
    </script>
@endpush