@extends('admin.layouts.app')

@section('title', $listTitle ?? 'Inspection Schedule Setting')

@php
    $listTitle ??= 'Inspection Schedule Setting';
    $settingData ??= [];
    $scheduels ??= [];
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4>
                    <i class="icon-arrow-left52 position-left"></i>
                    <span class="text-semibold">{{ $listTitle }}</span>
                </h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <div class="panel-body">
            <form action="{{ url('/admin/inspection_settings') }}" method="POST" name="frmadmin" id="frmadmin" class="form-horizontal">
                @csrf
                <legend>Inspection Schedule Setting</legend>

                <div class="form-group">
                    <label class="col-lg-2 control-label">Active:<font class="requiredField">*</font></label>
                    <div class="col-lg-5">
                        <select name="InspectionSetting[status]" class="required form-control">
                            <option value="1" @selected(($settingData['status'] ?? 1) == 1)>Yes</option>
                            <option value="0" @selected(($settingData['status'] ?? 1) == 0)>No</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">Create Schedule Every<font class="requiredField">*</font></label>
                    <div class="col-lg-5">
                        <select name="InspectionSetting[schedule]" class="required form-control">
                            @foreach($scheduels as $k => $v)
                                <option value="{{ $k }}" @selected(($settingData['schedule'] ?? 1) == $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-2 control-label">&nbsp;</label>
                    <div class="col-lg-6">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" class="btn left-margin btn-cancel" onclick="goBack('/admin/vehicle_issues')">Cancel</button>
                    </div>
                </div>

                <input type="hidden" name="InspectionSetting[id]" value="{{ $settingData['id'] ?? '' }}">
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="text/javascript">
        jQuery(document).ready(function () {
            jQuery("#frmadmin").validate();
        });
    </script>
@endpush
