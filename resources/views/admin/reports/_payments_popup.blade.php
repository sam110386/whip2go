<div style="font-size:13px;">
    <h3>Payments for order #{{ $id }}</h3>
    <div class="table-responsive">
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Type</th>
                <th>Amount</th>
                <th>Txn ID</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $p)
                <tr>
                    <td>{{ $p->type }}</td>
                    <td>{{ $p->amount }}</td>
                    <td>{{ $p->transaction_id }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No payment rows.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

