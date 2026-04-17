@extends('admin.layouts.app')
@section('content')
<script type="text/javascript">
    jQuery(document).ready(function() {});
</script>
<div id="myModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content"></div>
    </div>
</div>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Hitch</span> - Leads</h4>
        </div>
        <div class="heading-elements">
            <a href="/admin/hitch/leads/add" class="btn btn-success">Add New</a>
            <a href="/admin/hitch/leads/import" class="btn btn-success">Bulk Import</a>
        </div>
    </div>
</div>
<div class="row">
    @include('partials.flash')
</div>
<div class="panel">
    <form action="/admin/hitch/leads/index" method="POST" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <div class="col-md-2">
                <input type="text" name="Search[keyword]" class="form-control" maxlength="50" value="{{ $keyword }}" placeholder="Keyword..">
            </div>
            <div class="col-md-2">
                <button type="submit" name="search" value="search" class="btn btn-primary">SEARCH</button>
            </div>
        </div>
    </form>
</div>

<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('admin.hitch.leads._table')
    </div>
</div>
<script src="/Hitch/js/hitch.js"></script>
@endsection
