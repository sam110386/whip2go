<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <div class="col-sm-6">
        <div class="panel panel-flat">
            <div class="panel-body">
                <form action="#" method="POST" class="form-horizontal" id="updateVehicleDetails"
                    enctype="multipart/form-data">
                    @csrf

                    <fieldset>
                        <legend class="text-semibold">Vehicle Details</legend>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Plate Number :</label>
                            <div class="col-lg-8">
                                <a href="#" id="plate_number" class="edit" data-title="Edit"
                                    data-pk="{{ $vehicle->id }}"
                                    data-url="{{ url('admin/vehicles/updateVehicleDetails') }}">
                                    {{ $vehicle->plate_number ?? 'N/A' }}
                                </a>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Registration State :</label>
                            <div class="col-lg-8">
                                <a href="#" id="registered_state" class="registered_state" data-type="select"
                                    data-title="Edit" data-pk="{{ $vehicle->id }}"
                                    data-url="{{ url('admin/vehicles/updateVehicleDetails') }}">
                                    {{ $vehicle->registered_state ?? 'N/A' }}
                                </a>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Registration Exp Date :</label>
                            <div class="col-lg-8">
                                <a href="#" id="reg_name_exp_date" class="reg_name_exp_date" data-type="date"
                                    data-title="Edit" data-pk="{{ $vehicle->id }}"
                                    data-url="{{ url('admin/vehicles/updateVehicleDetails') }}">
                                    {{ $vehicle->reg_name_exp_date ?? '' }}
                                </a>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">GPS Serial # :</label>
                            <div class="col-lg-6">
                                <a href="#" id="gps_serialno" class="edit" data-title="Edit"
                                    data-pk="{{ $vehicle->id }}"
                                    data-url="{{ url('admin/vehicles/updateVehicleDetails') }}">
                                    {{ $vehicle->gps_serialno ?? 'N/A' }}
                                </a>
                            </div>
                            <div class="col-lg-2">
                                <a href="javascript:;" onclick="getVehicleGps('{{ $vehicle->id }}', 'gps_serialno')">
                                    <button type="button" class="btn btn-info btn-icon">
                                        <i class="icon-spinner9"></i>
                                    </button>
                                </a>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Starter Interrupt Device Serial# :</label>
                            <div class="col-lg-6">
                                <a href="#" id="passtime_serialno" class="edit" data-title="Edit"
                                    data-pk="{{ $vehicle->id }}"
                                    data-url="{{ url('admin/vehicles/updateVehicleDetails') }}">
                                    {{ $vehicle->passtime_serialno ?? 'N/A' }}
                                </a>
                            </div>
                            <div class="col-lg-2">
                                <a href="javascript:;"
                                    onclick="getVehicleGps('{{ $vehicle->id }}', 'passtime_serialno')">
                                    <button type="button" class="btn btn-info btn-icon">
                                        <i class="icon-spinner9"></i>
                                    </button>
                                </a>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Wireless GPS Serial# :</label>
                            <div class="col-lg-6">
                                <a href="#" id="wireless_gps_serial" class="edit" data-title="Edit"
                                    data-pk="{{ $vehicle->id }}"
                                    data-url="{{ url('admin/vehicles/updateVehicleDetails') }}">
                                    {{ $vehicle->wireless_gps_serial ?? 'N/A' }}
                                </a>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Download Vehicle Images :</label>
                            <div class="col-lg-6">
                                <a href="javascript:;"
                                    onclick="downloadVehicleImage('{{ base64_encode($vehicle->id) }}')">
                                    <button type="button" class="btn btn-info btn-icon">
                                        <i class="icon-file-download2"></i>
                                    </button>
                                </a>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-12 control-label">
                                <button type="button" class="focus_text btn no-margin"
                                    onclick="VehicleGpsSetting('{{ base64_encode($vehicle->id) }}'); return;">
                                    <i class="icon-satellite-dish2"></i> &nbsp;Vehicle Specific Gps Setting
                                </button>
                            </label>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Registration Doc:</label>
                            @if(!empty($vehicle->registration_image))
                                <div class="col-lg-8">
                                    <img height="150px" width="150px"
                                        src="{{ legacy_asset('img/custom/vehicle_photo/' . $vehicle->registration_image) }}" />
                                </div>
                            @endif
                            <div class="col-lg-8">
                                <input type="file" class="form-control" name="registration_image"
                                    id="VehicleRegistrationImage" data-show-preview="false" data-show-upload="false">
                                <span class="help-block">Please upload registration doc. (MAX File Size:
                                    {{ ini_get('upload_max_filesize') }})</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Vehicle Inspection:</label>
                            @if(!empty($vehicle->inspection_image))
                                <div class="col-lg-8">
                                    <img height="150px" width="150px"
                                        src="{{ legacy_asset('img/custom/vehicle_photo/' . $vehicle->inspection_image) }}" />
                                </div>
                            @endif
                            <div class="col-lg-8">
                                <input type="file" class="form-control" name="inspection_image"
                                    id="VehicleInspectionImage" data-show-preview="false" data-show-upload="false">
                                <span class="help-block">Please upload inspection doc. (MAX File Size:
                                    {{ ini_get('upload_max_filesize') }})</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-lg-6">
                                <button type="button" class="btn btn-primary pull-right"
                                    onclick="updateVehicleDetails()">Upload</button>
                            </div>
                        </div>
                    </fieldset>

                    <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
                </form>
            </div>
        </div>
    </div>

    <div class="col-sm-6" id="gpstester">
        <div class="panel panel-flat">
            <div class="panel-body">
                <legend class="text-semibold">GPS Testing</legend>
                <fieldset class="form-horizontal">
                    <div class="form-group">
                        <label class="col-lg-4 control-label">&nbsp;</label>
                        <div class="col-lg-8">
                            <button type="button" class="focus_text btn no-margin"
                                onclick="CheckOdometer('{{ $vehicle->id }}')">
                                Test Location & Pull Odometer
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">&nbsp;</label>
                        <div class="col-lg-8">
                            <button type="button" class="focus_text btn no-margin"
                                onclick="CheckStaterInterrupt('{{ $vehicle->id }}', '{{ $orderid }}')">
                                Test Starter Interrupt
                            </button>
                        </div>
                    </div>
                </fieldset>
                <div class="form-group messagedisplay"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
</div>

<script type="text/javascript">
    $(function () {
        $('.edit').editable({
            success: function (response, newValue) {
                if (!response.status) return response.msg;
            }
        });

        $('#registered_state').editable({
            source: {!! json_encode($stateopt) !!},
        });

        $('#reg_name_date, #reg_name_exp_date').editable({
            placement: 'right',
            format: 'yyyy-mm-dd',
            viewformat: 'yyyy-mm-dd',
            datepicker: {
                weekStart: 1
            }
        });
    });
</script>