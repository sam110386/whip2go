<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <div class="panel panel-flat">
        <div class="panel-heading">
            <h5 class="panel-title">
                {{ $vehicledepndend ? 'Vehicle GPS Setting' : 'Dealer GPS Setting' }}
            </h5>
        </div>
        <div class="panel-body">
            <form method="POST" action="" class="form-horizontal" id="GpsSettingAdminGpsSettingForm">
                @csrf
                <div class="form-group">
                    <label class="col-lg-4 control-label">GPS Provider :</label>
                    <div class="col-lg-8">
                        <select name="CsSetting[gps_provider]" id="CsSettingGpsProvider" class="form-control">
                            <option value="geotab" @selected(old('CsSetting.gps_provider', data_get($CsSetting, 'gps_provider', '')) == 'geotab')>
                                GeoTab
                            </option>
                            <option value="passtime" @selected(old('CsSetting.gps_provider', data_get($CsSetting, 'gps_provider', '')) == 'passtime')>
                                Passtime
                            </option>
                            <option value="ituran" @selected(old('CsSetting.gps_provider', data_get($CsSetting, 'gps_provider', '')) == 'ituran')>
                                Ituran
                            </option>
                            <option value="onestepgps" @selected(old('CsSetting.gps_provider', data_get($CsSetting, 'gps_provider', '')) == 'onestepgps')>
                                One Step GPS
                            </option>
                            <option value="autopi" @selected(old('CsSetting.gps_provider', data_get($CsSetting, 'gps_provider', '')) == 'autopi')>
                                AutoPi
                            </option>
                        </select>
                        <em>This setting will be used to get vehicle Geo code data. Please setup respective setting to
                            make it working</em>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">GPS Starter :</label>
                    <div class="col-lg-8">
                        <select name="CsSetting[passtime]" id="CsSettingPasstime" class="form-control">
                            <option value="geotab" @selected(old('CsSetting.passtime', data_get($CsSetting, 'passtime', '')) == 'geotab')>
                                GeoTab
                            </option>
                            <option value="passtime" @selected(old('CsSetting.passtime', data_get($CsSetting, 'passtime', '')) == 'passtime')>
                                Passtime
                            </option>
                            <option value="ituran" @selected(old('CsSetting.passtime', data_get($CsSetting, 'passtime', '')) == 'ituran')>
                                Ituran
                            </option>
                            <option value="onestepgps" @selected(old('CsSetting.passtime', data_get($CsSetting, 'passtime', '')) == 'onestepgps')>
                                One Step GPS
                            </option>
                            <option value="autopi" @selected(old('CsSetting.passtime', data_get($CsSetting, 'passtime', '')) == 'autopi')>
                                AutoPi
                            </option>
                            <option value="geotabkeyless" @selected(old('CsSetting.passtime', data_get($CsSetting, 'passtime', '')) == 'geotabkeyless')>
                                GeoTab Keyless
                            </option>
                        </select>
                        <em>This setting will be used for Starter Enable/Disable functioning. Please setup respective
                            setting to make it working</em>
                    </div>
                </div>

                <div class="form-group autopi">
                    <label class="col-lg-4 control-label">
                        AutoPi Api Token :<font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="CsSetting[autopi_token]" id="CsSettingAutopiToken"
                            value="{{ old('CsSetting.autopi_token', data_get($CsSetting, 'autopi_token', '')) }}"
                            class="form-control" />
                    </div>
                </div>

                <div class="form-group smartcar">
                    <label class="col-lg-4 control-label">
                        SmartCar Client ID# :<font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="CsSetting[smartcar_client_id]" id="CsSettingSmartcarClientId"
                            value="{{ old('CsSetting.smartcar_client_id', data_get($CsSetting, 'smartcar_client_id', '')) }}"
                            class="form-control" />
                    </div>
                </div>

                <div class="form-group smartcar">
                    <label class="col-lg-4 control-label">
                        SmartCar Secret :<font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="CsSetting[smartcar_secret]" id="CsSettingSmartcarSecret"
                            value="{{ old('CsSetting.smartcar_secret', data_get($CsSetting, 'smartcar_secret', '')) }}"
                            class="form-control" />
                    </div>
                </div>

                <div class="form-group passtime">
                    <label class="col-lg-4 control-label">
                        Passtime Dealer# :<font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="CsSetting[passtime_dealerid]" id="CsSettingPasstimeDealerid"
                            value="{{ old('CsSetting.passtime_dealerid', data_get($CsSetting, 'passtime_dealerid', '')) }}"
                            class="form-control" />
                    </div>
                </div>

                <div class="form-group ituran">
                    <label class="col-lg-4 control-label">
                        Ituran Username :<font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="CsSetting[ituran_usr]" id="CsSettingIturanUsr"
                            value="{{ old('CsSetting.ituran_usr', data_get($CsSetting, 'ituran_usr', '')) }}"
                            class="form-control" />
                    </div>
                </div>

                <div class="form-group ituran">
                    <label class="col-lg-4 control-label">
                        Ituran Password :<font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-8">
                        <input type="password" name="CsSetting[ituran_pwd]" id="CsSettingIturanPwd"
                            value="{{ old('CsSetting.ituran_pwd', data_get($CsSetting, 'ituran_pwd', '')) }}"
                            class="form-control" />
                    </div>
                </div>

                <div class="form-group geotab">
                    <label class="col-lg-4 control-label">
                        GeoTab Server Name :<font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="CsSetting[geotab_server]" id="CsSettingGeotabServer"
                            value="{{ old('CsSetting.geotab_server', data_get($CsSetting, 'geotab_server', '')) }}"
                            class="form-control" />
                    </div>
                </div>

                <div class="form-group geotab">
                    <label class="col-lg-4 control-label">
                        GeoTab Username :<font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="CsSetting[geotab_user]" id="CsSettingGeotabUser"
                            value="{{ old('CsSetting.geotab_user', data_get($CsSetting, 'geotab_user', '')) }}"
                            class="form-control" />
                    </div>
                </div>

                <div class="form-group geotab">
                    <label class="col-lg-4 control-label">
                        GeoTab Password :<font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-8">
                        <input type="password" name="CsSetting[geotab_pwd]" id="CsSettingGeotabPwd"
                            value="{{ old('CsSetting.geotab_pwd', data_get($CsSetting, 'geotab_pwd', '')) }}"
                            class="form-control" />
                    </div>
                </div>

                <div class="form-group geotab">
                    <label class="col-lg-4 control-label">
                        GeoTab Database :<font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="CsSetting[geotab_db]" id="CsSettingGeotabDb"
                            value="{{ old('CsSetting.geotab_db', data_get($CsSetting, 'geotab_db', '')) }}"
                            class="form-control" />
                    </div>
                </div>

                <div class="form-group geotab">
                    <label class="col-lg-4 control-label"></label>
                    <div class="col-lg-8">
                        <button type="button" class="btn btn-primary bg-primary form-control"
                            onclick="validateGeoTab()">Validate Geotab Settings</button>
                    </div>
                </div>

                <div class="form-group onestepgps">
                    <label class="col-lg-4 control-label">
                        One Step GPS Key :<font class="requiredField">*</font>
                    </label>
                    <div class="col-lg-8">
                        <input type="text" name="CsSetting[onestepgps]" id="CsSettingOnestepgps"
                            value="{{ old('CsSetting.onestepgps', data_get($CsSetting, 'onestepgps', '')) }}"
                            class="form-control" />
                    </div>
                </div>

                <div class="form-group onestepgps">
                    <label class="col-lg-4 control-label"></label>
                    <div class="col-lg-8">
                        <button type="button" class="btn btn-primary bg-primary form-control"
                            onclick="validateOneStepGPSKey()">
                            Validate One Step GPS Key
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-4 control-label">&nbsp;</label>
                    <div class="col-lg-8">
                        <button type="button" class="btn btn-primary full-width" onclick="saveGpsSetting()">
                            Save <i class="icon-arrow-right14 position-right"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-4 control-label">&nbsp;</label>
                    <div class="col-lg-8">
                        <button type="button" class="btn btn-danger bg-danger full-width"
                            onclick="deleteGpsSetting('{{ $vehicle }}')">
                            Delete For Vehicle <i class="icon-trash position-right"></i>
                        </button>
                    </div>
                </div>

                <input type="hidden" name="CsSetting[vehicle_id]" value="{{ $vehicle }}" />
            </form>
        </div>
    </div>
</div>