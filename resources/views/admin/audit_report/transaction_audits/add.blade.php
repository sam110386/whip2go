@extends('layouts.admin')

@section('content')
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery("#frmadmin").validate();
        jQuery('#AuditReportStartDate').datepicker({dateFormat: 'mm/dd/yy'});
        jQuery('#AuditReportEndDate').datepicker({dateFormat: 'mm/dd/yy'});
    });
</script>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">New</span> - Transactions Audit Report</h4>
        </div>
    </div>
</div>
<div class="row ">
    @include('partials.flash')
</div>
<div class="panel">
    <div class="row">
        <form action="{{ url('admin/audit_report/transaction_audits/add') }}" method="POST" id="frmadmin" class="form-horizontal">
            @csrf
            <div class="panel-body">
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">Date From:<span class="text-danger">*</span></label>
                        <div class="col-lg-3">
                            <input type="text" name="AuditReport[start_date]" id="AuditReportStartDate" class="form-control required">
                        </div>
                        <label class="col-lg-2 control-label">Date To :</label>
                        <div class="col-lg-3">
                            <input type="text" name="AuditReport[end_date]" id="AuditReportEndDate" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            <button type="submit" class="btn">Save</button>
                            <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/audit_report/transaction_audits/index')">Return</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
