@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
<div class="text-center">{{ $lists->withQueryString()->links() }}</div>
@endif
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1"  border="0"  class="table  table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">Vehicle</th>
                <th style="text-align:center;">VIN</th>
                <th style="text-align:center;">Last Rcorded Mile</th>
                <th style="text-align:center;">Last Checked</th>
                <th style="text-align:center;">Booking</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lists ?? [] as $list)
                <tr id="{{ $list['ReportCustomer']['vehicle_id'] ?? '' }}">
                    <td style="text-align:center;">
                        {{ $list['Vehicle']['vehicle_name'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['Vehicle']['vin_no'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['Vehicle']['last_mile'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['Vehicle']['modified'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['ReportCustomer']['increment_id'] ?? '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
<div class="text-center">{{ $lists->withQueryString()->links() }}</div>
@endif
