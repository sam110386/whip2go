@extends('admin.layouts.app')

@section('title', 'Fleet Productivity')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Fleet</span> Productivity
                </h4>
            </div>
            <div class="heading-elements">
                <a href="/cloud/linked_reports/index" class="btn btn-default">Linked reports</a>
            </div>
        </div>

        <div class="breadcrumb-line">
            <ul class="breadcrumb">
                <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
                <li><a href="/cloud/linked_reports/index">Linked Reports</a></li>
                <li class="active">Fleet Productivity</li>
            </ul>
        </div>
    </div>

    <div class="content">
        @includeif('partials.flash')

        <div class="panel">
            <div class="panel-body">
                <form method="get" action="/cloud/linked_reports/vehicle" class="form-inline" style="margin-bottom:12px;">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="mr-2">From
                                <input type="text" name="Search[date_from]" value="{{ $dateFrom ?? '' }}" class="form-control" placeholder="m/d/Y or Y-m-d">
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label class="mr-2">To
                                <input type="text" name="Search[date_to]" value="{{ $dateTo ?? '' }}" class="form-control" placeholder="m/d/Y or Y-m-d">
                            </label>
                        </div>
                        <div class="col-md-3">
                            <label class="mr-2">Rows
                                <select name="Record[limit]" class="form-control" onchange="this.form.submit()">
                                    @foreach ([25, 50, 100, 200] as $opt)
                                        <option value="{{ $opt }}" @selected((int)($limit ?? 50) === $opt)>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Apply</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="panel">
            <div class="panel-body" id="listing">
                @if($reportlists->count() > 0)
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Vehicle#</th>
                                <th>Total Rent ($)</th>
                                <th>Total Mileage</th>
                                <th>Total Days</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reportlists as $row)
                                <tr>
                                    <td>{{ $row->vehicle_name }}</td>
                                    <td>{{ $row->totalrent ?? 0 }}</td>
                                    <td>{{ $row->mileage ?? 0 }}</td>
                                    <td>{{ $row->totaldays ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $reportlists->links() }}
                @else
                    <p class="text-muted">No record found.</p>
                @endif
            </div>
        </div>
    </div>
@endsection
