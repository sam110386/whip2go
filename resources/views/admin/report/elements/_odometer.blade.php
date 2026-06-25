@if(isset($lists) && is_object($lists) && method_exists($lists, 'links'))
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => 'Vehicle', 'field' => 'vehicle_name', 'style' => 'text-align:center;'],
                    ['title' => 'VIN', 'field' => 'vin_no', 'style' => 'text-align:center;'],
                    ['title' => 'Last Recorded Mile', 'field' => 'last_mile', 'style' => 'text-align:center;'],
                    ['title' => 'Last Checked', 'field' => 'modified', 'style' => 'text-align:center;'],
                    ['title' => 'Booking', 'field' => 'increment_id', 'style' => 'text-align:center;'],
                ]])
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
    @include('partials.dispacher.paging_box', ['paginator' => $lists, 'limit' => $limit ?? 50])
@endif
