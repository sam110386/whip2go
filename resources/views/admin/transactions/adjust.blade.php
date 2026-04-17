@extends('admin.layouts.app')

@section('title', $title ?? 'Adjust order')

@section('content')
    <p><a href="/admin/transactions/updatetransaction/{{ base64_encode((string)$order->id) }}">← Back to order transactions</a></p>
    <h1>{{ $title ?? 'Adjust order' }}</h1>
    <p>Order #{{ $order->increment_id ?? $order->id }}</p>
    <form id="adjust-form">
        @csrf
        <input type="hidden" name="CsOrder[id]" value="{{ $order->id }}">
        <label>New value for <code>{{ $field }}</code></label><br>
        <input type="number" step="0.01" name="CsOrder[value]" value="{{ (float)($order->{$field} ?? 0) }}">
        <button type="submit">Save</button>
    </form>
    <script>
        document.getElementById('adjust-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var form = new FormData(e.target);
            var path = location.pathname.toLowerCase();
            var endpoint = '/admin/transactions/adjustTotal';
            if (path.indexOf('updateinsurance') !== -1) endpoint = '/admin/transactions/adjustInsurance';
            else if (path.indexOf('updateinitialfee') !== -1) endpoint = '/admin/transactions/adjustinitialfee';
            else if (path.indexOf('updateemf') !== -1) endpoint = '/admin/transactions/adjustEmf';
            else if (path.indexOf('updatediainsu') !== -1) endpoint = '/admin/transactions/adjustDiainsu';
            else if (path.indexOf('latefee') !== -1) endpoint = '/admin/transactions/adjustLatefee';
            else if (path.indexOf('updatetoll') !== -1) endpoint = '/admin/transactions/adjusttollfee';
            fetch(endpoint, {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest'},
                body: form
            }).then(r => r.json()).then(function (res) {
                alert(res.message || 'Processed');
            });
        });
    </script>
@endsection

