@extends('layouts.admin')

@section('content')
<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
        </div>
    </div>
</div>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Loan</span> - Stipulations</h4>
        </div>
    </div>
</div>
<div class="row ">
    @include('partials.flash')
</div>

<div class="panel">
    <form action="{{ url('admin/loan/managers/index') }}" method="POST" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <div class="col-md-3">
                <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Keyword">
            </div>
            <div class="col-md-2">
                <input type="submit" name="search" value="SEARCH" class="btn btn-primary">
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('admin.loan._admin_index')
    </div>
</div>
<script src="{{ asset('assets/js/plugins/media/fancybox.min.js') }}"></script>
@endsection
