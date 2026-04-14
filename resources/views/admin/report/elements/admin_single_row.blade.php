<td style="text-align:center;">
    {{ $list['ReportCustomer']['increment_id'] ?? '' }}
</td>

<td style="text-align:center;">
    @if(($list['ReportCustomer']['status'] ?? null) == 0) New @endif
    @if(($list['ReportCustomer']['status'] ?? null) == 1) Active @endif
    @if(($list['ReportCustomer']['status'] ?? null) == 2) Canceled @endif
    @if(($list['ReportCustomer']['status'] ?? null) == 3) Completed @endif
</td>
<td style="text-align:center;">
    @php
        $sd = $list['ReportCustomer']['start_datetime'] ?? '';
        $tz = $list['ReportCustomer']['timezone'] ?? config('app.timezone');
        echo ($sd !== '' && $sd !== '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($sd)->timezone($tz)->format('Y-m-d h:i A') : '--';
    @endphp
</td>
<td style="text-align:center;">
    @php
        $ed = $list['ReportCustomer']['end_datetime'] ?? '';
        $tz2 = $list['ReportCustomer']['timezone'] ?? config('app.timezone');
        echo ($ed !== '' && $ed !== '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($ed)->timezone($tz2)->format('Y-m-d h:i A') : '--';
    @endphp
</td>

<td style="text-align:center;">
    {{ ($list['User']['first_name'] ?? '') . ' ' . ($list['User']['last_name'] ?? '') }}
</td>
<td style="text-align:center;">
    {{ $list['Vehicle']['vehicle_name'] ?? '' }}
</td>
<td style="text-align:center;">
    {{ $list['ReportCustomer']['days'] ?? '' }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', (($list['ReportCustomer']['rent'] ?? 0) + ($list['ReportCustomer']['fixed_amt'] ?? 0))) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['extra_mile_fee'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['tax'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['dia_fee'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', (($list['ReportCustomer']['total_rent'] ?? 0) + ($list['ReportCustomer']['fixed_amt'] ?? 0))) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['insurance_driver'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['total_billed'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['uncollected'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['total_collected'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['revpart'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['gross_revenue'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['insurance'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['driver_credit'] ?? 0) }}
</td>
<td style="text-align:center;position: relative;">
    @if(($list['ReportCustomer']['insurance'] ?? 0) > 0)
        <span data-popup="popover" data-placement="top" title="Total Net Pay" data-content="Dealer Amt: {{ sprintf('%0.2f', (($list['ReportCustomer']['total_net_pay'] ?? 0) + ($list['ReportCustomer']['insurance'] ?? 0))) }},  Insurance :{{ sprintf('%0.2f', $list['ReportCustomer']['insurance'] ?? 0) }}">{{ sprintf('%0.2f', $list['ReportCustomer']['total_net_pay'] ?? 0) }} <i class="icon-info22 position-right"></i></span>
    @else
        {{ sprintf('%0.2f', $list['ReportCustomer']['total_net_pay'] ?? 0) }}
    @endif
</td>

<td style="text-align:center;">
    {{ sprintf('%0.2f', (($list['ReportCustomer']['transferred'] ?? 0) - ($list['ReportCustomer']['insurance'] ?? 0))) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', (($list['ReportCustomer']['transferred'] ?? 0) - ($list['ReportCustomer']['insurance'] ?? 0) - ($list['ReportCustomer']['net_transferred'] ?? 0))) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['net_transferred'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ sprintf('%0.2f', $list['ReportCustomer']['pending'] ?? 0) }}
</td>
<td style="text-align:center;">
    <a href="javascript:;" title="Refresh" onclick="AdmincustomerReportRefresh({{ $list['ReportCustomer']['id'] ?? 0 }})"><i class="glyphicon glyphicon-refresh"></i></a>
</td>
