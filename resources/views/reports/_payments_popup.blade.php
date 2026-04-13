<div style="font-size:13px;">
    <h4>Payments</h4>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Type</th>
                <th>Amount</th>
                <th>When</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $p)
                <tr>
                    <td>{{ $p->type ?? '' }}</td>
                    <td>{{ $p->amount ?? '' }}</td>
                    <td>{{ $p->charged_at ?? $p->created ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No payments found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
