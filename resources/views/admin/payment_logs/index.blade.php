@extends('admin.layouts.app')

@section('title', 'Payment Logs')

@section('content')
    <h1>Payment logs</h1>
    <form method="get" action="{{ str_contains(request()->path(), '/cloud/') ? '/cloud/payment_logs/index' : '/admin/payment_logs/index' }}" style="margin-bottom:10px;">
        <label>From <input type="date" name="Search[date_from]" value="{{ $dateFrom ?? '' }}"></label>
        <label>To <input type="date" name="Search[date_to]" value="{{ $dateTo ?? '' }}"></label>
        <label>Keyword <input type="text" name="Search[keyword]" value="{{ $keyword ?? '' }}"></label>
        <label>Rows
            <select name="Record[limit]" onchange="this.form.submit()">
                @foreach ([25,50,100,200] as $opt)
                    <option value="{{ $opt }}" @selected((int)($limit ?? 50) === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">Search</button>
    </form>

    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr style="border-bottom:2px solid #ccc; text-align:left;">
                <th style="padding:6px;">ID</th>
                <th style="padding:6px;">User</th>
                <th style="padding:6px;">Txn ID</th>
                <th style="padding:6px;">Reference</th>
                <th style="padding:6px;">Message</th>
                <th style="padding:6px;">Created</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px;">{{ $r->id }}</td>
                    <td style="padding:6px;">{{ $r->user_id ?? '' }}</td>
                    <td style="padding:6px;">{{ $r->transaction_id ?? '' }}</td>
                    <td style="padding:6px;">{{ $r->reference_id ?? '' }}</td>
                    <td style="padding:6px;">{{ $r->message ?? '' }}</td>
                    <td style="padding:6px;">{{ $r->created ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding:10px;">No logs found.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $rows->links() }}
@endsection

