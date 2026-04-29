@extends('admin.layouts.app')

@section('title', 'Failed Transfer')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">Failed</span> Transfer
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form method="get" action="/admin/transactions/failedtransfer" id="frmSearchadmin" name="frmSearchadmin">
                <div class="row">
                    <div class="col-md-3">
                        From :
                        <input type="date" name="Search[date_from]" class="form-control" value="{{ $date_from ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        To :
                        <input type="date" name="Search[date_to]" class="form-control" value="{{ $date_to ?? '' }}">
                    </div>
                    <div class="col-md-2">
                        <label style="margin-bottom:0;">&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Txn ID</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reportlists as $r)
                        <tr>
                            <td>{{ $r->increment_id ?? $r->cs_order_id }}</td>
                            <td>{{ $r->type }}</td>
                            <td>{{ $r->amount }}</td>
                            <td>{{ $r->transaction_id }}</td>
                            <td>
                                <button type="button" class="btn btn-primary btn-xs" onclick="requeue({{ (int)$r->id }})">Requeue</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No rows.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function requeue(id) {
            fetch('/admin/transactions/requeuefailedtransfer', {
                method: 'POST',
                headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                body: JSON.stringify({id: id})
            }).then(function (r) { return r.json(); }).then(function (res) {
                alert(res.message || 'Done');
                if (res.status) window.location.reload();
            });
        }
    </script>
@endpush
