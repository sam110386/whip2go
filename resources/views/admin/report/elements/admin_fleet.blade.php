@php
    $portfolioSvc = app(\App\Services\Legacy\Report\PortfolioService::class);
@endphp

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['field' => 'vehicle_name', 'title' => 'Vehicle', 'style' => 'text-align:center;'],
                    ['field' => 'days', 'title' => '# of Days', 'style' => 'text-align:center;'],
                    ['field' => 'miles', 'title' => 'Total Distance', 'style' => 'text-align:center;'],
                    ['field' => 'avg_miles', 'title' => 'Distance/Day', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'total_collected', 'title' => 'Total Revenue ($)', 'style' => 'text-align:center;'],
                    ['field' => 'write_down_allocation', 'title' => 'Write Down Allocation', 'style' => 'text-align:center;'],
                    ['field' => 'depreciation', 'title' => 'Est. Depreciation ($)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'starting_cost', 'title' => 'Starting Cost ($)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'expenses', 'title' => 'Expenses ($)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['field' => 'ending_cost', 'title' => 'Ending Cost ($)', 'style' => 'text-align:center;', 'sortable' => false],
                ]])
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

@include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
