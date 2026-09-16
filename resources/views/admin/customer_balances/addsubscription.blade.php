@extends('admin.layouts.app')

@php
    $csUserBalances ??= collect();
    $title ??= 'Dealer Charges';
    $balanceTypes ??= [];
    $userid ??= '';
    $id ??= '';
@endphp

@section('title', $title)

@section('content')

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <div class="row">
                <form method="POST"
                    action="{{ url('admin/customer_balances/addsubscription/' . base64_encode($userid) . '/' . base64_encode(data_get($csUserBalances, 'id', ''))) }}"
                    class="form-horizontal" id="frmadmin" name="frmadmin">
                    @csrf

                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="col-lg-2 control-label">
                                Credit :
                            </label>
                            <div class="col-lg-4">
                                {{ data_get($csUserBalances, 'credit', 0) }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">
                                Debit :
                            </label>
                            <div class="col-lg-4">
                                {{data_get($csUserBalances, 'debit', 0)}}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">
                                Balance :
                            </label>
                            <div class="col-lg-4">
                                {{ data_get($csUserBalances, 'balance', 0)  }}
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <legend>
                            <center>Charge Balance</center>
                        </legend>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">
                                Type :
                            </label>
                            <div class="col-lg-8">
                                <select name="CsUserBalance[type]" class="form-control">
                                    @foreach ($balanceTypes as $k => $label)
                                        <option value="{{ $k }}" @selected(old('CsUserBalance.type', data_get($csUserBalances, 'type', 9)) === $k)>
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
                                    value="{{ old('CsUserBalance.balance', data_get($csUserBalances, 'balance', '')) }}"
                                    class="number form-control required" placeholder="0.00">
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
                                @php
                                    $ct = old('CsUserBalance.chargetype', data_get($csUserBalances, 'chargetype', 'subscription'));
                                @endphp
                                <select name="CsUserBalance[chargetype]" id="CsUserBalanceChargetype" class="form-control">
                                    <option value="subscription" @selected($ct === 'subscription')>
                                        Subscription
                                    </option>
                                    <option value="lumpsum" @selected($ct === 'lumpsum')>
                                        Lumpsum
                                    </option>
                                    <option value="installment" @selected($ct === 'installment')>
                                        Installment
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group installment" @if($ct !== 'installment') style="display:none;" @endif>
                            <label class="col-lg-4 control-label text-right">
                                Installment Type :
                            </label>
                            <div class="col-lg-8">
                                <select name="CsUserBalance[installment_type]" class="form-control">
                                    <option value="daily" @selected(old('CsUserBalance.installment_type', data_get($csUserBalances, 'installment_type', 'weekly')) === 'daily')>
                                        Daily
                                    </option>
                                    <option value="weekly" @selected(old('CsUserBalance.installment_type', data_get($csUserBalances, 'installment_type', 'weekly')) === 'weekly')>
                                        Weekly
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group subscription" @if($ct === 'subscription') style="display:none;" @endif>
                            <label class="col-lg-4 control-label text-right">
                                Week Day :
                            </label>
                            <div class="col-lg-8">
                                <select name="CsUserBalance[installment_day]" class="form-control">
                                    @foreach ($weekdays as $k => $label)
                                        <option value="{{ $k }}" @selected(old('CsUserBalance.installment_day', data_get($csUserBalances, 'installment_day', 'sun')) === $k)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group installment" @if($ct !== 'installment') style="display:none;" @endif>
                            <label class="col-lg-4 control-label text-right">
                                Installment :
                            </label>
                            <div class="col-lg-8">
                                <input type="number" step="0.01" name="CsUserBalance[installment]"
                                    value="{{ old('CsUserBalance.installment', data_get($csUserBalances, 'installment', '0')) }}"
                                    class="form-control">
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
                                    maxlength="255">{{ old('CsUserBalance.note', data_get($csUserBalances, 'note', '')) }}</textarea>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                <button type="submit" class="btn left-margin btn-warning">
                                    {{ data_get($csUserBalances, 'id', false) ? 'Update' : 'Create' }}
                                </button>
                                <a href="{{ url('admin/customer_balances/subscription/' . base64_encode($userid)) }}"
                                    class="btn left-margin btn-cancel">
                                    Return
                                </a>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="CsUserBalance[id]" value="{{ data_get($balance, 'id', '') }}">
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script type="text/javascript">
        $(document).ready(function () {
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