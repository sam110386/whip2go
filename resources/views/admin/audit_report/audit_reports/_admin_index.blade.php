@if($records->hasPages())
    <div class="text-center">{{ $records->appends(request()->query())->links() }}</div>
@endif
<!-- Simple list -->
<div class="panel-flat">
    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="width:5px;">#</th>
                <th style="width:10px;">Date From</th>
                <th style="width:5px;">Date To</th>
                <th style="width:5px;">Status</th>
                <th style="width:5px;">File</th>
                <th style="width:5px;">Created</th>
                <th style="width:10px;">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $record)
                <tr>
                    <td>{{ $record->id }}</td>
                    <td>{{ $record->start_date }}</td>
                    <td>{{ $record->end_date }}</td>
                    <td>{{ $record->status == 0 ? 'Incomplete' : 'Completed' }}</td>
                    <td>{{ $record->file_name }}</td>
                    <td>{{ $record->created }}</td>
                    <td>
                        &nbsp;
                        @if($record->status == 0)
                            <a href="{{ url('admin/audit_report/audit_reports/process/' . $record->id) }}" title="Process"><i class="icon-spinner9"></i></a>
                        @endif
                        @if($record->status)
                            <a href="{{ url('admin/audit_report/audit_reports/download/' . base64_encode($record->id)) }}"><i class="icon-file-download2"></i></a>
                        @endif
                        &nbsp;
                        <a href="{{ url('admin/audit_report/audit_reports/delete/' . base64_encode($record->id)) }}"><i class="glyphicon glyphicon-trash"></i></a>
                    </td>
                </tr>
            @endforeach
            <tr>
                <td height="6" colspan="16"></td>
            </tr>
        </tbody>
    </table>
</div>
<!-- /simple list -->
@if($records->hasPages())
    <div class="text-center">{{ $records->appends(request()->query())->links() }}</div>
@endif
