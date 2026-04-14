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
                <th style="text-align:center;">Dealer</th>
                <th style="text-align:center;"># of Active Vehicles</th>
                <th style="text-align:center;"># of Rental Days</th>
                <th style="text-align:center;">Total Rent</th>
                <th style="text-align:center;">Uncollected</th>
                <th style="text-align:center;">Collected</th>
                <th style="text-align:center;">DIA Fee</th>
                <th style="text-align:center;">Insurance (Dealer Paid)</th>
                <th style="text-align:center;">Driver Credits/ Cash Paid</th>
                <th style="text-align:center;">Total Net Pay</th>
                <th style="text-align:center;">Transferred</th>
                <th style="text-align:center;">Pending</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lists ?? [] as $list)
                <tr id="{{ $list['ReportCustomer']['user_id'] ?? '' }}">
                    <td style="text-align:center;">
                        {{ $list['ReportCustomer']['user_id'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ ($list['User']['first_name'] ?? '') . ' ' . ($list['User']['last_name'] ?? '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['0']['activevehicles'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['0']['days'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['0']['total_rent'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['0']['uncollected'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['0']['total_collected'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['0']['revpart'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['0']['insurance'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ 0 }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['0']['total_net_pay'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['0']['transferred'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['0']['pending'] ?? '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
<div class="text-center">{{ $lists->withQueryString()->links() }}</div>
@endif
