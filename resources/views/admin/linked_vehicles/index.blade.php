@extends('admin.layouts.app')

@section('title', 'Manage Vehicles (Linked)')

@php
    $searchin ??= '';
    $keyword ??= '';
    $show ??= null;
    $userId ??= '';
    $limit ??= 50;
    $showArr ??= [];
    $searchOptions ??= [];
    $listUrl ??= url('admin/linked_vehicles/index');
    $isCloud = strpos($listUrl, '/cloud/') !== false;
    $addUrl = $isCloud ? url('cloud/linked_vehicles/add') : url('admin/linked_vehicles/add');
    $linkedBasePath = $isCloud ? '/cloud/linked_vehicles' : '/admin/linked_vehicles';
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Manage</span> Vehicles
                </h4>
            </div>
            <div class="heading-elements">
                <a href="{{ $addUrl }}" class="btn btn-success">Add Vehicle</a>
                <a href="{{ url('admin/vehicles/index') }}" class="btn btn-primary">Super-admin vehicle list</a>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <p class="text-muted">Dealer-linked fleet.</p>

            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ $listUrl }}">
                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-2">
                            {{ 'Search Field :' }}
                            <select name="Search[searchin]" class="form-control">
                                <option value="" @selected($searchin === '')>All</option>
                                @foreach ($searchOptions as $k => $label)
                                    <option value="{{ $k }}" @selected($searchin === $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            {{ 'Keyword :' }}
                            <input type="text" name="Search[keyword]" class="form-control" value="{{ $keyword }}" maxlength="50">
                        </div>
                        <div class="col-md-2">
                            {{ 'Status :' }}
                            <select name="Search[show]" class="form-control">
                                <option value="">All</option>
                                @foreach ($showArr as $k => $label)
                                    <option value="{{ $k }}" @selected((string) $show === (string) $k)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            {{ 'Dealer User ID :' }}
                            <input type="number" name="Search[user_id]" class="form-control" value="{{ $userId }}">
                        </div>
                        <div class="col-md-2">
                            {{ 'Rows :' }}
                            <select name="Record[limit]" class="form-control">
                                @foreach ([25, 50, 100, 200] as $opt)
                                    <option value="{{ $opt }}" @selected((int) $limit === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" class="btn btn-primary">
                                {{ 'Search' }}
                            </button>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <button type="submit" name="export" value="Export" class="btn btn-warning">
                                {{ 'Export CSV' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="row">&nbsp;</div>

            <div id="listing">
                @include('admin.vehicles._index_table', [
                    'vehicleDetails' => $vehicleDetails,
                    'listContext' => 'linked',
                    'linkedBasePath' => $linkedBasePath,
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
    <script src="{{ asset('js/admin_booking.js') }}"></script>
@endpush
