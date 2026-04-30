@extends('layouts.admin_booking')

@section('title', $title_for_layout ?? 'User MVR Reports')

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i> 
                    <span class="text-semibold">MVR</span> - Reports
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @include('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form method="get" action="{{ $basePath }}/index" id="frmSearchadmin" name="frmSearchadmin">
                <div class="row">
                    <div class="col-md-10">
                        <div class="col-md-3">
                            Keyword :
                            <input type="text" name="keyword" class="form-control" maxlength="50" value="{{ $keyword }}">
                        </div>
                        <div class="col-md-3">
                            Search in :
                            <select name="searchin" class="form-control">
                                <option value="first_name" @selected($searchin === '' || $searchin === 'first_name')>First Name</option>
                                <option value="last_name" @selected($searchin === 'last_name')>Last Name</option>
                                <option value="contact_number" @selected($searchin === 'contact_number')>Phone#</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label style="margin-bottom: 0px;">&nbsp;</label>
                            <input type="submit" name="search" value="APPLY" class="btn btn-primary" alt="Next">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="panel">
        <div class="panel-body">
            <div id="listing">
                @include('admin.mvr_reports.partials.listing', [
                    'users' => $users,
                    'keyword' => $keyword,
                    'searchin' => $searchin,
                    'limit' => $limit,
                    'basePath' => $basePath,
                ])
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{  legacy_asset('js/assets/js/plugins/notifications/sweet_alert.min.js') }}"></script>
@endpush
