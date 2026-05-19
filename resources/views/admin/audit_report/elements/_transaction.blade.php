@include('partials.dispacher.paging_box', ['paginator' => $records, 'limit' => $limit ?? 50, 'position' => 'top'])

<div class="panel-flat">
    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                @include('partials.dispacher.sortable_header', ['columns' => [
                    ['title' => '#', 'field' => 'id', 'style' => 'width:5px;'],
                    ['title' => 'Date From', 'sortable' => false, 'style' => 'width:10px;'],
                    ['title' => 'Date To', 'sortable' => false, 'style' => 'width:5px;'],
                    ['title' => 'Status', 'sortable' => false, 'style' => 'width:5px;'],
                    ['title' => 'File', 'sortable' => false, 'style' => 'width:5px;'],
                    ['title' => 'Created', 'sortable' => false, 'style' => 'width:5px;'],
                    ['title' => 'Action', 'sortable' => false, 'style' => 'width:10px;'],
                ]])
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
                            <a href="{{ url('admin/transaction_audits/process/' . $record->id) }}" title="Process">
                                <i class="icon-spinner9"></i>
                            </a>
                        @endif
                        @if($record->status)
                            <a href="{{ url('admin/transaction_audits/download/' . base64_encode($record->id)) }}">
                                <i class="icon-file-download2"></i>
                            </a>
                        @endif
                        &nbsp;
                        <a href="{{ url('admin/transaction_audits/delete/' . base64_encode($record->id)) }}">
                            <i class="glyphicon glyphicon-trash"></i>
                        </a>
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

@include('partials.dispacher.paging_box', ['paginator' => $records, 'limit' => $limit ?? 50])
