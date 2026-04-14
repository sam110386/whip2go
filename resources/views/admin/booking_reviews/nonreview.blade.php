@extends('layouts.admin')

@section('title', 'Review Returns')

@section('content')
    <h1>Review returns</h1>
    @if(session('success'))<p style="color:green;">{{ session('success') }}</p>@endif
    @if(session('error'))<p style="color:red;">{{ session('error') }}</p>@endif

    <form method="get" action="{{ $basePath }}/nonreview" style="margin-bottom:12px;">
        <label>Rows
            <select name="Record[limit]" onchange="this.form.submit()">
                @foreach ([25,50,100,200] as $opt)
                    <option value="{{ $opt }}" @selected((int)($limit ?? 25) === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </label>
    </form>

    <div id="listing">
        @include('admin.booking_reviews._nonreview_table', ['nonreviews' => $nonreviews, 'basePath' => $basePath])
    </div>
@endsection
