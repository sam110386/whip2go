@if($leads->hasPages())
<div class="datatable-footer">
    <div class="dataTables_paginate paging_simple_numbers">
        {{ $leads->appends(['keyword' => $keyword])->links() }}
    </div>
</div>
@endif

<div class="panel-flat">
    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th style="width:5px;">#</th>
                <th style="width:10px;">Status</th>
                <th style="width:5px;">Phone</th>
                <th style="width:5px;">Name</th>
                <th style="width:5px;">Created</th>
                <th style="width:5px;">Email</th>
                <th style="width:10px;">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($leads as $lead)
                <tr>
                    <td>{{ $lead->id }}</td>
                    <td>
                        @if ($lead->status == 1)
                            Notified
                        @elseif ($lead->status == 2)
                            Canceled
                        @elseif ($lead->status == 3)
                            Approved
                        @else
                            New
                        @endif
                    </td>
                    <td>{{ $lead->phone }}</td>
                    <td>{{ $lead->first_name }} {{ $lead->last_name }}</td>
                    <td>{{ \Carbon\Carbon::parse($lead->created)->format('m/d/Y h:i A') }}</td>
                    <td>{{ $lead->email }}</td>
                    <td>
                        @if ($lead->status != 3)
                            &nbsp;<a href="/admin/hitch/leads/add/{{ base64_encode($lead->id) }}"><i class="glyphicon glyphicon-edit"></i></a>
                            &nbsp;<a href="/admin/hitch/leads/delete/{{ base64_encode($lead->id) }}"><i class="glyphicon glyphicon-trash"></i></a>
                            &nbsp;<a href="javascript:;" onclick="refreshLead('{{ $lead->id }}')"><i class="icon-spinner9"></i></a>
                        @endif
                    </td>
                </tr>
            @endforeach
            <tr><td height="6" colspan="16"></td></tr>
        </tbody>
    </table>
</div>

@if($leads->hasPages())
<div class="datatable-footer">
    <div class="dataTables_paginate paging_simple_numbers">
        {{ $leads->appends(['keyword' => $keyword])->links() }}
    </div>
</div>
@endif
