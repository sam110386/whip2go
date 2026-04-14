<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <form action="#" method="POST" id="openVehicleScanRequestPopup" class="form-horizontal">
        @csrf
        <fieldset>
            <legend class="text-semibold">Request for Vehicle Inspection Scan</legend>
            <div class="form-group">
                <label class="col-lg-3 control-label">Email :</label>
                <div class="col-lg-9">
                    <input type="email" name="Text[email]" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-3 control-label">Token:</label>
                <div class="col-lg-5 control-label">
                    <input type="radio" name="Text[token]" value="old" checked />
                    Send With Existing Token
                </div>
                <div class="col-lg-4 control-label">
                    <input type="radio" name="Text[token]" value="new" />
                    Send With New Token
                </div>
            </div>
        </fieldset>
        <input type="hidden" name="Text[booking]" value="{{ base64_encode($booking) }}">
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" onclick="saveVehicleScanPopupRequest()">Send</button>
</div>
