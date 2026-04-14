@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
<div class="text-center">{{ $lists->withQueryString()->links() }}</div>
@endif
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">Vehicle</th>
                <th style="text-align:center;">Month</th>
                <th style="text-align:center;"># of Bookings</th>
                <th style="text-align:center;"># of Days</th>
                <th style="text-align:center;">Revenue($)</th>
                <th style="text-align:center;">Total Miles</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lists ?? [] as $list)
                <tr>
                    <td style="text-align:center;">
                        {{ $list['RevenueReport']['vehicle_name'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['RevenueReport']['month'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['RevenueReport']['bookings'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['RevenueReport']['days'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['RevenueReport']['revenue_for_month'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['RevenueReport']['odometer_for_month'] ?? '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
<div class="text-center">{{ $lists->withQueryString()->links() }}</div>
@endif
