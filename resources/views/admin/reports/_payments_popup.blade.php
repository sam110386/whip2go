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
                    <td>{{ $paymentTypeValue[$p->type] ?? $p->type }}</td>
                    <td>{{ number_format((float)$p->amount, 2) }}</td>
                    <td>{{ $p->transaction_id }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No payment rows.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

