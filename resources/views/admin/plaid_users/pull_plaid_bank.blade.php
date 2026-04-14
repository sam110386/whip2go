<div class="panel-body">
    <p><strong>pullPlaidBank</strong> (stub) — modal: {{ $modal }}, user: {{ $userid }}</p>
    <ul>
        @forelse($plaids as $p)
            <li>PlaidUser #{{ $p->id }} (paystub=0)</li>
        @empty
            <li>No records.</li>
        @endforelse
    </ul>
</div>
