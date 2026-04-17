<div class="panel panel-flat">
    <table width="100%" cellpadding="2" cellspacing="1" border="0" class="table table-responsive">
        <thead>
            <tr>
                <th align="left">By</th>
                <th align="left">Date</th>
                <th align="left">Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach($notelists as $notelist)
            <tr>
                <td align="left">{{ $notelist->admin_first_name }} {{ $notelist->admin_last_name }}</td>
                <td align="left">{{ \Carbon\Carbon::parse($notelist->created)->format('Y-m-d h:i A') }}</td>
                <td align="left">{{ $notelist->note }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
{{ $notelists->links() }}
