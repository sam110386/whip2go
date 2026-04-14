@extends('layouts.main')

@section('title', $title_for_layout)

@section('content')
<div class="panel">
    <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;">
        <h3 style="width: 80%; float: left;">Message History</h3>
    </section>
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="post" action="/message_histories/index" id="frmSearchadmin" name="frmSearchadmin">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <div class="col-md-3">
                    Keyword :
                    <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}">
                </div>
                <div class="col-md-3">
                    Search By:&nbsp;
                    <select name="Search[searchin]" class="form-control">
                        <option value="">Select..</option>
                        @foreach($options as $k => $v)
                            <option value="{{ $k }}" @selected($fieldname === $k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    Status :
                    <select name="Search[type]" class="form-control">
                        <option value="">Select..</option>
                        <option value="1" @selected((string)$type === '1')>Outbound</option>
                        <option value="2" @selected((string)$type === '2')>Inbound</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label style="margin-bottom:0;">&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-lg">APPLY</button>
                </div>
                <div class="col-md-2">
                    <label style="margin-bottom:0;">Rows</label>
                    <select name="Record[limit]" class="form-control" onchange="this.form.submit()">
                        @foreach([25,50,100,200] as $opt)
                            <option value="{{ $opt }}" @selected((int)$limit === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

    <div style="width:100%; overflow: visible;">
        @if ($CsTwilioLogs->count() > 0)
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive vehiclelist">
                <tr>
                    <th><input type="checkbox" id="selectAllChildCheckboxs" value="1"></th>
                    <th valign="top" width="10%">Order#</th>
                    <th valign="top">Phone#</th>
                    <th valign="top">Message</th>
                    <th valign="top">Type</th>
                    <th valign="top">Created</th>
                </tr>
                @foreach ($CsTwilioLogs as $row)
                    <tr id="{{ $row->id }}">
                        <td><input type="checkbox" name="select[{{ $row->id }}]" value="{{ $row->id }}" id="select1" style="border:0"></td>
                        <td valign="top" width="10%">{{ $row->increment_id }}</td>
                        <td valign="top">{{ $row->renter_phone }}</td>
                        <td valign="top">{{ $row->msg }}</td>
                        <td valign="top">{{ (int)$row->type === 1 ? 'Outbound' : 'Inbound' }}</td>
                        <td valign="top">{{ $row->created }}</td>
                    </tr>
                @endforeach
                <tr><td height="6" colspan="7"></td></tr>
            </table>

            <div style="margin-top:12px;">
                Page {{ $CsTwilioLogs->currentPage() }} of {{ $CsTwilioLogs->lastPage() }} ({{ $CsTwilioLogs->total() }} total)
                @if (!$CsTwilioLogs->onFirstPage())
                    <a href="{{ $CsTwilioLogs->previousPageUrl() }}">Previous</a>
                @endif
                @if ($CsTwilioLogs->hasMorePages())
                    <a href="{{ $CsTwilioLogs->nextPageUrl() }}">Next</a>
                @endif
            </div>
        @else
            <table width="100%" cellpadding="2" cellspacing="1" border="0" class="borderTable">
                <tr>
                    <td colspan="14" align="center">No record found</td>
                </tr>
            </table>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ legacy_asset('js/selectAllCheckbox.js') }}"></script>
<script src="{{ legacy_asset('js/booking.js') }}"></script>
@endpush
