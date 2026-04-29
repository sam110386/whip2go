@extends('admin.layouts.app')

@section('title', $title ?? 'Adjust order')

@section('content')
    @php
        $backUrl = '/admin/transactions/updatetransaction/' . base64_encode((string) $order->id);
    @endphp

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ $title ?? 'Adjust order' }}</span>
                </h4>
            </div>
            <div class="heading-elements">
                <div class="heading-btn-group">
                    <a href="{{ $backUrl }}" class="btn btn-default">
                        <i class="icon-arrow-left16 position-left"></i> Back to order transactions
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Order #{{ $order->increment_id ?? $order->id }}</h5>
        </div>
        <div class="panel-body">
            <form id="adjust-form" class="form-horizontal">
                @csrf
                <input type="hidden" name="CsOrder[id]" value="{{ $order->id }}">

                <div class="form-group">
                    <label class="col-lg-3 control-label">New value for <code>{{ $field }}</code> :</label>
                    <div class="col-lg-9">
                        <input type="number" step="0.01" name="CsOrder[value]" class="form-control"
                               value="{{ (float)($order->{$field} ?? 0) }}">
                    </div>
                </div>

                <div class="form-group">
                    <div class="col-lg-offset-3 col-lg-9">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="{{ $backUrl }}" class="btn btn-default">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
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
            }).then(function (r) { return r.json(); }).then(function (res) {
                alert(res.message || 'Processed');
            });
        });
    </script>
@endpush
