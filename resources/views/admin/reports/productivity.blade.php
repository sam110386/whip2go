@extends('admin.layouts.app')

@section('title', 'Reports productivity')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Productivity</span> Report
                </h4>
            </div>
        </div>

        <div class="breadcrumb-line">
            <ul class="breadcrumb">
                <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
                <li><a href="/admin/reports/index">Reports</a></li>
                <li class="active">Productivity</li>
            </ul>
        </div>
    </div>

    <div class="content">
        @includeif('partials.flash')

        <div class="panel">
            <div class="panel-body" id="listing">
                <p>Range: {{ $dateFrom ?: 'all' }} to {{ $dateTo ?: 'all' }}</p>
                <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Dealer user id</th>
                            <th>Total orders</th>
                            <th>Gross</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $r)
                            <tr>
                                <td>{{ $r->user_id }}</td>
                                <td>{{ $r->total_orders }}</td>
                                <td>{{ number_format((float)$r->gross, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No rows</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
@endsection
