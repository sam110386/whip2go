@extends('admin.layouts.app')

@section('title', $title ?? 'Add Vehicle Maintenance Record')

@push('styles')
    <link rel="stylesheet" href="{{ legacy_asset('css/select2.css') }}">
    <style type="text/css">
        .kv-file-upload {
            display: none;
        }

        .krajee-default.file-preview-frame .kv-file-content {
            width: 210px;
            height: 160px;
        }
    </style>
@endpush

@php
    $vehicleIssue ??= null;
    $images = data_get($vehicleIssue, 'images', []);
    $initialPreview = [];
    $initialPreviewConfig = [];

    foreach ($images as $img) {
        $fileUrl = legacy_asset('img/custom/vehicle_issue/' . data_get($img, 'image'));
        $ext = strtolower(pathinfo(data_get($img, 'image'), PATHINFO_EXTENSION));
        $preview = [
            'caption' => data_get($img, 'image'),
            'filename' => data_get($img, 'image'),
            'key' => data_get($img, 'id'),
            'width' => '120px',
            'downloadUrl' => $fileUrl
        ];

        if ($ext == 'pdf')
            $preview['type'] = 'pdf';
        if (in_array($ext, ['doc', 'docx']))
            $preview['type'] = 'gdocs';

        $initialPreview[] = $fileUrl;
        $initialPreviewConfig[] = $preview;
    }
@endphp

