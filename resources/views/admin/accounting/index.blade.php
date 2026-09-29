@extends('admin.layouts.app')

@php
    $title ??= 'Account Reports';
    $keyword ??= '';
    $date_from ??= '';
    $date_to ??= '';
    $rtype ??= '';
    $type ??= '';
    $userid ??= '';
@endphp

@section('title', $title)

@section('content')

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Accounting</span> - Reports
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <form id="frmSearchadmin" name="frmSearchadmin" method="GET"
            action="{{ url('admin/accounting_reports/index', $userid) }}" class="form-horizontal">
            <div class="panel-body">
                <div class="col-md-2">
                    <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}"
                        placeholder="Keyword">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control"
                        value="{{ !empty($date_from) ? \Carbon\Carbon::parse($date_from)->format('m/d/Y') : '' }}"
                        placeholder="Date Range From">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control"
                        value="{{ !empty($date_to) ? \Carbon\Carbon::parse($date_to)->format('m/d/Y') : '' }}"
                        placeholder="Date Range To">
                </div>
                <div class="col-md-2">
                    <select name="Search[rtype]" class="form-control">
                        <option value="">
                            Payment
                        </option>
                        <option value="D" @selected($rtype === 'D')>
                            Debit
                        </option>
                        <option value="C" @selected($rtype === 'C')>
                            Credit
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="Search[type]" class="form-control">
                        <option value="">Type</option>
                        @foreach(\App\Services\Legacy\Reportlib::getPaymentType(true) as $k => $v)
                            <option value="{{ $k }}" @selected($type == $k)>
                                {{ $v }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" value="search" class="btn btn-primary" alt="Next">
                        APPLY
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="panel">
        <div class="panel-body">
            @include('admin.accounting.elements.index')
        </div>
    </div>

@endsection

@push('scripts')
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
            jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });
        });
    </script>
    <script src="{{ legacy_asset('js/accounting/report.js') }}"></script>
@endpush