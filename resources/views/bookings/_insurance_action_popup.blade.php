<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <div class="row form-horizontal">
        @if (!$trip)
            <div class="form-group">Sorry, You are not authorize user.</div>
        @else
            <div class="col-md-12">
                <legend>Insurance Tasks</legend>

                <div class="form-group">
                    <label class="col-lg-8 control-label">
                        Send link to Get Axle details:
                    </label>
                    <div class="col-lg-4">
                        <a href="javascript:void(0)" class="text" title="Send link to Get Axle details"
                            onclick="return sendAxleShareDetails('{{ base64_encode($csOrderId) }}');">
                            <i class="icon-stack-check"></i> Send link to Get Axle details
                        </a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-8 control-label">
                        Send link to connect Axle:
                    </label>
                    <div class="col-lg-4">
                        <a href="javascript:void(0)" class="text" title="Send link to connect Axle"
                            onclick="return sendAxleShareDetails('{{ base64_encode($csOrderId) }}');">
                            <i class="icon-stack-check"></i> Send link to connect Axle
                        </a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-8 control-label">
                        BYOI via Driver Financed Details:
                    </label>
                    <div class="col-lg-4">
                        <a href="javascript:void(0)" class="text" title="BYOI via Driver Financed Details"
                            onclick="return OpenDriverFinancedInsuranceQuoteUploadPopUp('{{ data_get($trip, 'order_deposit_rule.vehicle_reservation_id', '') }}');">
                            <i class="icon-stack-check"></i> BYOI via Driver Financed Details
                        </a>
                    </div>
                </div>

                <div class="form-group">
                    <label class="col-lg-8 control-label">
                        Booking Notes:
                    </label>
                    <div class="col-lg-4">
                        <a href="javascript:void(0)" class="text" title="Booking Notes"
                            onclick="return getBookingNotes('{{ base64_encode(data_get($trip, 'id', '')) }}');">
                            <i class="icon-pencil7"></i> Booking Notes
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>