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
    @if ($list->insurance > 0)
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
