@extends('admin.layouts.app')
@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Intercom-</span> Carousels</h4>
        </div>
    </div>
</div>
<div class="row">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
</div>
<div class="panel">
    <div class="row">
        <form action="{{ url('/admin/intercom/carousels/index') }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
            @csrf
            <div class="panel-body">
                <legend class="text-size-large text-bold">Details</legend>
                <div class="col-lg-6">
                    <div class="form-group">
                        <label class="col-lg-5 control-label">Find Car Screen :<span class="text-danger">*</span></label>
                        <div class="col-lg-3">
                            <input type="text" name="Carousel[1][screen]" class="form-control required" readonly value="findcar" />
                        </div>
                        <div class="col-lg-3">
                            <input type="text" name="Carousel[1][intercom]" class="form-control" value="{{ $obj['findcar'] ?? '' }}" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-5 control-label">Car Detail Screen :<span class="text-danger">*</span></label>
                        <div class="col-lg-3">
                            <input type="text" name="Carousel[2][screen]" class="form-control required" readonly value="cardetail" />
                        </div>
                        <div class="col-lg-3">
                            <input type="text" name="Carousel[2][intercom]" class="form-control" value="{{ $obj['cardetail'] ?? '' }}" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-5 control-label">Home Screen :<span class="text-danger">*</span></label>
                        <div class="col-lg-3">
                            <input type="text" name="Carousel[3][screen]" class="form-control required" readonly value="home" />
                        </div>
                        <div class="col-lg-3">
                            <input type="text" name="Carousel[3][intercom]" class="form-control" value="{{ $obj['home'] ?? '' }}" />
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            <button type="submit" class="btn">Save</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery("#frmadmin").validate();
    });
</script>
@endsection
