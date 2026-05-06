@extends('admin.layouts.app')

@section('title', 'Review Returns')

@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4>
                <i class="icon-arrow-left52 position-left"></i>
                <span class="text-semibold">Review</span> Returns
            </h4>
        </div>
    </div>

    <div class="breadcrumb-line">
        <ul class="breadcrumb">
            <li><a href="{{ url('admin/dashboard') }}"><i class="icon-home2 position-left"></i> Home</a></li>
            <li class="active">Review Returns</li>
        </ul>
    </div>
</div>

<div class="content">
    @includeif('partials.flash')

    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">Return filters</h5>
        </div>

        <div class="panel-body">
            <form method="get" action="{{ $basePath }}/nonreview" class="form-horizontal">
                <div class="form-group">
                    <label class="col-lg-3 control-label text-semibold">Rows:</label>
                    <div class="col-lg-9">
                        <select name="Record[limit]" onchange="this.form.submit()" class="form-control">
                            @foreach ([25,50,100,200] as $opt)
                                <option value="{{ $opt }}" @selected((int)($limit ?? 25) === $opt)>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel panel-flat">
        <div class="panel-body">
            <div id="listing">
                @include('admin.booking_reviews._nonreview_table', ['nonreviews' => $nonreviews, 'basePath' => $basePath])
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/admin_booking_reviews.js') }}"></script>
@endpush
