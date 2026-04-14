@extends('layouts.main')

@section('title', $title_for_layout ?? 'Renter Reports')

@push('css')
<link rel="stylesheet" href="{{ legacy_asset('css/colorbox.css') }}">
@endpush

@section('content')
@php
    $statusOpt = ['complete' => 'Complete', 'cancel' => 'Cancel', 'incomplete' => 'InComplete'];
    $searchIn = [1 => 'Renter Name', 3 => 'Order#', 4 => 'Renter Phone#'];
@endphp
<style type="text/css">
    tbody tr { cursor: pointer; }
    .table > thead > tr > th, .table > tbody > tr > th, .table > tfoot > tr > th,
    .table > thead > tr > td, .table > tbody > tr > td, .table > tfoot > tr > td { padding: 12px 15px; }
</style>
<div class="panel">
    <section class="right_content">
        <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;">
            <h3 style="width: 40%; float: left; padding: 10px;">Renter Reports</h3>
        </section>

        <form method="get" action="/report_renters/index" class="form-horizontal" id="frmSearchadmin" name="frmSearchadmin">
            <fieldset class="content-group" style="padding:0.35em 0.625em 0.75em">
                <div class="col-md-3">
                    <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Keyword">
                </div>
                <div class="col-md-3">
                    <select name="Search[searchin]" class="form-control">
                        <option value="">Select In</option>
                        @foreach($searchIn as $k => $label)
                            <option value="{{ $k }}" @selected((string)$fieldname === (string)$k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </fieldset>
            <fieldset class="content-group" style="padding:0.35em 0.625em 0.75em">
                <div class="col-md-3">
                    <select name="Search[status_type]" class="form-control">
                        <option value="">Select Type</option>
                        @foreach($statusOpt as $k => $label)
                            <option value="{{ $k }}" @selected($status_type === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control" value="{{ $date_from_display }}" placeholder="Date Range From">
                </div>
                <div class="col-md-3">
                    <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control" value="{{ $date_to_display }}" placeholder="Date Range To">
                </div>
                <div class="col-md-3">
                    <button type="submit" name="search" value="search" class="btn btn-primary">APPLY</button>
                </div>
            </fieldset>
            <div class="row" style="margin-top:8px;">
                <div class="col-md-2">
                    <label class="text-muted text-size-mini">Rows per page</label>
                    <select name="Record[limit]" class="form-control" onchange="this.form.submit()">
                        @foreach([25, 50, 100, 200] as $opt)
                            <option value="{{ $opt }}" @selected((int)$limit === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        <div style="width:100%; overflow: visible; margin-top: 16px;">
            @if($reportlists->count() > 0)
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                    <thead>
                        <tr>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Contact#</th>
                            <th>Total Booking</th>
                            <th style="width:80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportlists as $trip)
                            @php
                                $tid = base64_encode((string) ($trip->id ?? ''));
                                $uid = base64_encode((string) ($trip->user_id ?? ''));
                            @endphp
                            <tr>
                                <td onclick="openReportRenterDetails('{{ $tid }}');">{{ $trip->first_name }}</td>
                                <td onclick="openReportRenterDetails('{{ $tid }}');">{{ $trip->last_name }}</td>
                                <td onclick="openReportRenterDetails('{{ $tid }}');">{{ $trip->contact_number }}</td>
                                <td onclick="openReportRenterDetails('{{ $tid }}');">{{ $trip->totalbooking }}</td>
                                <td>
                                    <a href="#" title="History" onclick="openRenterHistoryFixed('{{ $uid }}'); return false;"><i class="icon-clipboard3"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div style="margin-top:12px;">{{ $reportlists->links() }}</div>
            @else
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="borderTable">
                    <tr>
                        <td colspan="5" align="center">No record found</td>
                    </tr>
                </table>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
    jQuery(document).ready(function () {
        jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
        jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });
    });
    function openReportRenterDetails(tripId) {
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Just a moment...</h1>',
        });
        jQuery.ajax({
            url: SITE_URL + 'report_renters/details/' + tripId,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function (data) {
                jQuery.unblockUI();
                jQuery('#myModal .modal-content').html(data);
                jQuery('#myModal').modal('show').find('.modal-dialog').css('width', '1050px');
            },
            error: function () {
                jQuery.unblockUI();
            },
        });
        return false;
    }
    function openRenterHistoryFixed(renterid) {
        jQuery.blockUI({
            message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> loading...</h1>',
        });
        jQuery.ajax({
            url: SITE_URL + 'report_renters/history',
            type: 'POST',
            data: { renterid: renterid, _token: '{{ csrf_token() }}' },
            success: function (data) {
                jQuery.unblockUI();
                jQuery('#myModal .modal-content').html(data);
                jQuery('#myModal').modal('show').find('.modal-dialog').css('width', '800px');
            },
            error: function () {
                jQuery.unblockUI();
            },
        });
    }
</script>
@endpush
