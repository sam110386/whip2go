@extends('layouts.main')

@section('title', 'Wallet Balance')
@section('header_title', 'Wallet')

@section('content')
    @if(session('success'))<p style="color:green;">{{ session('success') }}</p>@endif
    @if(session('error'))<p style="color:red;">{{ session('error') }}</p>@endif

    <p><strong>My balance:</strong> ${{ $wallet->balance ?? 0 }}</p>
    <h2>Transaction history</h2>

    <div id="postsPaging">
        @include('admin.wallet._transaction_panel', [
            'transactions' => $transactions,
            'keyword' => $keyword ?? '',
            'limit' => $limit ?? 50,
            'adminContext' => false,
            'useridB64' => null,
        ])
    </div>
@endsection
