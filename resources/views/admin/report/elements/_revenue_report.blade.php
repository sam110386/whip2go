@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table fixed_header table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['field' => 'vehicle_name', 'title' => 'Vehicle', 'style' => 'text-align:center;'],
                    ['field' => 'month', 'title' => 'Month', 'style' => 'text-align:center;'],
                    ['field' => 'bookings', 'title' => '# of Bookings', 'style' => 'text-align:center;'],
                    ['field' => 'days', 'title' => '# of Days', 'style' => 'text-align:center;'],
                    ['field' => 'revenue_for_month', 'title' => 'Revenue($)', 'style' => 'text-align:center;'],
                    ['field' => 'odometer_for_month', 'title' => 'Total Miles', 'style' => 'text-align:center;'],
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
