@extends('layouts.admin')

@section('title', $title_for_layout ?? 'SMS Logs')

@section('content')
@php
    $statusOpt = ['1' => 'Sent', '2' => 'Recieved'];
@endphp
<div class="panel">
    <section class="right_content">
        <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;">
            <h3 style="width: 40%; float: left; padding: 10px;">Sms Logs</h3>
        </section>

        <form method="post" action="/admin/smslogs/index" id="frmSearchadmin" class="form-horizontal">
            @csrf
            <fieldset class="content-group" style="padding:0.35em 0.625em 0.75em">
                <div class="col-md-2">
                    <input type="text" name="Search[keyword]" class="form-control" maxlength="16"
                           value="{{ e($keyword ?? '') }}" placeholder="Phone#">
                </div>
                <div class="col-md-2">
                    <select name="Search[status_type]" class="form-control">
                        <option value="">Select Type</option>
                        @foreach($statusOpt as $k => $label)
                            <option value="{{ $k }}" @if((string)($status_type ?? '') === (string)$k) selected @endif>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_from]" id="SearchDateFrom" class="form-control"
                           value="{{ e($date_from ?? '') }}" placeholder="Date Range From">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_to]" id="SearchDateTo" class="form-control"
                           value="{{ e($date_to ?? '') }}" placeholder="Date Range To">
                </div>
                <div class="col-md-2">
                    <button type="submit" name="search" value="search" class="btn btn-primary">APPLY</button>
                </div>
            </fieldset>
        </form>

        <div style="width:100%; overflow: visible;">
            @if($smslogs && $smslogs->count())
                <div style="margin:10px 0;">{{ $smslogs->links() }}</div>
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                    <thead>
                        <tr>
                            <th style="width:105px;">#</th>
                            <th>Type</th>
                            <th>Phone#</th>
                            <th>TimeStamp</th>
                            <th style="width:80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($smslogs as $smslog)
                            @php $b64 = base64_encode((string)$smslog->id); @endphp
                            <tr id="tr_{{ (int)$smslog->id }}">
                                <td>{{ (int)$smslog->id }}</td>
                                <td>{{ ((int)($smslog->type ?? 0) === 1) ? 'Sent' : 'Recieved' }}</td>
                                <td>{{ e($smslog->renter_phone ?? '') }}</td>
                                <td>
                                    @if(!empty($smslog->created))
                                        {{ \Carbon\Carbon::parse($smslog->created)->format('m/d/Y h:i A') }}
                                    @endif
                                </td>
                                <td>
                                    <a href="javascript:void(0)" title="Message Details" onclick="messageDetail('{{ $b64 }}'); return false;"><i class="icon-clipboard3"></i></a>
                                    <a href="javascript:void(0)" title="Delete" onclick="deleteMessage('{{ $b64 }}'); return false;"><i class="icon-trash"></i></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div style="margin:10px 0;">{{ $smslogs->links() }}</div>
            @else
                <table width="100%" cellpadding="1" cellspacing="1" border="0" class="borderTable">
                    <tr><td colspan="6" align="center">No record found</td></tr>
                </table>
            @endif
        </div>

        <form method="post" action="/admin/smslogs/index" class="form-inline" style="margin-top:12px;">
            @csrf
            <label>Per page</label>
            <select name="Record[limit]" class="form-control" onchange="this.form.submit()">
                @foreach([25, 50, 100, 200] as $lim)
                    <option value="{{ $lim }}" @if((int)($limit ?? 25) === $lim) selected @endif>{{ $lim }}</option>
                @endforeach
            </select>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ legacy_asset('js/colorbox.js') }}"></script>
<script src="{{ legacy_asset('js/admin_booking.js') }}"></script>
<script>
jQuery(document).ready(function () {
    if (jQuery.fn.datepicker) {
        jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
        jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });
    }
    jQuery('.clorbox').colorbox({ width: '700px' });
});
</script>
@endpush
