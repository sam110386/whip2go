<div class="panel panel-flat">
    <table class="table table-responsive table-hover">
        <thead>
            <tr class="bg-slate-700">
                <th>By</th>
                <th>Date</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse($notelists as $notelist)
                <tr>
                    <td class="text-semibold">{{ $notelist->admin_first_name }} {{ $notelist->admin_last_name }}</td>
                    <td><span class="text-muted">{{ \Carbon\Carbon::parse($notelist->created)->format('Y-m-d h:i A') }}</span></td>
                    <td>{{ $notelist->note }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">No notes found for this user.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="panel-body text-center">
    {{ $notelists->links() }}
</div>
