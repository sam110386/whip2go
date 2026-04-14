@extends('layouts.admin')

@section('title', 'Reports')

@section('content')
    <h1>Reports</h1>
    <form method="get" action="/admin/reports/index" style="margin-bottom:10px;">
        <label>From <input type="date" name="Search[date_from]" value="{{ $dateFrom ?? '' }}"></label>
        <label>To <input type="date" name="Search[date_to]" value="{{ $dateTo ?? '' }}"></label>
        <label>Status <input type="number" name="Search[status]" value="{{ $status ?? '' }}" style="width:90px;"></label>
        <label>Rows
            <select name="Record[limit]" onchange="this.form.submit()">
                @foreach ([25,50,100,200] as $opt)
                    <option value="{{ $opt }}" @selected((int)($limit ?? 50) === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit">Search</button>
    </form>
    @include('admin.reports._listing', ['reportlists' => $reportlists])
@endsection

