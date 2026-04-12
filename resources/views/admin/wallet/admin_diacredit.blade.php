@extends('admin.layouts.app')

@section('title', 'Add DIA Credits')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <a href="/admin/wallet/index/{{ $userid }}"><i class="icon-arrow-left52 position-left"></i></a>
                    <span class="text-semibold">Add</span> — DIA Credits
                </h4>
            </div>
        </div>
    </div>

    @if(session('error'))<p style="color:red;">{{ session('error') }}</p>@endif

    <div class="panel">
        <div class="panel-body">
            <p><strong>Stripe is not fully wired in Laravel yet.</strong> <code>admin/wallet/createintent</code> returns a stub response, so the legacy Stripe Elements flow from Cake will not complete here until PaymentProcessor is ported.</p>
            <p>If you POST <code>admin/wallet/diacreditprocess</code> with a JSON body matching the legacy shape (amount in cents, userid base64, transaction id), wallet ledger + balance are updated for testing.</p>
        </div>
    </div>
@endsection
