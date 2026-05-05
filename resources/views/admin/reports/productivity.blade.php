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
            <div class="panel-body">
                <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('admin/reports/productivity') }}">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" id="SearchUserId" name="Search[user_id]" class="" style="width:100%;"
                                value="{{ $user_id }}" placeholder="Select Dealer..">
                        </div>
                        <div class="col-md-2">
                            <input type="text" id="SearchDateFrom" name="Search[date_from]" class="form-control" 
                                value="{{ $dateFrom }}" placeholder="Date Range From">
                        </div>
                        <div class="col-md-2">
                            <input type="text" id="SearchDateTo" name="Search[date_to]" class="form-control" 
                                value="{{ $dateTo }}" placeholder="Date Range To">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="search" value="SEARCH" class="btn btn-primary">SEARCH</button>
                            <button type="submit" name="search" value="EXPORT" class="btn btn-success">
                                <i class="icon-file-excel"></i> EXPORT
                            </button>
                        </div>
                    </div>
                </form>

                <div class="row">&nbsp;</div>

                <div id="listing">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
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
                                    <th>Idle Days</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reportlists as $r)
                                    @php
                                        $expenses = \App\Support\PortfolioSupport::getVehicleExpenses($r->id, $dateFrom, $dateTo);
                                        if (empty($dateFrom) || empty($dateTo)) {
                                            $totalRangeDays = \App\Support\PortfolioSupport::daysBetweenDates($r->created_at, date('Y-m-d'));
                                        } else {
                                            $totalRangeDays = \App\Support\PortfolioSupport::daysBetweenDates($dateFrom, $dateTo);
                                        }
                                        $totalUsageFee = (float)$r->totalrent + (float)$r->extra_mileage_fee;
                                        $idleDays = $totalRangeDays - (int)$r->totaldays;
                                    @endphp
                                    <tr>
                                        <td>{{ $r->vehicle_name }}</td>
                                        <td>{{ $r->msrp }}</td>
                                        <td>{{ number_format($expenses['depreciation'], 2) }}</td>
                                        <td>{{ number_format((float)$r->totalrent, 2) }}</td>
                                        <td>{{ number_format((float)$r->extra_mileage_fee, 2) }}</td>
                                        <td>{{ number_format($totalUsageFee, 2) }}</td>
                                        <td>{{ $r->mileage ?: 0 }}</td>
                                        <td>{{ $r->totaldays ?: 0 }}</td>
                                        <td>{{ $idleDays > 0 ? $idleDays : 0 }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center">No records found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-10">
                        {{ $reportlists->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@push('scripts')
    <script src="{{ legacy_asset('js/select2.js') }}"></script>
    <script type="text/javascript">
        function format(item) { return item.tag; }

        jQuery(document).ready(function () {
            if (jQuery().datepicker) {
                jQuery('#SearchDateFrom').datepicker({ dateFormat: 'mm/dd/yy' });
                jQuery('#SearchDateTo').datepicker({ dateFormat: 'mm/dd/yy' });
            }

            jQuery("#SearchUserId").select2({
                data: { results: {}, text: 'tag' },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Dealer ",
                minimumInputLength: 1,
                ajax: {
                    url: "{{ url('admin/bookings/customerautocomplete') }}",
                    dataType: "json",
                    type: "GET",
                    data: function (params) {
                        return { term: params, "is_dealer": true };
                    },
                    processResults: function (data) {
                        return {
                            results: $.map(data, function (item) {
                                return { tag: item.tag, id: item.id };
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    var user_id = "{{ $user_id }}";
                    if (user_id !== "") {
                        jQuery.ajax({
                            url: "{{ url('admin/bookings/customerautocomplete') }}",
                            dataType: "json",
                            type: "GET",
                            data: { "id": user_id }
                        }).done(function (data) {
                            callback(data[0]);
                        });
                    }
                }
            });
        });
    </script>
@endpush
