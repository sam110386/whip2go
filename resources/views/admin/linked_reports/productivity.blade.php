@extends('admin.layouts.app')

@section('title', 'Fleet Productivity')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Portfolio</span> Productivity
                </h4>
            </div>
            <div class="heading-elements">
                <a href="/cloud/linked_reports/index" class="btn btn-default">Linked reports</a>
                <a href="/cloud/linked_reports/vehicle" class="btn btn-default">Fleet productivity (by vehicle)</a>
            </div>
        </div>

        <div class="breadcrumb-line">
            <ul class="breadcrumb">
                <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
                <li><a href="/cloud/linked_reports/index">Linked Reports</a></li>
                <li class="active">Productivity</li>
            </ul>
        </div>
    </div>

    <div class="content">
        @includeif('partials.flash')

        <div class="panel">
            <div class="panel-body">
                <form method="get" action="/cloud/linked_reports/productivity" class="form-horizontal" style="margin-bottom:12px;">
                    <div class="row">
                        <div class="col-md-3">
                            <select name="Search[user_id]" class="form-control">
                                <option value="">Select Dealer..</option>
                                @foreach($dealers ?? [] as $uid => $uname)
                                    <option value="{{ $uid }}" @selected((string)($user_id ?? '') === (string)$uid)>{{ $uname }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="Search[date_from]" class="form-control" value="{{ $date_from ?? '' }}" placeholder="Date from">
                        </div>
                        <div class="col-md-2">
                            <input type="text" name="Search[date_to]" class="form-control" value="{{ $date_to ?? '' }}" placeholder="Date to">
                        </div>
                        <div class="col-md-2">
                            <select name="Record[limit]" class="form-control">
                                @foreach ([25, 50, 100, 200] as $opt)
                                    <option value="{{ $opt }}" @selected((int)($limit ?? 50) === $opt)>{{ $opt }} / page</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" name="search" value="SEARCH" class="btn btn-primary">Search</button>
                            <button type="submit" name="search" value="EXPORT" class="btn btn-success">Export</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel-body" id="listing">
                @if(isset($reportlists) && $reportlists->count() > 0)
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Vehicle#</th>
                                <th>Vehicle Cost</th>
                                <th>Depreciation</th>
                                <th>Base Usage ($)</th>
                                <th>Extra Usage</th>
                                <th>Total Distance</th>
                                <th>Total Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportlists as $row)
                                @php $vid = (int) ($row->vehicle_id ?? 0); @endphp
                                <tr>
                                    <td>{{ $row->vehicle_name ?? '' }}</td>
                                    <td>{{ $row->msrp ?? '' }}</td>
                                    <td>{{ $vehicleDepreciation[$vid] ?? 0 }}</td>
                                    <td>{{ $row->totalrent ?? 0 }}</td>
                                    <td>{{ $row->extra_mileage_fee ?? 0 }}</td>
                                    <td>{{ $row->mileage ?? 0 }}</td>
                                    <td>{{ $row->totaldays ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $reportlists->appends(request()->except('page'))->links() }}
                @else
                    <p class="text-muted">No record found.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
