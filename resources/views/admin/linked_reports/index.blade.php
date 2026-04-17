@extends('admin.layouts.app')

@section('title', 'Linked Reports')

@section('content')
    <h1>Linked reports</h1>
    <p><a href="/cloud/linked_reports/vehicle">Fleet productivity (by vehicle)</a> ·
        <a href="/cloud/linked_reports/productivity">Portfolio productivity</a></p>

    <form method="get" action="/cloud/linked_reports/index" class="form-horizontal" style="margin-bottom:12px;">
        <div class="row" style="margin-bottom:8px;">
            <div class="col-md-2">
                <select name="Search[dealer_id]" class="form-control">
                    <option value="">Dealers</option>
                    @foreach($dealers ?? [] as $did => $dname)
                        <option value="{{ $did }}" @selected((string)($dealer_id ?? '') === (string)$did)>{{ $dname }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="Search[keyword]" class="form-control" value="{{ $keyword ?? '' }}" placeholder="Keyword" maxlength="50">
            </div>
            <div class="col-md-2">
                <select name="Search[searchin]" class="form-control">
                    <option value="">Search By</option>
                    <option value="1" @selected(($fieldname ?? '') === '1')>Pickup Address</option>
                    <option value="2" @selected(($fieldname ?? '') === '2')>Vehicle#</option>
                    <option value="3" @selected(($fieldname ?? '') === '3')>Order#</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="Search[status_type]" class="form-control">
                    <option value="">Status</option>
                    <option value="complete" @selected(($status_type ?? '') === 'complete')>Complete</option>
                    <option value="cancel" @selected(($status_type ?? '') === 'cancel')>Cancel</option>
                    <option value="incomplete" @selected(($status_type ?? '') === 'incomplete')>InComplete</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="Search[date_from]" class="form-control" value="{{ $date_from ?? '' }}" placeholder="Date from (m/d/Y or Y-m-d)">
            </div>
            <div class="col-md-2">
                <input type="text" name="Search[date_to]" class="form-control" value="{{ $date_to ?? '' }}" placeholder="Date to">
            </div>
        </div>
        <div class="row" style="margin-bottom:8px;">
            <div class="col-md-2">
                <input type="text" name="Search[renter_id]" class="form-control" value="{{ $renter_id ?? '' }}" placeholder="Customer id" title="Renter user id">
            </div>
            <div class="col-md-2">
                <select name="Record[limit]" class="form-control">
                    @foreach ([25, 50, 100, 200] as $opt)
                        <option value="{{ $opt }}" @selected((int)($limit ?? 50) === $opt)>{{ $opt }} / page</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" name="search" value="SEARCH" class="btn btn-primary">Search</button>
                <button type="submit" name="search" value="EXPORT" class="btn btn-success">Export CSV</button>
            </div>
        </div>
    </form>

    @include('admin.linked_reports._listing', [
        'reportlists' => $reportlists,
        'rollups' => $rollups ?? [],
    ])
@endsection

@push('scripts')
<script src="{{ legacy_asset('js/cloud_booking.js') }}"></script>
@endpush
