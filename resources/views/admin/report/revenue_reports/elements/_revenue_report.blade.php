@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50, 'position'=>'top'])
@endif

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => 'Vehicle', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Month', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => '# of Bookings', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => '# of Days', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Revenue($)', 'sortable' => false, 'style' => 'text-align:center;'],
                    ['title' => 'Total Miles', 'sortable' => false, 'style' => 'text-align:center;'],
                ]])
            </tr>
        </thead>
        <tbody>
            @foreach ($lists as $list)
                <tr>
                    <td style="text-align:center;">
                        {{ $list->vehicle_name ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->month ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->bookings ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->days ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->revenue_for_month ?? '' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->odometer_for_month ?? '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
