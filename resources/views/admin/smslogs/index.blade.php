@extends('admin.layouts.app')

@section('title', $title_for_layout ?? 'SMS Logs')

@php
    $keyword ??= '';
    $status_type ??= '';
    $date_from ??= '';
    $date_to ??= '';
    $limit ??= 25;
    $statusOpt = ['1' => 'Sent', '2' => 'Recieved'];
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Sms</span> Logs
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('admin/smslogs/index') }}" class="form-horizontal">
                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-2">
                            {{ 'Phone# :' }}
                            <input type="text" name="Search[keyword]" class="form-control" maxlength="16" value="{{ $keyword }}" placeholder="Phone#">
                        </div>
                        <div class="col-md-2">
                            {{ 'Type :' }}
                            <select name="Search[status_type]" class="form-control">
                                <option value="">Select Type</option>
                                @foreach ($statusOpt as $k => $label)
                                    <option value="{{ $k }}" @selected((string) $status_type === (string) $k)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            {{ 'Date From :' }}
                            <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control" value="{{ $date_from }}" placeholder="Date Range From">
                        </div>
                        <div class="col-md-2">
                            {{ 'Date To :' }}
                            <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control" value="{{ $date_to }}" placeholder="Date Range To">
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="search" value="search" class="btn btn-primary">
                                {{ 'APPLY' }}
                            </button>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="ClearFilter" value="Clear Filter" class="btn btn-warning">
                                {{ 'Clear Filter' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                <div class="table-responsive">
                    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                        <thead>
                            <tr>
                                @include('partials.dispacher.sortable_header', ['columns' => [
                                    ['field' => 'id', 'title' => '#', 'style' => 'width:105px;'],
                                    ['field' => 'type', 'title' => 'Type'],
                                    ['field' => 'renter_phone', 'title' => 'Phone#'],
                                    ['field' => 'created', 'title' => 'TimeStamp'],
                                    ['field' => 'actions', 'title' => 'Action', 'sortable' => false, 'style' => 'width:80px;']
                                ]])
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($smslogs as $smslog)
                                @php $b64 = base64_encode((string) $smslog->id); @endphp
                                <tr id="tr_{{ (int) $smslog->id }}">
                                    <td>{{ (int) $smslog->id }}</td>
                                    <td>{{ ((int) ($smslog->type ?? 0) === 1) ? 'Sent' : 'Recieved' }}</td>
                                    <td>{{ $smslog->renter_phone ?? '' }}</td>
                                    <td>
                                        @if (!empty($smslog->created))
                                            {{ \Carbon\Carbon::parse($smslog->created)->format('m/d/Y h:i A') }}
                                        @endif
                                    </td>
                                    <td>
                                        <a href="javascript:void(0)" title="Message Details" onclick="messageDetail('{{ $b64 }}'); return false;"><i class="icon-clipboard3"></i></a>
                                        <a href="javascript:void(0)" title="Delete" onclick="deleteMessage('{{ $b64 }}'); return false;"><i class="icon-trash"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" align="center">No record found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @include('partials.dispacher.paging_box', ['paginator' => $smslogs, 'limit' => $limit])
            </div>
        </div>
    </div>

    <div id="myModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content"></div>
        </div>
    </div>
@endsection

@push('styles')
    <style type="text/css">
        .table>thead>tr>th,
        .table>tbody>tr>th,
        .table>tfoot>tr>th,
        .table>thead>tr>td,
        .table>tbody>tr>td,
        .table>tfoot>tr>td {
            padding: 5px;
        }
    </style>
@endpush

@push('scripts')
    <script src="{{ legacy_asset('js/colorbox.js') }}"></script>
    <script src="{{ asset('js/admin_booking.js') }}"></script>
    <script type="text/javascript">
        jQuery(document).ready(function () {
            if (jQuery.fn.datepicker) {
                jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
                jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });
            }
            if (jQuery.fn.colorbox) {
                jQuery('.clorbox').colorbox({ width: '700px' });
            }
        });
    </script>
@endpush
