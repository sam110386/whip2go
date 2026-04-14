<div class="panel-body">
    <p><strong>pullPlaidPaystub</strong> (stub) — modal: {{ $modal }}, user: {{ $userid }}</p>
    <ul>
        @forelse($plaids as $p)
            <li>PlaidUser #{{ $p->id }} (paystub=1)</li>
        @empty
            <li>No records.</li>
        @endforelse
    </ul>
</div>
