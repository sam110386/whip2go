@extends('layouts.admin')

@section('title', 'Vehicle Offers')

@section('content')
    <h1>Vehicle offers</h1>
    <p><a href="{{ $basePath }}/add">Add offer</a></p>
    <form method="get" action="{{ $basePath }}/index" style="margin-bottom:10px;">
        <label>Rows
            <select name="Record[limit]" onchange="this.form.submit()">
                @foreach ([25,50,100,200] as $opt)
                    <option value="{{ $opt }}" @selected((int)($limit ?? 50) === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </label>
    </form>
    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr style="border-bottom:2px solid #ccc; text-align:left;">
                <th style="padding:6px;">ID</th>
                <th style="padding:6px;">Vehicle</th>
                <th style="padding:6px;">Dealer</th>
                <th style="padding:6px;">Renter</th>
                <th style="padding:6px;">Price</th>
                <th style="padding:6px;">Status</th>
                <th style="padding:6px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($offers as $o)
                <tr style="border-bottom:1px solid #eee;">
                    <td style="padding:6px;">{{ $o->id }}</td>
                    <td style="padding:6px;">{{ $o->vehicle_unique_id }} - {{ $o->vehicle_name }}</td>
                    <td style="padding:6px;">{{ trim(($o->owner_first_name ?? '') . ' ' . ($o->owner_last_name ?? '')) }}</td>
                    <td style="padding:6px;">{{ trim(($o->renter_first_name ?? '') . ' ' . ($o->renter_last_name ?? '')) }}</td>
                    <td style="padding:6px;">{{ number_format((float)($o->offer_price ?? 0), 2) }}</td>
                    <td style="padding:6px;">{{ $o->status }}</td>
                    <td style="padding:6px;">
                        <a href="{{ $basePath }}/view/{{ base64_encode((string)$o->id) }}">View</a> ·
                        <a href="{{ $basePath }}/add/{{ base64_encode((string)$o->id) }}">Edit</a> ·
                        <a href="{{ $basePath }}/duplicate/{{ base64_encode((string)$o->id) }}">Duplicate</a> ·
                        <a href="{{ $basePath }}/cancel/{{ base64_encode((string)$o->id) }}">Cancel</a> ·
                        <a href="{{ $basePath }}/delete/{{ base64_encode((string)$o->id) }}" onclick="return confirm('Delete this offer?')">Delete</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" style="padding:10px;">No offers found.</td></tr>
            @endforelse
        </tbody>
    </table>
    {{ $offers->links() }}
@endsection

