@extends('layouts.admin')

@section('title', 'Fleet Productivity')

@section('content')
    <h1>Portfolio productivity</h1>
    <p><a href="/cloud/linked_reports/index">← Linked reports</a> · <a href="/cloud/linked_reports/vehicle">Fleet productivity (by vehicle)</a></p>

    <form method="get" action="/cloud/linked_reports/productivity" class="form-horizontal" style="margin-bottom:12px;">
        <div class="row" style="margin-bottom:8px;">
            <div class="col-md-4">
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
            <div class="col-md-2">
                <button type="submit" name="search" value="SEARCH" class="btn btn-primary">Search</button>
                <button type="submit" name="search" value="EXPORT" class="btn btn-success">Export</button>
            </div>
        </div>
    </form>

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
@endsection
