@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50, 'position' => 'top'])
@endif

<style type="text/css">
    .fixed_header tbody{
        display:block;
        overflow:auto;
     }
    .fixed_header thead tr{display:block;}
    .fixed_header thead tr th,.fixed_header tbody tr td{width: 100%;padding: 5px;min-width: 100px;max-width: 100px;}
</style>

<div class="panel-flat table-responsive" style="overflow-x: scroll;width: 100%;">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => '#', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Status', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Start Date', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'End Date', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Driver', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Vehicle', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => '# of Days', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Base Usage', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Extra Usage', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Tax', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'DIA', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Total Usage', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Insurance (Driver Paid)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Total Billed', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Un-collected', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Total Collected', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Rev Share', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Gross Revenue', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Insurance (Dealer Paid)', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Driver Credits/Cash Paid', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Net Dealer Pay', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Transferable', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Misc Fee', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Net Transferred', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Pending', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Action', 'style' => 'text-align:center;', 'sortable' => false],
                ]])
            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $list)
                <tr id="{{ $list->id }}">
                    <td style="text-align:center;">
                        {{ $list->increment_id ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        @if(($list->status ?? null) == 0) New @endif
                        @if(($list->status ?? null) == 1) Active @endif
                        @if(($list->status ?? null) == 2) Canceled @endif
                        @if(($list->status ?? null) == 3) Completed @endif
                    </td>
                    <td style="text-align:center;">
                        @php
                            $sd = $list->start_datetime ?? '';
                            $tz = !empty($list->timezone)? $list->timezone : config('app.timezone');
                            echo ($sd !== '' && $sd !== '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($sd)->timezone($tz)->format('Y-m-d h:i A') : '--';
                        @endphp
                    </td>
                    <td style="text-align:center;">
                        @php
                            $ed = $list->end_datetime ?? '';
                            $tz2 = !empty($list->timezone)? $list->timezone : config('app.timezone');
                            echo ($ed !== '' && $ed !== '0000-00-00 00:00:00') ? \Carbon\Carbon::parse($ed)->timezone($tz2)->format('Y-m-d h:i A') : '--';
                        @endphp
                    </td>
                    <td style="text-align:center;">
                        {{ ($list->user->first_name ?? '') . ' ' . ($list->user->last_name ?? '') }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->vehicle->vehicle_name ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->days ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', (($list->rent ?? 0) + ($list->fixed_amt ?? 0))) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->extra_mile_fee ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->tax ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->dia_fee ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', (($list->total_rent ?? 0) + ($list->fixed_amt ?? 0))) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->insurance_driver ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->total_billed ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->uncollected ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->total_collected ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->revpart ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->gross_revenue ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->insurance ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->driver_credit ?? 0) }}
                    </td>
                    <td style="text-align:center;position: relative;">
                        @if(($list->insurance ?? 0) > 0)
                        <span data-popup="popover" data-placement="top" title="Total Net Pay" data-content="Dealer Amt: {{ sprintf('%0.2f', (($list->total_net_pay ?? 0) + ($list->insurance ?? 0))) }},  Insurance :{{ sprintf('%0.2f', $list->insurance ?? 0) }}">{{ sprintf('%0.2f', $list->total_net_pay ?? 0) }} <i class="icon-info22 position-right"></i></span>
                        @else
                        {{ sprintf('%0.2f', $list->total_net_pay ?? 0) }}
                        @endif
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', (($list->transferred ?? 0) - ($list->insurance ?? 0))) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', (($list->transferred ?? 0) - ($list->insurance ?? 0) - ($list->net_transferred ?? 0))) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->net_transferred ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        {{ sprintf('%0.2f', $list->pending ?? 0) }}
                    </td>
                    <td style="text-align:center;">
                        <a href="javascript:void(0);" title="Refresh" onclick="AdmincustomerReportRefresh('{{ $list->id ?? 0 }}')">
                            <i class="glyphicon glyphicon-refresh"></i>
                        </a>
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
