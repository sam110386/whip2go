@extends('admin.layouts.app')

@section('title', $title ?? 'Fleet Productivity')

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
@endpush

@section('content')

    <div class="panel">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Fleet</span> Productivity
                </h4>
            </div>
        </div>

        @includeif('partials.flash')

        <div class="panel-body">
            <form id="frmSearchadmin" name="frmSearchadmin" method="GET" action="{{ url('admin/reports/productivity') }}">
                <div class="row">
                    <div class="col-md-4">
                        <input type="text" id="SearchUserId" name="Search[user_id]" class="" style="width:100%;"
                            value="{{ $user_id }}" placeholder="Select Dealer..">
                    </div>
                    <div class="col-md-2">
                        <input type="text" id="SearchDateFrom" name="Search[date_from]" class="form-control"
                            value="{{ $date_from }}" placeholder="Date Range From">
                    </div>
                    <div class="col-md-2">
                        <input type="text" id="SearchDateTo" name="Search[date_to]" class="form-control"
                            value="{{ $date_to }}" placeholder="Date Range To">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="search" value="SEARCH" class="btn btn-primary">
                            SEARCH
                        </button>
                        <button type="submit" name="search" value="EXPORT" class="btn btn-success">
                            <i class="icon-file-excel"></i> EXPORT
                        </button>
                    </div>
                </div>
            </form>

            <div style="width:100%; overflow: visible;">
                <div class="mt-10">
                    @if(isset($reportlists) && is_object($reportlists) && method_exists($reportlists, 'links'))
                        @include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit ?? 50, 'position' => 'top'])
                    @endif
                </div>
                <div class="table-responsive">
                    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                        <thead>
                            <tr>
                                <th class="text-center"> Vehicle# </th>
                                <th class="text-center"> Vehicle Cost </th>
                                <th class="text-center"> Depreciation </th>
                                <th class="text-center"> Base Usage ($) </th>
                                <th class="text-center"> Extra Usage </th>
                                <th class="text-center"> Total Usage Fee </th>
                                <th class="text-center"> Total Distance </th>
                                <th class="text-center"> Total Days </th>
                                <th class="text-center"> Idle Days </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportlists as $reportlist)
                                @php
                                    $totalRangeDays = (empty($date_from) || empty($date_to))
                                        ? $commonService->days_between_dates($reportlist->created_at, date('Y-m-d'))
                                        : $commonService->days_between_dates($date_from, $date_to);
                                    $expenses = (new \App\Services\Legacy\Portfolio())->getVehicleExpenses($reportlist->id, $date_from, $date_to);
                                    $totalUsageFee = (float) $reportlist->totalrent + (float) $reportlist->extra_mileage_fee;
                                    $idleDays = $totalRangeDays - (int) $reportlist->totaldays;
                                @endphp
                                <tr>
                                    <td class="text-center">
                                        {{ $reportlist->vehicle_name }}
                                    </td>
                                    <td class="text-center">
                                        {{ $reportlist->msrp }}
                                    </td>
                                    <td class="text-center">
                                        {{ number_format($expenses['depreciation'], 2) }}
                                    </td>
                                    <td class="text-center">
                                        {{ number_format((float) $reportlist->totalrent, 2) }}
                                    </td>
                                    <td class="text-center">
                                        {{ number_format((float) $reportlist->extra_mileage_fee, 2) }}
                                    </td>
                                    <td class="text-center">
                                        {{ number_format($totalUsageFee, 2) }}
                                    </td>
                                    <td class="text-center">
                                        {{ $reportlist->mileage ?: 0 }}
                                    </td>
                                    <td class="text-center">
                                        {{ $reportlist->totaldays ?: 0 }}
                                    </td>
                                    <td class="text-center">
                                        {{ $idleDays > 0 ? $idleDays : 0 }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">
                                        No records found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-10">
                    @if(isset($reportlists) && is_object($reportlists) && method_exists($reportlists, 'links'))
                        @include('partials.dispacher.paging_box', ['paginator' => $reportlists, 'limit' => $limit ?? 50])
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/select2.js') }}"></script>
    <script type="text/javascript">
        function format(item) {
            return item.tag;
        }

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