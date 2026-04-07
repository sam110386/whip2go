<div>
    <input type="hidden" name="CsSetting[vehicle_id]" value="{{ $vehicle ?? '' }}">
    <div style="margin: 8px 0;">
        <label>GPS Provider
            <input type="text" name="CsSetting[gps_provider]" value="{{ data_get($csSetting, 'gps_provider', '') }}">
        </label>
    </div>
    <div style="margin: 8px 0;">
        <label>Starter Provider
            <input type="text" name="CsSetting[passtime]" value="{{ data_get($csSetting, 'passtime', '') }}">
        </label>
    </div>
</div>

