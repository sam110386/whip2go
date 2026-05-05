@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => 'Vehicle', 'field' => 'vehicle_name', 'style' => 'text-align:center;'],
                    ['title' => 'Month', 'field' => 'month', 'style' => 'text-align:center;'],
                    ['title' => '# of Bookings', 'field' => 'bookings', 'style' => 'text-align:center;'],
                    ['title' => '# of Days', 'field' => 'days', 'style' => 'text-align:center;'],
                    ['title' => 'Revenue($)', 'field' => 'revenue_for_month', 'style' => 'text-align:center;'],
                    ['title' => 'Total Miles', 'field' => 'odometer_for_month', 'style' => 'text-align:center;'],
                ]])
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
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
