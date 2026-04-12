@extends('admin.layouts.app')

@section('title', 'Dynamic Fares')

@section('content')
    <h1>Dynamic fares (user #{{ $userId }})</h1>
    <p><a href="/cloud/linked_users/index">Back</a></p>
    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr style="border-bottom:2px solid #ccc; text-align:left;">
                <th style="padding:6px;">ID</th>
                <th style="padding:6px;">Key</th>
                <th style="padding:6px;">Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px;">{{ $r->id }}</td>
                    <td style="padding:6px;">{{ $r->key ?? ($r->name ?? '-') }}</td>
                    <td style="padding:6px;">{{ $r->value ?? ($r->amount ?? '-') }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="padding:10px;">No rows.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

