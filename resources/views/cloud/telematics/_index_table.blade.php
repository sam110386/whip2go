@if($records->hasPages())
<div class="datatable-footer">
    <div class="dataTables_paginate paging_simple_numbers">
        {{ $records->links() }}
    </div>
</div>
@endif

<div class="panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="text-align:center;">#</th>
                <th style="text-align:center;">Status</th>
                <th style="text-align:center;">Started On</th>
                <th style="text-align:center;">Next On</th>
                <th style="text-align:center;"># of Units</th>
                <th style="text-align:center;">Subtotal Amt</th>
                <th style="text-align:center;">Amt</th>
                <th style="text-align:center;">Txn #</th>
                <th style="text-align:center;">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $list)
                <tr>
                    <td style="text-align:center;">{{ $list->id }}</td>
                    <td style="text-align:center;">
                        @if ($list->status == 0) New
                        @elseif ($list->status == 1) Active
                        @elseif ($list->status == 2) Canceled
                        @endif
                    </td>
                    <td style="text-align:center;">
                        {{ $list->created != '0000-00-00 00:00:00' ? \Carbon\Carbon::parse($list->created)->timezone(session('default_timezone', 'UTC'))->format('Y-m-d h:i A') : '--' }}
                    </td>
                    <td style="text-align:center;">
                        {{ $list->next_on != '0000-00-00 00:00:00' ? \Carbon\Carbon::parse($list->next_on)->timezone(session('default_timezone', 'UTC'))->format('Y-m-d h:i A') : '--' }}
                    </td>
                    <td style="text-align:center;">{{ $list->units }}</td>
                    <td style="text-align:center;">{{ $list->upfront_amt }}</td>
                    <td style="text-align:center;">{{ $list->amt }}</td>
                    <td style="text-align:center;">{{ $list->txn_id }}</td>
                    <td style="text-align:center;">
                        <a href="javascript:;" title="Refresh"><i class="glyphicon glyphicon-refresh"></i></a>
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
