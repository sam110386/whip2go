<div class="panel">
    <form action="{{ config('app.url') }}admin/featured_vehicles/loadAttributeStep2Popup" method="POST" class="form-horizontal" id="FeaturedVehicleAttributeForm">
        @csrf
        <div class="panel-body">
            <div class="row">
                <div class="col-md-12">
                    <div id="VehicleAttributes" rel-attributes="1">
                        <div class="form-group" id="ele-1">
                            <label class="col-lg-2 control-label text-bold">Attribute Name #1:<font class="requiredField">*</font></label>
                            <div class="col-lg-6">
                                <input type="text" name="FeaturedVehicle[attribute][1]" class="required form-control alphanumericwithspace" placeholder="Like Color, Trim..." />
                            </div>
                            <div class="col-lg-4"><a href="javascript:;" onclick="featuredVehicleAddAttribute_More(true)"><i class="icon-plus-circle2 icon-2x"></i></a></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">&nbsp;</label>
                <div class="col-lg-2">
                    <button type="button" class="btn left-margin btn-primary w-100" onClick="featuredVehicleAttributeStep1()">Next</button>
                </div>
            </div>
        </div>
    </form>
</div>
