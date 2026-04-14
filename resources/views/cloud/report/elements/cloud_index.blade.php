@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
<div class="text-center">{{ $lists->withQueryString()->links() }}</div>
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
    <table width="100%" cellpadding="2" cellspacing="1"  border="0"  class="table fixed_header table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">#</th>
                <th style="text-align:center;">Status</th>
                <th style="text-align:center;">Start Date</th>
                <th style="text-align:center;">End Date</th>
                <th style="text-align:center;">Driver</th>
                <th style="text-align:center;">Vehicle</th>
                <th style="text-align:center;"># of Days</th>
                <th style="text-align:center;">Base Usage</th>
                <th style="text-align:center;">Extra Usage</th>
                <th style="text-align:center;">Tax</th>
                <th style="text-align:center;">DIA</th>
                <th style="text-align:center;">Total Usage</th>
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
            @foreach ($lists ?? [] as $list)
                <tr id="{{ $list['ReportCustomer']['id'] ?? '' }}">
                    <td style="text-align:center;">{{ $list['ReportCustomer']['increment_id'] ?? '' }}</td>
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
                    <td style="text-align:center;">{{ ($list['User']['first_name'] ?? '') . ' ' . ($list['User']['last_name'] ?? '') }}</td>
                    <td style="text-align:center;">{{ $list['Vehicle']['vehicle_name'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $list['ReportCustomer']['days'] ?? '' }}</td>
                    <td style="text-align:center;">{{ ($list['ReportCustomer']['rent'] ?? 0) + ($list['ReportCustomer']['fixed_amt'] ?? 0) }}</td>
                    <td style="text-align:center;">{{ $list['ReportCustomer']['extra_mile_fee'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $list['ReportCustomer']['tax'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $list['ReportCustomer']['dia_fee'] ?? '' }}</td>
                    <td style="text-align:center;">{{ ($list['ReportCustomer']['total_rent'] ?? 0) + ($list['ReportCustomer']['fixed_amt'] ?? 0) }}</td>
                    <td style="text-align:center;">{{ $list['ReportCustomer']['insurance_driver'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $list['ReportCustomer']['total_billed'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $list['ReportCustomer']['uncollected'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $list['ReportCustomer']['total_collected'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $list['ReportCustomer']['revpart'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $list['ReportCustomer']['gross_revenue'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $list['ReportCustomer']['insurance'] ?? '' }}</td>
                    <td style="text-align:center;">{{ $list['ReportCustomer']['driver_credit'] ?? '' }}</td>
                    <td style="text-align:center;position: relative;">
                        @if(($list['ReportCustomer']['insurance'] ?? 0) > 0)
                        <span data-popup="popover" data-placement="top" title="Total Net Pay" data-content="Dealer Amt: {{ ($list['ReportCustomer']['total_net_pay'] ?? 0) + ($list['ReportCustomer']['insurance'] ?? 0) }},  Insurance :{{ $list['ReportCustomer']['insurance'] ?? 0 }}">{{ $list['ReportCustomer']['total_net_pay'] ?? '' }} <i class="icon-info22 position-right"></i></span>
                        @else
                        {{ $list['ReportCustomer']['total_net_pay'] ?? '' }}
                        @endif
                    </td>
                    <td style="text-align:center;">{{ ($list['ReportCustomer']['transferred'] ?? 0) - ($list['ReportCustomer']['insurance'] ?? 0) }}</td>
                    <td style="text-align:center;">{{ $list['ReportCustomer']['pending'] ?? '' }}</td>
                    <td style="text-align:center;">
                        <a href="javascript:;" title="Refresh" onclick="CloudcustomerReportRefresh({{ $list['ReportCustomer']['id'] ?? 0 }})"><i class="glyphicon glyphicon-refresh"></i></a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
<div class="text-center">{{ $lists->withQueryString()->links() }}</div>
@endif
<script type="text/javascript">
    $('[data-popup="popover"]').popover({
		template: '<div class="popover border-teal-400"><div class="arrow"></div><h3 class="popover-title bg-teal-400"></h3><div class="popover-content"></div></div>'
	});
</script>
