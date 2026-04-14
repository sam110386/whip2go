@php
    $portfolioSvc = app(\App\Services\Legacy\Report\PortfolioService::class);
@endphp
@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
<div class="text-center">{{ $lists->withQueryString()->links() }}</div>
@endif
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1"  border="0"  class="table  table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">Vehicle</th>
                <th style="text-align:center;"># of Days</th>
                <th style="text-align:center;">Total Distance</th>
                <th style="text-align:center;">Distance/Day</th>
                <th style="text-align:center;">Total Revenue ($)</th>
                <th style="text-align:center;">Write Down Allocation</th>
                <th style="text-align:center;">Est. Depreciation ($)</th>
                <th style="text-align:center;">Starting Cost ($)</th>
                <th style="text-align:center;">Expenses ($)</th>
                <th style="text-align:center;">Ending Cost ($)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lists ?? [] as $list)
                @php
                    $depriciation = $portfolioSvc->getVehicleDepriciation($list);
                @endphp
                <tr id="{{ $list['ReportCustomer']['vehicle_id'] ?? '' }}">
                    <td style="text-align:center;">
                        {{ $list['Vehicle']['vehicle_name'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['0']['days'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['0']['miles'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ (($list['0']['miles'] ?? 0) > 0 && ($list['0']['days'] ?? 0) > 0) ? sprintf('%0.2f', ($list['0']['miles'] / $list['0']['days'])) : '-' }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list['0']['total_collected'] ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list['0']['write_down_allocation'] ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ $depriciation }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list['Vehicle']['vehicleCostInclRecon'] ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ ($list['0']['expenses'] ?? 0) - 0 }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', (($list['Vehicle']['msrp'] ?? 0) + (!empty($list['0']['expenses']) ? $list['0']['expenses'] : 0) + $depriciation - (!empty($list['0']['write_down_allocation']) ? $list['0']['write_down_allocation'] : 0))) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
<div class="text-center">{{ $lists->withQueryString()->links() }}</div>
@endif
