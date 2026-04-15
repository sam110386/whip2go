@extends('admin.layouts.app')

@section('title', 'Fleet Productivity')

@section('content')
    <h1>Fleet productivity</h1>
    <p><a href="/cloud/linked_reports/index">← Linked reports</a></p>
    <form method="get" action="/cloud/linked_reports/vehicle" class="form-inline" style="margin-bottom:12px;">
        <label class="mr-2">From
            <input type="text" name="Search[date_from]" value="{{ $dateFrom ?? '' }}" class="form-control" placeholder="m/d/Y or Y-m-d">
        </label>
        <label class="mr-2">To
            <input type="text" name="Search[date_to]" value="{{ $dateTo ?? '' }}" class="form-control" placeholder="m/d/Y or Y-m-d">
        </label>
        <label class="mr-2">Rows
            <select name="Record[limit]" class="form-control" onchange="this.form.submit()">
                @foreach ([25, 50, 100, 200] as $opt)
                    <option value="{{ $opt }}" @selected((int)($limit ?? 50) === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="btn btn-primary">Apply</button>
    </form>

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
@endsection
