<div style="font-size:13px;">
    <h3 style="margin:0 0 8px;">Reservation log #{{ $id }}</h3>
    <div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Message</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $l)
                <tr>
                    <td>{{ $l->id }}</td>
                    <td>{{ $l->message ?? $l->note ?? '-' }}</td>
                    <td>{{ $l->created ?? $l->created_at ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No logs found.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

