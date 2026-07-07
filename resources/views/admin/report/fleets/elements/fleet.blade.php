@include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50, 'position' => 'top'])

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;"> Vehicle</th>
                <th style="text-align:center;"> # of Days</th>
                <th style="text-align:center;"> Total Distance</th>
                <th style="text-align:center;"> Distance/Day</th>
                <th style="text-align:center;"> Total Revenue ($)</th>
                <th style="text-align:center;"> Write Down Allocation</th>
                <th style="text-align:center;"> Est. Depreciation ($)</th>
                <th style="text-align:center;"> Starting Cost ($)</th>
                <th style="text-align:center;"> Expenses ($)</th>
                <th style="text-align:center;"> Ending Cost ($)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $list)
                @php
                    $depriciation = \App\Helpers\Legacy\ReportHelper::getVehicleDepriciation($list);
                @endphp
                <tr id="{{ $list->vehicle_id}}">
                    <td style="text-align:center;">
                        {{ $list->vehicle_name ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->days ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->miles ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ (($list->miles ?? 0) > 0 && ($list->days ?? 0) > 0) ? sprintf('%0.2f', ($list->miles / $list->days)) : '-' }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->total_collected ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->write_down_allocation ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ $depriciation }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->vehicleCostInclRecon ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ ($list->expenses ?? 0) - 0 }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', (($list->msrp ?? 0) + (!empty($list->expenses) ? $list->expenses : 0) + $depriciation - (!empty($list->write_down_allocation) ? $list->write_down_allocation : 0))) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])