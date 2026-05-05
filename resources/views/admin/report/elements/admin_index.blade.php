@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
<style type="text/css">
    .fixed_header tbody{
        display:block;
        overflow:auto;
        height:400px;
        width:100%;
     }
    .fixed_header thead tr{display:block;}
    .fixed_header thead tr th,.fixed_header tbody tr td{width: 100%;padding: 5px;min-width: 100px;max-width: 100px;}
</style>
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => '#', 'field' => 'increment_id', 'style' => 'text-align:center;'],
                    ['title' => 'Status', 'field' => 'status', 'style' => 'text-align:center;'],
                    ['title' => 'Start Date', 'field' => 'start_datetime', 'style' => 'text-align:center;'],
                    ['title' => 'End Date', 'field' => 'end_datetime', 'style' => 'text-align:center;'],
                    ['title' => 'Driver', 'field' => 'user_name', 'style' => 'text-align:center;'],
                    ['title' => 'Vehicle', 'field' => 'vehicle_name', 'style' => 'text-align:center;'],
                    ['title' => '# of Days', 'field' => 'days', 'style' => 'text-align:center;'],
                    ['title' => 'Base Usage', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Extra Usage', 'field' => 'extra_mile_fee', 'style' => 'text-align:center;'],
                    ['title' => 'Tax', 'field' => 'tax', 'style' => 'text-align:center;'],
                    ['title' => 'DIA', 'field' => 'dia_fee', 'style' => 'text-align:center;'],
                    ['title' => 'Total Usage', 'field' => 'total_rent', 'style' => 'text-align:center;'],
                    ['title' => 'Insurance (Driver Paid)', 'field' => 'insurance_driver', 'style' => 'text-align:center;'],
                    ['title' => 'Total Billed', 'field' => 'total_billed', 'style' => 'text-align:center;'],
                    ['title' => 'Un-collected', 'field' => 'uncollected', 'style' => 'text-align:center;'],
                    ['title' => 'Total Collected', 'field' => 'total_collected', 'style' => 'text-align:center;'],
                    ['title' => 'Rev Share', 'field' => 'revpart', 'style' => 'text-align:center;'],
                    ['title' => 'Gross Revenue', 'field' => 'gross_revenue', 'style' => 'text-align:center;'],
                    ['title' => 'Insurance (Dealer Paid)', 'field' => 'insurance', 'style' => 'text-align:center;'],
                    ['title' => 'Driver Credits/Cash Paid', 'field' => 'driver_credit', 'style' => 'text-align:center;'],
                    ['title' => 'Net Dealer Pay', 'field' => 'total_net_pay', 'style' => 'text-align:center;'],
                    ['title' => 'Transferable', 'field' => 'transferred', 'style' => 'text-align:center;'],
                    ['title' => 'Misc Fee', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Net Transferred', 'field' => 'net_transferred', 'style' => 'text-align:center;'],
                    ['title' => 'Pending', 'field' => 'pending', 'style' => 'text-align:center;'],
                    ['title' => 'Action', 'style' => 'text-align:center;', 'sortable' => false],
                ]])
            </tr>
        </thead>
        <tbody>
            @foreach ($lists ?? [] as $list)
                <tr id="{{ $list['ReportCustomer']['id'] ?? '' }}">
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
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
<script type="text/javascript">
    $('[data-popup="popover"]').popover({
		template: '<div class="popover border-teal-400"><div class="arrow"></div><h3 class="popover-title bg-teal-400"></h3><div class="popover-content"></div></div>'
	});
</script>
