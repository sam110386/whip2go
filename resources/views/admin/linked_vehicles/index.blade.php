@extends('layouts.admin')

@section('title', 'Manage Vehicles (Linked)')

@section('content')
    <h1>Manage Vehicles</h1>
    <p style="font-size:13px;color:#555;">Dealer-linked fleet.</p>

    @if (session('success'))
        <p style="color:#0a0;">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p style="color:#b00020;">{{ session('error') }}</p>
    @endif

    <p style="margin: 10px 0;">
        @if (strpos($listUrl, '/cloud/') !== false)
            <a href="/cloud/linked_vehicles/add">Add Vehicle</a>
            <a href="/admin/vehicles/index" style="margin-left:16px;">Super-admin vehicle list</a>
        @else
            <a href="/admin/linked_vehicles/add">Add Vehicle</a>
            <a href="/admin/vehicles/index" style="margin-left:16px;">Super-admin vehicle list</a>
        @endif
    </p>

    <form method="get" action="{{ $listUrl }}" style="margin-bottom:12px; padding:12px; border:1px solid #eee;">
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
            <div>
                <label>Search field</label><br>
                <select name="Search[searchin]">
                    <option value="" @selected($searchin === '')>All</option>
                    @foreach ($searchOptions as $k => $label)
                        <option value="{{ $k }}" @selected($searchin === $k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Keyword</label><br>
                <input type="text" name="Search[keyword]" value="{{ $keyword }}">
            </div>
            <div>
                <label>Status</label><br>
                <select name="Search[show]">
                    <option value="">All</option>
                    @foreach ($showArr as $k => $label)
                        <option value="{{ $k }}" @selected((string)$show === (string)$k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Dealer user id</label><br>
                <input type="number" name="Search[user_id]" value="{{ $userId }}" style="width:110px;">
            </div>
            <div>
                <label>Rows / page</label><br>
                <select name="Record[limit]" onchange="this.form.submit()">
                    @foreach ([25, 50, 100, 200] as $opt)
                        <option value="{{ $opt }}" @selected((int)$limit === $opt)>{{ $opt }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit">Search</button>
            </div>
            <div>
                <button type="submit" name="export" value="Export" formaction="{{ $listUrl }}">Export CSV</button>
            </div>
        </div>
    </form>

    @include('admin.vehicles._index_table', [
        'vehicleDetails' => $vehicleDetails,
        'listContext' => 'linked',
        'linkedBasePath' => str_contains($listUrl, '/cloud/') ? '/cloud/linked_vehicles' : '/admin/linked_vehicles',
    ])
@endsection
