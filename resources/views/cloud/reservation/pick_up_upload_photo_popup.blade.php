<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <div class="col-sm-12">
        <div class="panel panel-flat">
            <div class="panel-body">
                <form action="#" method="POST" class="form-horizontal" id="pickUpUploadPhoto" enctype="multipart/form-data">
                    @csrf
                    <fieldset>
                        <legend class="text-semibold">Driver Details</legend>
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Uploaded Photo:</label>
                            @if(!empty($pickupData['driver_photo']))
                            <div class="col-lg-8">
                                <img height="150px" width="150px" src="{{ config('app.url') }}files/reservation/{{ $pickupData['driver_photo'] }}">
                            </div>
                            @endif
                        </div>
                        @if($isAdmin !== 1)
                        <div class="form-group">
                            <label class="col-lg-4 control-label">Upload Photo:</label>
                            <div class="col-lg-8">
                                <input type="file" class="form-control" name="data[OrderDepositRule][driver_photo]" id="VehicleDriverPhoto" data-show-preview="false" data-show-upload="false">
                                <span class="help-block">Please upload driver photo. (MAX File Size {{ ini_get('upload_max_filesize') }})</span>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-lg-6">
                                <button type="button" class="btn btn-primary pull-right" onclick="updateVehicleDetails()">Upload</button>
                            </div>
                        </div>
                        @endif
                    </fieldset>
                    <input type="hidden" name="OrderDepositRule[reservation_id]" value="{{ $orderid }}">
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
</div>
