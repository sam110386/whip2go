<table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
    <thead>
        <tr>
            <th style="width:5px;">Booking#</th>
            <th style="width:5px;">Case Id</th>
            <th style="width:5px;">Token</th>
            <th style="width:5px;">Status</th>
            <th style="width:5px;">Created</th>
            <th style="width:5px;">Action</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($lists as $list)
            <tr>
                <td>{{ $list->increment_id }}</td>
                <td>{{ $list->case_id }}</td>
                <td>{{ $list->token }}</td>
                <td>{{ $statusFlags[$list->status] ?? 'N/A' }}</td>
                <td>{{ $list->created }}</td>
                <td>
                    <a href="javascript:;" title="View Report" onclick="loadReportDetail('{{ $list->case_id }}')"><i class="icon-magazine icon-2x" title="View Report"></i></a>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
{{ $lists->links() }}
