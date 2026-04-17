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
                    ['field' => 'increment_id', 'title' => '#', 'style' => 'text-align:center;'],
                    ['field' => 'status', 'title' => 'Status', 'style' => 'text-align:center;'],
                    ['field' => 'start_datetime', 'title' => 'Start Date', 'style' => 'text-align:center;'],
                    ['field' => 'end_datetime', 'title' => 'End Date', 'style' => 'text-align:center;'],
                    ['field' => 'user_name', 'title' => 'Driver', 'style' => 'text-align:center;'],
                    ['field' => 'vehicle_name', 'title' => 'Vehicle', 'style' => 'text-align:center;'],
                    ['field' => 'days', 'title' => '# of Days', 'style' => 'text-align:center;'],
                    ['field' => 'base_usage', 'title' => 'Base Usage', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'extra_mile_fee', 'title' => 'Extra Usage', 'style' => 'text-align:center;'],
                    ['field' => 'tax', 'title' => 'Tax', 'style' => 'text-align:center;'],
                    ['field' => 'dia_fee', 'title' => 'DIA', 'style' => 'text-align:center;'],
                    ['field' => 'total_rent', 'title' => 'Total Usage', 'style' => 'text-align:center;'],
                    ['field' => 'insurance_driver', 'title' => 'Insurance (Driver Paid)', 'style' => 'text-align:center;'],
                    ['field' => 'total_billed', 'title' => 'Total Billed', 'style' => 'text-align:center;'],
                    ['field' => 'uncollected', 'title' => 'Un-collected', 'style' => 'text-align:center;'],
                    ['field' => 'total_collected', 'title' => 'Total Collected', 'style' => 'text-align:center;'],
                    ['field' => 'revpart', 'title' => 'Rev Share', 'style' => 'text-align:center;'],
                    ['field' => 'gross_revenue', 'title' => 'Gross Revenue', 'style' => 'text-align:center;'],
                    ['field' => 'insurance', 'title' => 'Insurance (Dealer Paid)', 'style' => 'text-align:center;'],
                    ['field' => 'driver_credit', 'title' => 'Driver Credits/Cash Paid', 'style' => 'text-align:center;'],
                    ['field' => 'total_net_pay', 'title' => 'Net Dealer Pay', 'style' => 'text-align:center;'],
                    ['field' => 'transferred', 'title' => 'Transferable', 'style' => 'text-align:center;'],
                    ['field' => 'misc_fee', 'title' => 'Misc Fee', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'net_transferred', 'title' => 'Net Transferred', 'style' => 'text-align:center;'],
                    ['field' => 'pending', 'title' => 'Pending', 'style' => 'text-align:center;'],
                    ['field' => 'actions', 'title' => 'Action', 'style' => 'text-align:center;', 'sortable' => false],
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
