@if($records->hasPages())
    <div class="datatable-footer">
        <div class="dataTables_paginate paging_simple_numbers">
            {{ $records->appends(['date_from' => $date_from, 'date_to' => $date_to, 'status_type' => $status_type, 'dealer_id' => $dealerid])->links() }}
        </div>
    </div>
@endif

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => '#', 'field' => 'id', 'style' => 'text-align:center;'],
                    ['title' => 'Status', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Started On', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Next On', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Dealer', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => '# of Units', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Subtotal Amt', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Amt', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Txn #', 'style' => 'text-align:center;', 'sortable' => false],
                    ['title' => 'Action', 'style' => 'text-align:center;', 'sortable' => false],
                ]])
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $list)
                <tr>
                    <td style="text-align:center;">{{ $list->id }}</td>
                    <td style="text-align:center;">
                        @if ($list->status == 0) Inactive
                        @elseif ($list->status == 1) Active
                        @elseif ($list->status == 2) Canceled
                        @endif
                        @if ($list->status == 1)
                            <img src="/img/green2.jpg" alt="Status" title="Status">
                        @else
                            <img src="/img/red3.jpg" alt="Status" title="Status">
                        @endif
                    </td>
                    <td style="text-align:center;">
                        {{ $list->created != '0000-00-00 00:00:00' ? \Carbon\Carbon::parse($list->created)->timezone(session('default_timezone', 'UTC'))->format('Y-m-d h:i A') : '--' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->next_on != '0000-00-00 00:00:00' ? \Carbon\Carbon::parse($list->next_on)->timezone(session('default_timezone', 'UTC'))->format('Y-m-d h:i A') : '--' }}
                    </td>
                    <td style="text-align:center;">{{ $list->first_name }} {{ $list->last_name }}</td>
                    <td style="text-align:center;">{{ $list->units }}</td>
                    <td style="text-align:center;">{{ $list->upfront_amt }}</td>
                    <td style="text-align:center;">{{ $list->amt }}</td>
                    <td style="text-align:center;">{{ $list->txn_id }}</td>
                    <td style="text-align:center;">
                        <a href="/admin/telematics/sub_devices/index/{{ base64_encode($list->id) }}"
                            title="Manage Attached Devices"><i class="icon-cabinet"></i></a>
                        <a href="javascript:;" title="Payments" onclick="openPayments('{{ base64_encode($list->id) }}')"><i
                                class="icon-coin-dollar"></i></a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($records->hasPages())
    <div class="datatable-footer">
        <div class="dataTables_paginate paging_simple_numbers">
            {{ $records->links() }}
        </div>
    </div>
@endif