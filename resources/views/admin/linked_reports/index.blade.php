@extends('admin.layouts.app')

@section('title', 'Manage Linked Reports')

@php
    $dealers ??= [];
    $dealer_id ??= '';
    $keyword ??= '';
    $fieldname ??= '';
    $status_type ??= '';
    $date_from ??= '';
    $date_to ??= '';
    $renter_id ??= '';
    $limit ??= 50;
    $rollups ??= [];
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage</span> Linked Reports
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ url('cloud/linked_reports/vehicle') }}" class="btn btn-primary">Fleet productivity</a>
                <a href="{{ url('cloud/linked_reports/productivity') }}" class="btn btn-primary">Portfolio productivity</a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('cloud/linked_reports/index') }}">
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-2">
                            {{ 'Dealer :' }}
                            <select name="Search[dealer_id]" class="form-control">
                                <option value="">Dealers</option>
                                @foreach ($dealers as $did => $dname)
                                    <option value="{{ $did }}" @selected((string) $dealer_id === (string) $did)>
                                        {{ $dname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            {{ 'Keyword :' }}
                            <input type="text" name="Search[keyword]" class="form-control" value="{{ $keyword }}" maxlength="50">
                        </div>
                        <div class="col-md-2">
                            {{ 'Search By :' }}
                            <select name="Search[searchin]" class="form-control">
                                <option value="">Search By</option>
                                <option value="1" @selected((string) $fieldname === '1')>Pickup Address</option>
                                <option value="2" @selected((string) $fieldname === '2')>Vehicle#</option>
                                <option value="3" @selected((string) $fieldname === '3')>Order#</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            {{ 'Status :' }}
                            <select name="Search[status_type]" class="form-control">
                                <option value="">Status</option>
                                <option value="complete" @selected($status_type === 'complete')>Complete</option>
                                <option value="cancel" @selected($status_type === 'cancel')>Cancel</option>
                                <option value="incomplete" @selected($status_type === 'incomplete')>InComplete</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            {{ 'Date From :' }}
                            <input type="text" name="Search[date_from]" class="form-control" value="{{ $date_from }}" placeholder="m/d/Y or Y-m-d">
                        </div>
                        <div class="col-md-2">
                            {{ 'Date To :' }}
                            <input type="text" name="Search[date_to]" class="form-control" value="{{ $date_to }}" placeholder="m/d/Y or Y-m-d">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-2">
                            {{ 'Customer ID :' }}
                            <input type="text" name="Search[renter_id]" class="form-control" value="{{ $renter_id }}" title="Renter user id">
                        </div>
                        <div class="col-md-2">
                            {{ 'Rows :' }}
                            <select name="Record[limit]" class="form-control">
                                @foreach ([25, 50, 100, 200] as $opt)
                                    <option value="{{ $opt }}" @selected((int) $limit === $opt)>{{ $opt }} / page</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="search" value="SEARCH" class="btn btn-primary">
                                {{ 'Search' }}
                            </button>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="search" value="EXPORT" class="btn btn-warning">
                                {{ 'Export CSV' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                @include('admin.linked_reports._listing', [
                    'reportlists' => $reportlists,
                    'rollups' => $rollups,
                ])
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
    <script src="{{ legacy_asset('js/cloud_booking.js') }}"></script>
    <script src="{{ asset('js/admin_booking.js') }}"></script>
@endpush
