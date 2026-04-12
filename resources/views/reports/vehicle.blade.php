@extends('layouts.main')

@section('title', $title_for_layout ?? 'Fleet Productivity')

@section('content')
<div class="panel">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Fleet</span> Productivity</h4>
        </div>
    </div>
    <div class="panel-body">
        <form method="post" action="/reports/vehicle" class="form-horizontal" id="frmSearchadmin" name="frmSearchadmin">
            @csrf
            <fieldset class="content-group" style="padding:0.35em 0.625em 0.75em">
                <div class="col-md-2">
                    <input type="text" name="Search[date_from]" class="form-control" value="{{ $date_from }}" placeholder="Date Range From">
                </div>
                <div class="col-md-2">
                    <input type="text" name="Search[date_to]" class="form-control" value="{{ $date_to }}" placeholder="Date Range To">
                </div>
                <div class="col-md-4">
                    <button type="submit" name="search" value="SEARCH" class="btn btn-primary">SEARCH</button>
                    <button type="submit" name="search" value="EXPORT" class="btn btn-success">EXPORT</button>
                </div>
            </fieldset>
            <div class="col-md-2">
                <label class="text-muted text-size-mini">Rows</label>
                <select name="Record[limit]" class="form-control" onchange="this.form.submit()">
                    @foreach([25,50,100,200] as $opt)
                        <option value="{{ $opt }}" @selected((int)$limit === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        <div style="width:100%; overflow:visible;margin-top:12px;">
            @if($reportlists->count() > 0)
                <table class="table table-responsive" style="width:100%;">
                    <thead>
                        <tr>
                            <th>Vehicle#</th>
                            <th>Vehicle Cost</th>
                            <th>Depreciation</th>
                            <th>Base Usage ($)</th>
                            <th>Extra Usage</th>
                            <th>Total Usage Fee</th>
                            <th>Total Distance</th>
                            <th>Total Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportlists as $list)
                            @php
                                $base = (float)($list->totalrent ?? 0);
                                $extra = (float)($list->extra_mileage_fee ?? 0);
                            @endphp
                            <tr>
                                <td>{{ $list->vehicle_name }}</td>
                                <td>{{ $list->msrp ?? '' }}</td>
                                <td>—</td>
                                <td>{{ number_format($base, 2) }}</td>
                                <td>{{ $extra }}</td>
                                <td>{{ number_format($base + $extra, 2) }}</td>
                                <td>{{ $list->mileage ?? 0 }}</td>
                                <td>{{ $list->totaldays ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $reportlists->links() }}
            @else
                <table class="table"><tr><td class="text-center">No record found</td></tr></table>
            @endif
        </div>
    </div>
</div>
@endsection
