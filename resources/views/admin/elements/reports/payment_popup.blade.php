<div>
    <h4>Payments</h4>
    <ul>
    @forelse(($payments ?? []) as $payment)
        <li>{{ is_array($payment) ? json_encode($payment) : ($payment->id ?? json_encode($payment)) }}</li>
    @empty
        <li>No payment records.</li>
    @endforelse
    </ul>
</div>
