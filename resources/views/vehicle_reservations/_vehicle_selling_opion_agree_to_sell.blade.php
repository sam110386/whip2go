<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <div class="panel panel-flat">
        <div class="panel-body">
            @if(!empty($booking))

                <form action="#" method="POST" class="form-horizontal" id="vehicleSellingOpionAgreeToSellForm"
                    enctype="multipart/form-data">
                    @csrf

                    <fieldset>
                        <legend class="text-semibold">Please upload vehicle documents</legend>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Vehicle Invoice:</label>
                            @if(!empty($booking['selling_option']['invoice']))
                                <div class="col-lg-2">
                                    <a href="{{ url('img/custom/vehicle_photo/' . $booking['selling_option']['invoice']) }}"
                                        class="fancybox" title="Vehicle Invoice" escape="false">
                                        <i class="icon-magazine icon-2x"></i>
                                    </a>
                                </div>
                            @endif
                            <div class="col-lg-6">
                                <input type="file" class="form-control" name="OrderDepositRule[selling_option][invoice]"
                                    id="SellingOptionInvoice" data-show-preview="false" data-show-upload="false">
                                <span class="help-block">
                                    Please upload doc. (MAX File Size {{ ini_get('upload_max_filesize') }})
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-4 control-label">Vehicle Buyer's order:</label>
                            @if(!empty($booking['selling_option']['buyer_order']))
                                <div class="col-lg-2">
                                    <a href="{{ url('img/custom/vehicle_photo/' . $booking['selling_option']['buyer_order']) }}"
                                        class="fancybox" title="Vehicle Buyer Order" escape="false">
                                        <i class="icon-magazine icon-2x"></i>
                                    </a>
                                </div>
                            @endif
                            <div class="col-lg-6">
                                <input type="file" class="form-control" name="OrderDepositRule[selling_option][buyer_order]"
                                    id="SellingOptionBuyerOrder" data-show-preview="false" data-show-upload="false">
                                <span class="help-block">
                                    Please upload doc. (MAX File Size {{ ini_get('upload_max_filesize') }})
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-lg-6">
                                <button type="button" class="btn btn-primary pull-right" onclick="saveVehicleAgreeToSell()">
                                    Upload & Save
                                </button>
                            </div>
                        </div>
                    </fieldset>

                    <input type="hidden" name="OrderDepositRule[id]" value="{{ $booking['id'] }}">
                </form>
            @else
                <div class="alert alert-info">
                    <span class="text-semibold">Info!</span> No booking found.
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
</div>