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
    {{ ($list['ReportCustomer']['rent'] ?? 0) + ($list['ReportCustomer']['fixed_amt'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ $list['ReportCustomer']['extra_mile_fee'] ?? '' }}
</td>
<td style="text-align:center;">
    {{ $list['ReportCustomer']['tax'] ?? '' }}
</td>
<td style="text-align:center;">
    {{ $list['ReportCustomer']['dia_fee'] ?? '' }}
</td>
<td style="text-align:center;">
    {{ ($list['ReportCustomer']['total_rent'] ?? 0) + ($list['ReportCustomer']['fixed_amt'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ $list['ReportCustomer']['insurance_driver'] ?? '' }}
</td>
<td style="text-align:center;">
    {{ $list['ReportCustomer']['total_billed'] ?? '' }}
</td>
<td style="text-align:center;">
    {{ $list['ReportCustomer']['uncollected'] ?? '' }}
</td>
<td style="text-align:center;">
    {{ $list['ReportCustomer']['total_collected'] ?? '' }}
</td>
<td style="text-align:center;">
    {{ $list['ReportCustomer']['revpart'] ?? '' }}
</td>

<td style="text-align:center;">
    {{ $list['ReportCustomer']['gross_revenue'] ?? '' }}
</td>
<td style="text-align:center;">
    {{ $list['ReportCustomer']['insurance'] ?? '' }}
</td>
<td style="text-align:center;">
    {{ $list['ReportCustomer']['driver_credit'] ?? '' }}
</td>
<td style="text-align:center;position: relative;">
    @if(($list['ReportCustomer']['insurance'] ?? 0) > 0)
        <span data-popup="popover" data-placement="top" title="Total Net Pay" data-content="Dealer Amt: {{ ($list['ReportCustomer']['total_net_pay'] ?? 0) + ($list['ReportCustomer']['insurance'] ?? 0) }},  Insurance :{{ $list['ReportCustomer']['insurance'] ?? 0 }}">{{ $list['ReportCustomer']['total_net_pay'] ?? '' }} <i class="icon-info22 position-right"></i></span>
    @else
        {{ $list['ReportCustomer']['total_net_pay'] ?? '' }}
    @endif
</td>
<td style="text-align:center;">
    {{ ($list['ReportCustomer']['transferred'] ?? 0) - ($list['ReportCustomer']['insurance'] ?? 0) }}
</td>
<td style="text-align:center;">
    {{ $list['ReportCustomer']['pending'] ?? '' }}
</td>

<td style="text-align:center;">
    <a href="javascript:;" title="Refresh" onclick="CloudcustomerReportRefresh({{ $list['ReportCustomer']['id'] ?? 0 }})"><i class="glyphicon glyphicon-refresh"></i></a>
</td>
