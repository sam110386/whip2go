@extends('admin.layouts.app')

@section('title', ($balance ? 'Edit' : 'Add') . ' Dealer Charge')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">User</span> - {{ $balance ? 'Edit' : 'Add' }} Subscription / Charge
            </h4>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="{{ url('admin/users/index') }}">Users</a></li>
            <li><a href="{{ url('admin/customer_balances/index') }}">Customer Balances</a></li>
            <li><a href="{{ url('admin/customer_balances/subscription', $useridB64) }}">Subscriptions</a></li>
            <li class="active">{{ $balance ? 'Edit' : 'Add' }}</li>
        </ul>
    </div>
</div>

<div class="content">
    @include('partials.flash')

    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-flat">
                <div class="panel-heading">
                    <h5 class="panel-title">Charge Details</h5>
                </div>

                <div class="panel-body">
                    <form method="POST" action="{{ url('admin/customer_balances/addsubscription', [$useridB64, ($balance ? base64_encode((string)$balance->id) : '')]) }}" class="form-horizontal">
                        @csrf
                        @if ($balance)
                            <input type="hidden" name="CsUserBalance[id]" value="{{ $balance->id }}">
                        @endif

                        <div class="row mb-20 text-center">
                            <div class="col-xs-4">
                                <div class="text-uppercase text-size-mini text-muted">Credit</div>
                                <h5 class="text-semibold no-margin text-success">{{ number_format($balance->credit ?? 0, 2) }}</h5>
                            </div>
                            <div class="col-xs-4">
                                <div class="text-uppercase text-size-mini text-muted">Debit</div>
                                <h5 class="text-semibold no-margin text-danger">{{ number_format($balance->debit ?? 0, 2) }}</h5>
                            </div>
                            <div class="col-xs-4">
                                <div class="text-uppercase text-size-mini text-muted">Balance</div>
                                <h5 class="text-semibold no-margin text-primary">{{ number_format($balance->balance ?? 0, 2) }}</h5>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Charge Type:</label>
                            <div class="col-lg-9">
                                <select name="CsUserBalance[type]" class="form-control select">
                                    @foreach ($balanceTypes as $k => $label)
                                        <option value="{{ $k }}" @selected((int)old('CsUserBalance.type', $balance->type ?? 9) === (int)$k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Amount ($):</label>
                            <div class="col-lg-9">
                                <input type="number" step="0.01" name="CsUserBalance[balance]" value="{{ old('CsUserBalance.balance', $balance->balance ?? '') }}" class="form-control" placeholder="0.00">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Capture Mode:</label>
                            <div class="col-lg-9">
                                @php
                                    $ct = old('CsUserBalance.chargetype', $balance ? ($balance->chargetype ?? 'subscription') : 'subscription');
                                @endphp
                                <select name="CsUserBalance[chargetype]" id="capture-mode" class="form-control">
                                    <option value="subscription" @selected($ct === 'subscription')>Subscription</option>
                                    <option value="lumpsum" @selected($ct === 'lumpsum')>Lumpsum</option>
                                    <option value="installment" @selected($ct === 'installment')>Installment</option>
                                </select>
                            </div>
                        </div>

                        <div class="installment-fields" style="display:none;">
                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Installment Type:</label>
                                <div class="col-lg-9">
                                    <select name="CsUserBalance[installment_type]" class="form-control">
                                        <option value="daily" @selected(old('CsUserBalance.installment_type', $balance->installment_type ?? 'daily') === 'daily')>Daily</option>
                                        <option value="weekly" @selected(old('CsUserBalance.installment_type', $balance->installment_type ?? '') === 'weekly')>Weekly</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Partial Amount ($):</label>
                                <div class="col-lg-9">
                                    <input type="number" step="0.01" name="CsUserBalance[installment]" value="{{ old('CsUserBalance.installment', $balance->installment ?? '0') }}" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="weekday-fields">
                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Processing Day:</label>
                                <div class="col-lg-9">
                                    <select name="CsUserBalance[installment_day]" class="form-control">
                                        @foreach ($weekdays as $k => $label)
                                            <option value="{{ $k }}" @selected(old('CsUserBalance.installment_day', $balance->installment_day ?? 'sun') === $k)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Notes:</label>
                            <div class="col-lg-9">
                                <textarea name="CsUserBalance[note]" rows="3" class="form-control" maxlength="255" placeholder="Internal notes...">{{ old('CsUserBalance.note', $balance->note ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">{{ $balance ? 'Update' : 'Create' }} Charge <i class="icon-arrow-right14 position-right"></i></button>
                            <a href="{{ url('admin/customer_balances/subscription', $useridB64) }}" class="btn btn-default">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="panel panel-flat">
                <div class="panel-heading">
                    <h5 class="panel-title">Capture Rules</h5>
                </div>
                <div class="panel-body">
                    <ul class="list-feed">
                        <li>
                            <span class="text-semibold">Subscription:</span> Charges fixed amount on selected processing day.
                        </li>
                        <li>
                            <span class="text-semibold">Lumpsum:</span> One-time full charge.
                        </li>
                        <li>
                            <span class="text-semibold">Installment:</span> Splits total amount into daily/weekly pieces.
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function toggleFields() {
        var mode = $('#capture-mode').val();
        if (mode === 'installment') {
            $('.installment-fields').slideDown();
            $('.weekday-fields').slideDown();
        } else if (mode === 'subscription') {
            $('.installment-fields').slideUp();
            $('.weekday-fields').slideUp();
        } else { // lumpsum
            $('.installment-fields').slideUp();
            $('.weekday-fields').slideDown();
        }
    }

    $('#capture-mode').on('change', toggleFields);
    toggleFields();
});
</script>
@endsection
