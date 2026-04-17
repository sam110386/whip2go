<table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
    <tr>
        <th valign="top" align="center" width="5%">#</th>
        <th valign="top">Status</th>
        <th valign="top">Scheduled To</th>
        <th valign="top">Amount</th>
        <th valign="top">Last Processed</th>
        <th valign="top">Txn #</th>
        <th valign="top">Action</th>
    </tr>
    @foreach ($records as $record)
        <tr>
            <td valign="top">{{ $record->id }}</td>
            <td valign="top">
                @if ($record->status == 1)
                    <img src="/img/green2.jpg" alt="Status" title="Status">
                @else
                    <img src="/img/red3.jpg" alt="Status" title="Status">
                @endif
            </td>
            <td valign="top">
                {{ !empty($record->created) ? \Carbon\Carbon::parse($record->created)->timezone(session('timezone', 'UTC'))->format('Y-m-d h:i A') : '' }}
            </td>
            <td valign="top">{{ $record->amt }}</td>
            <td valign="top">
                {{ !empty($record->last_processed) ? \Carbon\Carbon::parse($record->last_processed)->timezone(session('timezone', 'UTC'))->format('Y-m-d h:i A') : '' }}
            </td>
            <td valign="top">{{ $record->txn_id }}</td>
            <td valign="top">
                @if ($record->status == 0)
                    <a href="javascript:;" onclick="paymentRetry('{{ base64_encode($record->id) }}')"><i class="icon-spinner9"></i> Retry</a>
                @endif
            </td>
        </tr>
    @endforeach
    <tr><td height="6" colspan="9"></td></tr>
</table>

@if($records->hasPages())
<div class="datatable-footer">
    <div class="dataTables_paginate paging_simple_numbers">
        {{ $records->links() }}
    </div>
</div>
@endif