@section('content')
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title">
                <h4><i class="icon-arrow-left52 position-left"></i> {{ $title }}</h4>
            </div>
        </div>
    </div>

    <div class="row">
        @includeif('partials.flash')
    </div>

    <div class="panel">
        <form method="POST" name="frmadmin" id="frmadmin" class="form-horizontal" enctype="multipart/form-data">
            @csrf
            <div class="panel-body">
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Vehicle# :<font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-4">
                        <input type="text" name="CsVehicleIssue[vehicle_id]" id="CsVehicleIssueVehicleId"
                            class="vehicle_id required textfield" value="{{ data_get($vehicleIssue, 'vehicle_id', '') }}"
                            placeholder="Select Vehicle.." style="width:100%;">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Driver :<font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-4">
                        <input type="text" name="CsVehicleIssue[renter_id]" id="CsVehicleIssueRenterId"
                            class="renter_id required textfield" value="{{ data_get($vehicleIssue, 'renter_id', '') }}"
                            placeholder="Select Driver.." style="width:100%;">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Status:
                    </label>
                    <div class="col-lg-4">
                        <select name="CsVehicleIssue[status]" class="textfield form-control">
                            @foreach($issueStatus as $k => $v)
                                <option value="{{ $k }}" @selected(data_get($vehicleIssue, 'status', 0) == $k)>
                                    {{ $v }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Maintenance Details: <font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-4">
                        <textarea name="CsVehicleIssue[maintenance_issue_detail]"
                            class="textfield required form-control">{{ data_get($vehicleIssue, 'maintenance_issue_detail', '') }}</textarea>
                        <em>Maintenance request details if any</em>
                    </div>
                </div>

                <legend>Task Details</legend>

                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Vehicle Scheduled for service:
                    </label>
                    <div class="col-lg-4">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="icon-calendar22"></i>
                            </span>
                            <input type="text" name="CsVehicleIssue[vehicle_scheduled_for_service]"
                                id="CsVehicleIssueVehicleScheduledForService" class="form-control date"
                                value="{{ data_get($vehicleIssue, 'vehicle_scheduled_for_service', '') }}">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Vehicle serviced: <font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-4">
                        <select name="CsVehicleIssue[vehicle_serviced]" class="required form-control">
                            <option value="">--Select--</option>
                            <option value="0" @selected(data_get($vehicleIssue, 'vehicle_serviced', '') === '0')>
                                Not Yet
                            </option>
                            <option value="1" @selected(data_get($vehicleIssue, 'vehicle_serviced', '') === '1')>
                                Yes, Done
                            </option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Service paid for:
                    </label>
                    <div class="col-lg-4">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="icon-coin-dollar"></i>
                            </span>
                            <input type="text" name="CsVehicleIssue[service_paid]" class="form-control digit"
                                value="{{ data_get($vehicleIssue, 'service_paid', '') }}">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Current Odometer:
                    </label>
                    <div class="col-lg-4">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="icon-meter2"></i>
                            </span>
                            <input type="text" name="CsVehicleIssue[current_odometer]" id="CsVehicleIssueCurrentOdometer"
                                class="form-control digit" value="{{ data_get($vehicleIssue, 'current_odometer', '') }}">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Next Service Mileage Counter:
                    </label>
                    <div class="col-lg-4">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <i class="icon-meter-fast"></i>
                            </span>
                            <input type="text" name="CsVehicleIssue[next_service_odometer]" class="form-control digit"
                                value="{{ data_get($vehicleIssue, 'next_service_odometer', '') }}">
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <label class="checkbox-inline">
                            <input name="CsVehicleIssue[next_service_odometer_checkbox]" type="checkbox" class="styled"
                                value="1">
                            Update To Vehicle Module
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">
                        Attach Receipt
                    </label>
                    <div class="col-lg-8">
                        <input type="file" class="fileinputajax" multiple name="vehicleimage" data-show-preview="true"
                            data-show-upload="false">
                        <span class="help-block">You can select multiple images.</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-2 control-label">&nbsp;</label>
                    <div class="col-lg-6">
                        <button type="button" class="btn btn-primary" onclick="saveForm()">
                            {{ !empty(data_get($vehicleIssue, 'id')) ? 'Update' : 'Save' }}
                        </button>
                        <button type="button" class="btn left-margin btn-cancel"
                            onclick="goBack('/admin/vehicle_issues/index')">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <input type="hidden" name="CsVehicleIssue[id]" id="CsVehicleIssueId"
                value="{{ data_get($vehicleIssue, 'id', '') }}">
            <input type="hidden" name="CsVehicleIssue[user_id]" id="CsVehicleIssueUserId"
                value="{{ data_get($vehicleIssue, 'user_id', '') }}">
            <input type="hidden" name="CsVehicleIssue[type]" value="6">
        </form>
    </div>
@endsection

@push('scripts')
    <script src="{{ legacy_asset('js/select2.js') }}"></script>
    <script src="{{ legacy_asset('js/assets/js/plugins/uploaders/fileinput.min.js') }}"></script>

    <script type="text/javascript">
        function format(item) {
            return item.tag;
        }
        jQuery(document).ready(function () {

            jQuery("#CsVehicleIssueVehicleScheduledForService").datetimepicker({
                format: 'MM/DD/YYYY'
            });

            jQuery("#frmadmin").validate({
                ignore: [':hidden:not(.vehicle_id)', ':hidden:not(.renter_id)']
            });

            jQuery("#CsVehicleIssueVehicleId").select2({
                data: {
                    results: {},
                    text: 'tag'
                },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Vehicle ",
                minimumInputLength: 1,
                ajax: {
                    url: SITE_URL + "admin/vehicle_issues/getVehicle",
                    dataType: "json",
                    type: "GET",
                    data: function (params) {
                        return {
                            term: params
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: jQuery.map(data, function (item) {
                                return {
                                    tag: item.tag,
                                    id: item.id,
                                    user_id: item.user_id,
                                    last_mile: item.last_mile
                                };
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    var id = $(element).val();
                    if (id !== "") {
                        $.ajax(SITE_URL + "admin/vehicle_issues/getVehicle", {
                            dataType: "json",
                            type: 'POST',
                            data: {
                                id: id
                            }
                        }).done(function (data) {
                            callback(data[0]);
                            jQuery("#CsVehicleIssueUserId").val(data[0].user_id);
                        });
                    }
                }
            });

            jQuery("#CsVehicleIssueVehicleId").on('select2-selecting', function (e) {
                jQuery("#CsVehicleIssueUserId").val(e.choice.user_id);
                jQuery("#CsVehicleIssueCurrentOdometer").val(e.choice.last_mile);
            });

            jQuery("#CsVehicleIssueRenterId").select2({
                data: {
                    results: {},
                    text: 'tag'
                },
                formatSelection: format,
                formatResult: format,
                placeholder: "Select Driver ",
                minimumInputLength: 1,
                ajax: {
                    url: SITE_URL + "admin/bookings/customerautocomplete",
                    dataType: "json",
                    type: "GET",
                    data: function (params) {
                        return {
                            term: params
                        };
                    }, processResults: function (data) {
                        return {
                            results: jQuery.map(data, function (item) {
                                return {
                                    tag: item.tag,
                                    id: item.id
                                };
                            })
                        };
                    }
                },
                initSelection: function (element, callback) {
                    var id = $(element).val();
                    if (id !== "") {
                        $.ajax(SITE_URL + "admin/bookings/customerautocomplete", {
                            dataType: "json",
                            type: 'GET',
                            data: {
                                id: id
                            }
                        }).done(function (data) {
                            callback(data[0]);
                        });
                    }
                }
            });
        });

        $(function () {
            $(".fileinputajax").fileinput({
                showUpload: false,
                uploadUrl: SITE_URL + "admin/vehicle_issues/saveImage",
                uploadAsync: true,
                maxFileCount: 15,
                deleteUrl: SITE_URL + "admin/vehicle_issues/deleteImage",
                allowedFileExtensions: ['jpeg', 'jpg', 'png', 'doc', 'docx', 'pdf'],
                initialPreview: {!! json_encode($initialPreview) !!},
                overwriteInitial: false,
                initialPreviewAsData: true,
                initialPreviewFileType: 'image',
                initialPreviewConfig: {!! json_encode($initialPreviewConfig) !!},
                maxFileSize: 1024,
                uploadExtraData: function () {
                    return {
                        id: $("#CsVehicleIssueId").val(),
                        _token: '{{ csrf_token() }}'
                    };
                },
                fileActionSettings: {
                    showDrag: false,
                    showZoom: true,
                    showUpload: false,
                    removeIcon: '<i class="icon-bin"></i>',
                    removeClass: 'btn btn-link btn-xs btn-icon',
                    uploadIcon: '<i class="icon-upload"></i>',
                    uploadClass: 'btn btn-link btn-xs btn-icon',
                    indicatorNew: '<i class="icon-file-plus text-slate"></i>',
                    indicatorSuccess: '<i class="icon-checkmark3 file-icon-large text-success"></i>',
                    indicatorError: '<i class="icon-cross2 text-danger"></i>',
                    indicatorLoading: '<i class="icon-spinner2 spinner text-muted"></i>'
                }
            }).on('fileuploaded', function (event, data, previewId) {
                $("#" + previewId + " button.kv-file-remove").attr('data-key', data.response.key);
            }).on('filebatchuploadcomplete', function () {
                goBack('/admin/vehicle_issues/index');
            });
        });

        function saveForm() {
            if ($("#frmadmin").valid()) {
                jQuery.blockUI({
                    message: '<h1><img src="' + SITE_URL + 'img/select2-spinner.gif" /> Just a moment...</h1>'
                });

                $.post(SITE_URL + 'admin/vehicle_issues/saveMaintenance',
                    $("#frmadmin").serialize(), function (data) {
                        if (data.status == 'success') {
                            $("#CsVehicleIssueId").val(data.recordid);
                            setTimeout(function () {
                                $('.fileinputajax').fileinput('upload');
                            }, 1000);
                        } else {
                            alert(data.message);
                        }
                    }, 'json').fail(function () {
                        alert("error");
                    }).always(function () {
                        jQuery.unblockUI();
                    });
            }
        }
    </script>
@endpush