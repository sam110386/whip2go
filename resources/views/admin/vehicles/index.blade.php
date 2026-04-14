@extends('layouts.admin')

@section('title', 'Manage Vehicles')

@section('content')
    <h1>Manage Vehicles</h1>

    @if (session('success'))
        <p style="color:#0a0;">{{ session('success') }}</p>
    @endif
    @if (session('error'))
        <p style="color:#b00020;">{{ session('error') }}</p>
    @endif

    <p style="margin: 10px 0;">
        <a href="/admin/vehicles/add">Add Vehicle</a>
    </p>

    <form method="get" action="/admin/vehicles/index" style="margin-bottom:12px; padding:12px; border:1px solid #eee;">
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:flex-end;">
            <div>
                <label>Search field</label><br>
                <select name="Search[searchin]">
                    <option value="" @selected($searchin === '')>All (name + VIN)</option>
                    <option value="vehicle_name" @selected($searchin === 'vehicle_name')>Car #</option>
                    <option value="vin_no" @selected($searchin === 'vin_no')>VIN #</option>
                    <option value="plate_number" @selected($searchin === 'plate_number')>Plate Number</option>
                </select>
            </div>
            <div>
                <label>Keyword</label><br>
                <input type="text" name="Search[keyword]" value="{{ $keyword }}" placeholder="keyword">
            </div>
            <div>
                <label>Status / filter</label><br>
                <select name="Search[show]">
                    <option value="">All</option>
                    @foreach ($showArr as $k => $label)
                        <option value="{{ $k }}" @selected((string)$show === (string)$k)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Owner user id</label><br>
                <input type="number" name="Search[user_id]" value="{{ $userId }}" placeholder="user_id" style="width:110px;">
            </div>
            <div>
                <label>Featured</label><br>
                <select name="Search[type]">
                    <option value="" @selected($type === '')>Any</option>
                    <option value="featured" @selected($type === 'featured')>Featured</option>
                    <option value="regular" @selected($type === 'regular')>Regular</option>
                </select>
            </div>
            <div>
                <label>Visibility</label><br>
                <select name="Search[visibility]">
                    <option value="" @selected($visibility === '')>Any</option>
                    <option value="1" @selected($visibility === '1')>Visible</option>
                    <option value="0" @selected($visibility === '0')>Hidden</option>
                </select>
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
                <button type="submit" name="export" value="Export" formaction="/admin/vehicles/index">Export CSV</button>
            </div>
        </div>
    </form>

    @include('admin.vehicles._index_table', [
        'vehicleDetails' => $vehicleDetails,
        'listContext' => 'admin',
    ])
@endsection
