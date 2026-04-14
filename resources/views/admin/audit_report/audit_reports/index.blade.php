@extends('layouts.admin')

@section('content')
<script type="text/javascript">
    jQuery(document).ready(function() {
    });
</script>
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
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Audit</span> - Reports</h4>
        </div>
        <div class="heading-elements">
            <a href="{{ url('admin/audit_report/audit_reports/add') }}" class="btn btn-success">Add New</a>
        </div>
    </div>
</div>
<div class="row ">
    @include('partials.flash')
</div>
<div class="panel">
    <div style="width:100%; overflow: visible;" id="postsPaging" class="panel-body">
        @include('admin.audit_report.audit_reports._admin_index')
    </div>
</div>
@endsection
