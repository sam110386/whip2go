@extends('admin.layouts.app')
@section('content')
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Manage</span> - Promo Rule Users</h4>
        </div>
        <div class="heading-elements">
            <a href="{{ url('/admin/promo_rules/index') }}" class="btn btn-primary" style="float:right;">Back</a>
        </div>
    </div>
</div>
<div class="row">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
</div>
<div class="panel">
    <div class="panel-body">
        <form action="{{ url('/admin/promo_rules/promousers/' . $promo) }}" method="POST" id="frmSearchadmin" name="frmSearchadmin">
            @csrf
            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-6">
                        <input type="text" name="Search[keyword]" class="form-control" maxlength="50" placeholder="Search name, phone or email" value="{{ $keyword }}" />
                    </div>
                    <div class="col-md-1">
                        <input type="submit" value="Search" class="btn btn-info" />
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('admin.promo_rules._promousers')
    </div>
</div>
<script type="text/javascript">
    function removePromo(promoid){
        if(!confirm("Are you sure you want to perform this action?")){
            return false;
        }
        jQuery.blockUI({
            message: '<h1><img src="'+SITE_URL+'img/select2-spinner.gif" /> loading...</h1>',
            css:{'z-index':'9999'}
        });
        $.post(SITE_URL+"admin/promo_rules/deletePromoterm", {'promoid':promoid, '_token': '{{ csrf_token() }}'},function (data) {
            jQuery.unblockUI();
            if(!data.status){
                alert(data.message);
            }else{
                window.location.reload(true);
            }
        },'json');
    }
</script>
@endsection
