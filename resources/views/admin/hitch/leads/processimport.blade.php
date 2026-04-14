@extends('layouts.admin')
@section('content')
<script type="text/javascript">
    jQuery(document).ready(function () {
        jQuery("#frmadmin").validate();
    });
</script>
<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Process</span> -Lead Import</h4>
        </div>
    </div>
</div>
<div class="row">
    @include('partials.flash')
</div>
<div class="panel">
    <div class="row">
        <form action="/admin/hitch/leads/processimport/{{ $count }}/{{ $filename }}/{{ $dealerid }}/{{ $skip }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
            @csrf
            <div class="panel-body">
                <div class="col-lg-6">
                    <table width="100%" cellpadding="1" cellspacing="1" border="0" class="table table-responsive">
                        <thead>
                            <tr>
                                <th style="width:5px;">Phone</th>
                                <th style="width:5px;">First Name</th>
                                <th style="width:5px;">Last Name</th>
                                <th style="width:5px;">Email</th>
                                <th style="width:5px;">Payroll</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($previewData as $preview)
                                <tr>
                                    <td>{{ $preview[0] ?? '' }}</td>
                                    <td>{{ $preview[1] ?? '' }}</td>
                                    <td>{{ $preview[2] ?? '' }}</td>
                                    <td>{{ $preview[3] ?? '' }}</td>
                                    <td>{{ !empty($preview[4]) ? 'Yes' : 'No' }}</td>
                                </tr>
                            @endforeach
                            <tr><td height="6" colspan="5"></td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">&nbsp;</label>
                        <div class="col-lg-6">
                            <button type="submit" class="btn">Process</button>
                            <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/hitch/leads/index')">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
