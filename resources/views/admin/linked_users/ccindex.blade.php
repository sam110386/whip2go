@extends('admin.layouts.app')

@section('title', 'User Cards')

@section('content')
    <h1>User cards</h1>
    <p><a href="/cloud/linked_users/ccadd/{{ base64_encode((string)$userId) }}">Add card</a> · <a href="/cloud/linked_users/index">Back</a></p>
    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr style="border-bottom:2px solid #ccc; text-align:left;">
                <th style="padding:6px;">Name</th>
                <th style="padding:6px;">Card</th>
                <th style="padding:6px;">Expiry</th>
                <th style="padding:6px;">Default</th>
                <th style="padding:6px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($cards as $c)
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px;">{{ $c->card_name ?? '' }}</td>
                    <td style="padding:6px;">{{ $c->card_number ?? '' }}</td>
                    <td style="padding:6px;">{{ $c->expiry_month ?? '' }}/{{ $c->expiry_year ?? '' }}</td>
                    <td style="padding:6px;">{{ !empty($c->is_default) ? 'Yes' : 'No' }}</td>
                    <td style="padding:6px;">
                        <a href="/cloud/linked_users/makeccdefault/{{ base64_encode((string)$c->id) }}/{{ base64_encode((string)$userId) }}">Make default</a> ·
                        <a href="/cloud/linked_users/ccdelete/{{ base64_encode((string)$c->id) }}/{{ base64_encode((string)$userId) }}" onclick="return confirm('Delete card?')">Delete</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="padding:10px;">No cards found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection

