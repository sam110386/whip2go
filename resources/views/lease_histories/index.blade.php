@extends('layouts.main')

@section('title', $title_for_layout ?? 'Lease history')

@section('content')
@php
    $statusOpt = ['complete' => 'Complete', 'cancel' => 'Cancel', 'incomplete' => 'InComplete'];
    $searchIn = [1 => 'Pickup Address', 2 => 'Vehicle#', 3 => 'Lease#', 4 => 'Telephone'];
@endphp
<div class="panel">
    <section class="right_content">
        <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;">
            <h3 style="width: 40%; float: left; padding: 10px;">{{ $title_for_layout ?? 'Reports' }}</h3>
        </section>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="get" action="/lease_histories/index" id="frmSearchadmin" class="form-horizontal">
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
                <div class="col-md-3">
                    <select name="Search[show_address]" class="form-control">
                        <option value="">Keyword applies to…</option>
                        <option value="1" @selected((string)$address_type === '1')>Pickup address</option>
                        <option value="2" @selected((string)$address_type === '2')>Vehicle #</option>
                        <option value="3" @selected((string)$address_type === '3')>Availability #</option>
                        <option value="4" @selected((string)$address_type === '4')>Telephone</option>
                    </select>
                </div>
            </fieldset>
            <fieldset class="content-group" style="padding:0.35em 0.625em 0.75em">
                <div class="col-md-3">
                    <select name="Search[status_type]" class="form-control">
                        <option value="">Select Type</option>
                        @foreach($statusOpt as $k => $label)
                            <option value="{{ $k }}" @selected(($status_type ?? '') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="Search[date_from]" class="form-control" value="{{ $date_from }}" placeholder="Date from (m/d/Y or Y-m-d)">
                </div>
                <div class="col-md-3">
                    <input type="text" name="Search[date_to]" class="form-control" value="{{ $date_to }}" placeholder="Date to">
                </div>
                <div class="col-md-3">
                    <input type="text" name="Search[payment_method]" class="form-control" value="{{ $payment_method }}" placeholder="Payment method">
                </div>
                <div class="col-md-3" style="margin-top:8px;">
                    <button type="submit" name="search" value="search" class="btn btn-primary">APPLY</button>
                </div>
            </fieldset>
            <div class="row" style="margin:8px 0;">
                <div class="col-md-2">
                    <label class="text-muted text-size-mini">Rows per page</label>
                    <select name="Record[limit]" class="form-control" onchange="this.form.submit()">
                        @foreach([25,50,100,200] as $opt)
                            <option value="{{ $opt }}" @selected((int)($limit ?? 50) === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>

        @if ($triploglist->total() > 0)
            <div class="table-responsive" style="clear:both;padding:10px;">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Lease ID</th>
                            <th>Vehicle</th>
                            <th>Start date</th>
                            <th>Start time</th>
                            <th>Status</th>
                            <th>Pickup</th>
                            <th style="width:160px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($triploglist as $row)
                            @php
                                $lid = (int)($row->lease_id ?? 0);
                                $enc = $lid ? base64_encode((string)$lid) : '';
                            @endphp
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->lease_id }}</td>
                                <td>{{ $row->vehicle_name ?? $row->vehicle_unique_id }}</td>
                                <td>{{ $row->start_date }}</td>
                                <td>{{ $row->start_time }}</td>
                                <td>
                                    @if((int)($row->status ?? 0) === 3) Complete
                                    @elseif((int)($row->status ?? 0) === 2) Canceled
                                    @else Incomplete
                                    @endif
                                </td>
                                <td>{{ \Illuminate\Support\Str::limit((string)($row->pickup_address ?? ''), 40) }}</td>
                                <td>
                                    @if($enc !== '')
                                        <a href="/lease_histories/lease_details/{{ $enc }}" class="btn btn-xs btn-default" target="_blank">Detail</a>
                                        <a href="/lease_histories/edit_lease_details/{{ $enc }}" class="btn btn-xs btn-default">Edit</a>
                                        @if((int)($row->status ?? 0) !== 2 && (int)($row->status ?? 0) !== 3)
                                            <a href="/lease_histories/cancel_lease/{{ $enc }}" class="btn btn-xs btn-warning" onclick="return confirm('Cancel this lease?');">Cancel</a>
                                            <a href="/lease_histories/auto_complete/{{ $enc }}" class="btn btn-xs btn-success" onclick="return confirm('Mark lease complete?');">Complete</a>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="padding:10px;">
                {{ $triploglist->links() }}
            </div>
        @else
            <p class="text-center" style="padding:24px;">No record found</p>
        @endif
    </section>
</div>
@endsection
