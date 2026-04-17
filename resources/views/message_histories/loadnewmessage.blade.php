<div class="panel">
    <section class="right_content">
        <section class="reportListingHeading" style="margin-bottom: 7px; float: left; width: 100%;padding: 13px 23px 0;"></section>
        <div style="width:100%; overflow: visible;">
            <fieldset class="col-lg-12">
                <div class="panel-body">
                    <form method="POST" name="frmadmin" id="newmessageform" class="form-horizontal">
                        @csrf
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Phone# :</label>
                            <div class="col-lg-4">{{ $contact_number }}</div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Message :</label>
                            <div class="col-lg-4"><textarea name="CsTwilioOrder[details]" class="form-control"></textarea></div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                <button type="button" class="btn btn-primary" onclick="SendNewMessage()">Send</button>
                                <button type="button" class="btn left-margin btn-cancel">Cancel</button>
                            </div>
                        </div>
                        <input type="hidden" name="CsTwilioOrder[id]" value="{{ $twilio_order_id }}">
                        <input type="hidden" name="CsTwilioOrder[cs_order_id]" value="{{ $orderid }}">
                    </form>
                </div>
            </fieldset>
        </div>
    </section>
</div>
