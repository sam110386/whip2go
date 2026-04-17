@extends('admin.layouts.app')

@section('title', $listTitle)

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">Customer Balance</span> - {{ $listTitle }}
            </h4>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li><a href="{{ url('admin/users/index') }}">Users</a></li>
            <li><a href="{{ url('admin/customer_balances/index') }}">Customer Balances</a></li>
            <li class="active">{{ $balance ? 'Update' : 'Add' }} Charge</li>
        </ul>
    </div>
</div>

<div class="content">
    @include('partials.flash')

    <div class="row">
        <div class="col-md-8">
            <div class="panel panel-flat">
                <div class="panel-heading">
                    <h5 class="panel-title">Add / Edit Balance Record</h5>
                </div>

                <div class="panel-body">
                    <form method="POST" action="{{ url('admin/customer_balances/add', [$balance ? base64_encode((string)$balance->id) : '']) }}" class="form-horizontal">
                        @csrf
                        @if ($balance)
                            <input type="hidden" name="CsUserBalance[id]" value="{{ $balance->id }}">
                        @endif

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Target User ID:</label>
                            <div class="col-lg-9">
                                @if ($balance)
                                    <input type="hidden" name="CsUserBalance[user_id]" value="{{ $balance->user_id }}">
                                    <div class="form-control-static"><span class="label label-flat border-grey text-grey-600">{{ $balance->user_id }}</span></div>
                                @else
                                    <input type="number" name="CsUserBalance[user_id]" value="{{ old('CsUserBalance.user_id') }}" class="form-control" min="1" required placeholder="Enter User ID">
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Status:</label>
                            <div class="col-lg-9">
                                <select name="CsUserBalance[status]" class="form-control">
                                    <option value="1" @selected((int)old('CsUserBalance.status', $balance->status ?? 1) === 1)>Active</option>
                                    <option value="0" @selected((int)old('CsUserBalance.status', $balance->status ?? 1) === 0)>Inactive</option>
                                    <option value="2" @selected((int)old('CsUserBalance.status', $balance->status ?? 1) === 2)>Completed</option>
                                </select>
                            </div>
                        </div>

                        @if ($balance)
                        <div class="row mb-20 text-center">
                            <div class="col-xs-4">
                                <div class="text-uppercase text-size-mini text-muted">Credit</div>
                                <h6 class="text-semibold no-margin text-success">{{ number_format($balance->credit, 2) }}</h6>
                            </div>
                            <div class="col-xs-4">
                                <div class="text-uppercase text-size-mini text-muted">Debit</div>
                                <h6 class="text-semibold no-margin text-danger">{{ number_format($balance->debit, 2) }}</h6>
                            </div>
                            <div class="col-xs-4">
                                <div class="text-uppercase text-size-mini text-muted">Balance</div>
                                <h6 class="text-semibold no-margin text-primary">{{ number_format($balance->balance, 2) }}</h6>
                            </div>
                        </div>
                        <hr>
                        @endif

                        <fieldset>
                            <legend class="text-semibold">Update Balance</legend>
                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Action Type:</label>
                                <div class="col-lg-9">
                                    <select name="CsUserBalance[creditdebit]" class="form-control">
                                        <option value="credit">Charge To Driver (Add Credit)</option>
                                        <option value="debit">Give Refund/Debit to Customer</option>
                                    </select>
                                    <span class="help-block text-muted">Credit: increases amount owed by user. Debit: decreases it.</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Record Category:</label>
                                <div class="col-lg-9">
                                    <select name="CsUserBalance[type]" class="form-control">
                                        @foreach ($balanceTypes as $k => $label)
                                            <option value="{{ $k }}" @selected((string)old('CsUserBalance.type', $balance->type ?? '') === (string)$k)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Adjustment Amount ($):</label>
                                <div class="col-lg-9">
                                    <input type="number" step="0.01" name="CsUserBalance[balance]" value="{{ old('CsUserBalance.balance') }}" class="form-control" placeholder="0.00">
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="text-semibold">Capture Schedule</legend>
                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Payment Mode:</label>
                                <div class="col-lg-9">
                                    <select name="CsUserBalance[chargetype]" id="capture-mode" class="form-control">
                                        <option value="lumpsum" @selected(old('CsUserBalance.chargetype', $balance->chargetype ?? 'lumpsum') === 'lumpsum')>Lumpsum (One Time)</option>
                                        <option value="installment" @selected(old('CsUserBalance.chargetype', $balance->chargetype ?? '') === 'installment')>Installments (Tiers)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="installment-fields" style="display:none;">
                                <div class="form-group">
                                    <label class="col-lg-3 control-label text-semibold">Installment Frequency:</label>
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

                            <div class="form-group">
                                <label class="col-lg-3 control-label text-semibold">Processing Weekday:</label>
                                <div class="col-lg-9">
                                    <select name="CsUserBalance[installment_day]" class="form-control">
                                        @foreach ($weekdays as $k => $label)
                                            <option value="{{ $k }}" @selected(old('CsUserBalance.installment_day', $balance->installment_day ?? 'sun') === $k)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </fieldset>

                        <div class="form-group">
                            <label class="col-lg-3 control-label text-semibold">Internal Notes:</label>
                            <div class="col-lg-9">
                                <textarea name="CsUserBalance[note]" rows="3" class="form-control" maxlength="255" placeholder="Reason for adjustment...">{{ old('CsUserBalance.note', $balance->note ?? '') }}</textarea>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-primary">Save Balance Record <i class="icon-database-insert position-right"></i></button>
                            <a href="{{ url('admin/customer_balances/index') }}" class="btn btn-default">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="panel panel-flat border-top-primary">
                <div class="panel-heading">
                    <h5 class="panel-title">System Guidance</h5>
                </div>
                <div class="panel-body">
                    <p class="text-size-small">Use this form to add ad-hoc charges (Credits) or refunds (Debits) to a customer balance.</p>
                    <div class="alert alert-info border-grey alert-styled-left alert-xs">
                        Lumpsum payments will attempt to capture the full balance on the next processing cycle.
                    </div>
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
        } else {
            $('.installment-fields').slideUp();
        }
    }

    $('#capture-mode').on('change', toggleFields);
    toggleFields();
});
</script>
@endsection
