<td style="text-align:center;">
    {{ $list->increment_id ?? '' }}
</td>
<td style="text-align:center;">
    @if(($list->status ?? null) == 0)
        New
    @endif
    @if(($list->status ?? null) == 1)
        Active
    @endif
    @if(($list->status ?? null) == 2)
        Canceled
    @endif
    @if(($list->status ?? null) == 3)
        Completed
    @endif
</td>
<td style="text-align:center;">
    @php
        $sd = $list->start_datetime ?? '';
        $tz = !empty($list->timezone) ? $list->timezone : config('app.timezone');
        echo ($sd !== '' && $sd !== '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($sd)->timezone($tz)->format('Y-m-d h:i A') : '--';
    @endphp
</td>
<td style="text-align:center;">
    @php
        $ed = $list->end_datetime ?? '';
        $tz2 = !empty($list->timezone) ? $list->timezone : config('app.timezone');
        echo ($ed !== '' && $ed !== '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($ed)->timezone($tz2)->format('Y-m-d h:i A') : '--';
    @endphp
</td>

<td style="text-align:center;">
    {{ ($list->user->first_name ?? '') . ' ' . ($list->user->last_name ?? '') }}
</td>
<td style="text-align:center;">
    {{ $list->vehicle->vehicle_name ?? '' }}
</td>
<td style="text-align:center;">
    {{ $list->days ?? '' }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', (($list->rent ?? 0) + ($list->fixed_amt ?? 0))) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list->extra_mile_fee ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list->tax ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list->dia_fee ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', (($list->total_rent ?? 0) + ($list->fixed_amt ?? 0))) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list->insurance_driver ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list->total_billed ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list->uncollected ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list->total_collected ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list->revpart ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list->gross_revenue ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list->insurance ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['driver_credit'] ?? 0) }}
</td>
<td style="text-align:center;position: relative;">
    @if(($list->insurance ?? 0) > 0)
        <span data-popup="popover" data-placement="top" title="Total Net Pay"
            data-content="Dealer Amt: {{ sprintf('%0.2f', (($list->total_net_pay ?? 0) + ($list->insurance ?? 0))) }},  Insurance :{{ sprintf('%0.2f', $list->insurance ?? 0) }}">{{ sprintf('%0.2f', $list->total_net_pay ?? 0) }}
            <i class="icon-info22 position-right"></i></span>
    @else
        {{ sprintf('%0.2f', $list->total_net_pay ?? 0) }}
    @endif
</td>

<td style="text-align:center;">
    {{ sprintf('%0.2f', (($list->transferred ?? 0) - ($list->insurance ?? 0))) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', (($list->transferred ?? 0) - ($list->insurance ?? 0) - ($list->net_transferred ?? 0))) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list->net_transferred ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list->pending ?? 0) }}
</td>
<td style="text-align:center;">
    <a href="javascript:void(0)" title="Refresh" onclick="AdmincustomerReportRefresh({{ $list->id ?? 0 }})">
        <i class="glyphicon glyphicon-refresh"></i>
    </a>
</td>