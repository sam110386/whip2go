@if($lists->hasPages())
<div class="datatable-footer">
    <div class="dataTables_paginate paging_simple_numbers">
        {{ $lists->links() }}
    </div>
</div>
@endif

<style type="text/css">
    .fixed_header tbody { display:block; overflow:auto; height:400px; width:100%; }
    .fixed_header thead tr { display:block; }
    .fixed_header thead tr th, .fixed_header tbody tr td { width:100%; padding:5px; min-width:100px; max-width:100px; }
</style>
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">#</th>
                <th style="text-align:center;">Status</th>
                <th style="text-align:center;">Start Date</th>
                <th style="text-align:center;">End Date</th>
                <th style="text-align:center;">Driver</th>
                <th style="text-align:center;">Vehicle</th>
                <th style="text-align:center;"># of Rental Days</th>
                <th style="text-align:center;">Base Rent</th>
                <th style="text-align:center;">Extra Mileage</th>
                <th style="text-align:center;">Tax</th>
                <th style="text-align:center;">DIA</th>
                <th style="text-align:center;">Total Rent</th>
                <th style="text-align:center;">Insurance (Driver Paid)</th>
                <th style="text-align:center;">Total Billed</th>
                <th style="text-align:center;">Un-collected</th>
                <th style="text-align:center;">Total Collected</th>
                <th style="text-align:center;">Rev Share</th>
                <th style="text-align:center;">Gross Revenue</th>
                <th style="text-align:center;">Insurance (Dealer Paid)</th>
                <th style="text-align:center;">Driver Credits/Cash Paid</th>
                <th style="text-align:center;">Net Dealer Pay</th>
                <th style="text-align:center;">Transferred</th>
                <th style="text-align:center;">Pending</th>
                <th style="text-align:center;">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $list)
                <tr id="{{ $list->id }}">
                    <td style="text-align:center;">{{ $list->increment_id }}</td>
                    <td style="text-align:center;">
                        @if ($list->status == 0) New
                        @elseif ($list->status == 1) Active
                        @elseif ($list->status == 2) Canceled
                        @elseif ($list->status == 3) Completed
                        @endif
                    </td>
                    <td style="text-align:center;">
                        {{ $list->start_datetime != '0000-00-00 00:00:00' ? \Carbon\Carbon::parse($list->start_datetime)->timezone($list->timezone ?? 'UTC')->format('Y-m-d h:i A') : '--' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->end_datetime != '0000-00-00 00:00:00' ? \Carbon\Carbon::parse($list->end_datetime)->timezone($list->timezone ?? 'UTC')->format('Y-m-d h:i A') : '--' }}
                    </td>
                    <td style="text-align:center;">{{ $list->first_name }} {{ $list->last_name }}</td>
                    <td style="text-align:center;">{{ $list->vehicle_name }}</td>
                    <td style="text-align:center;">{{ $list->days }}</td>
                    <td style="text-align:center;">{{ $list->rent + $list->fixed_amt }}</td>
                    <td style="text-align:center;">{{ $list->extra_mile_fee }}</td>
                    <td style="text-align:center;">{{ $list->tax }}</td>
                    <td style="text-align:center;">{{ $list->dia_fee }}</td>
                    <td style="text-align:center;">{{ $list->total_rent + $list->fixed_amt }}</td>
                    <td style="text-align:center;">{{ $list->insurance_driver }}</td>
                    <td style="text-align:center;">{{ $list->total_billed }}</td>
                    <td style="text-align:center;">{{ $list->uncollected }}</td>
                    <td style="text-align:center;">{{ $list->total_collected }}</td>
                    <td style="text-align:center;">{{ $list->revpart }}</td>
                    <td style="text-align:center;">{{ $list->gross_revenue }}</td>
                    <td style="text-align:center;">{{ $list->insurance }}</td>
                    <td style="text-align:center;">{{ $list->driver_credit }}</td>
                    <td style="text-align:center;position:relative;">
                        @if($list->insurance > 0)
                            <span data-popup="popover" data-placement="top" title="Total Net Pay" data-content="Dealer Amt: {{ $list->total_net_pay + $list->insurance }}, Insurance: {{ $list->insurance }}">{{ $list->total_net_pay }} <i class="icon-info22 position-right"></i></span>
                        @else
                            {{ $list->total_net_pay }}
                        @endif
                    </td>
                    <td style="text-align:center;">{{ $list->transferred - $list->insurance }}</td>
                    <td style="text-align:center;">{{ $list->pending }}</td>
                    <td style="text-align:center;">
                        <a href="javascript:;" title="Refresh" onclick="customerReportRefresh({{ $list->id }})"><i class="glyphicon glyphicon-refresh"></i></a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($lists->hasPages())
<div class="datatable-footer">
    <div class="dataTables_paginate paging_simple_numbers">
        {{ $lists->links() }}
    </div>
</div>
@endif

<script type="text/javascript">
    $('[data-popup="popover"]').popover({
        template: '<div class="popover border-teal-400"><div class="arrow"></div><h3 class="popover-title bg-teal-400"></h3><div class="popover-content"></div></div>'
    });
</script>
