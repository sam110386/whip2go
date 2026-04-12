@extends('layouts.main')

@section('title', $title_for_layout ?? 'Reports')

@section('content')
@php
    $statusOpt = ['complete' => 'Complete', 'cancel' => 'Cancel', 'incomplete' => 'InComplete'];
    $searchIn = [1 => 'Pickup Address', 2 => 'Vehicle#', 3 => 'Order#'];
@endphp
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Reports</span></h4>
        </div>
    </div>
</div>
<div class="panel">
    <div class="panel-body">
        <form method="post" action="/reports/index" class="form-horizontal" id="frmSearchadmin" name="frmSearchadmin">
            @csrf
            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-2">
                        <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Keyword">
                    </div>
                    <div class="col-md-2">
                        <select name="Search[searchin]" class="form-control">
                            <option value="">Select In</option>
                            @foreach($searchIn as $k => $label)
                                <option value="{{ $k }}" @selected((string)$fieldname === (string)$k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="Search[status_type]" class="form-control">
                            <option value="">Select Type</option>
                            @foreach($statusOpt as $k => $label)
                                <option value="{{ $k }}" @selected($status_type === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Search[date_from]" class="form-control" value="{{ $date_from }}" placeholder="Date Range From">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="Search[date_to]" class="form-control" value="{{ $date_to }}" placeholder="Date Range To">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="search" value="search" class="btn btn-primary">APPLY</button>
                        <button type="submit" name="search" value="EXPORT" class="btn btn-primary">EXPORT</button>
                    </div>
                </div>
            </div>
            <div class="row" style="margin-top:8px;">
                <div class="col-md-2">
                    <label class="text-muted text-size-mini">Rows per page</label>
                    <select name="Record[limit]" class="form-control" onchange="this.form.submit()">
                        @foreach([25,50,100,200] as $opt)
                            <option value="{{ $opt }}" @selected((int)$limit === $opt)>{{ $opt }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="panel">
    <div class="panel-body" id="listing">
        @include('reports._listing', ['reportlists' => $reportlists])
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ legacy_asset('js/booking.js') }}"></script>
@endpush
