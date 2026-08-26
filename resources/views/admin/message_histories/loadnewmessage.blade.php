<div class="panel">
    <section class="right_content">
        <section class="reportListingHeading"
            style="margin-bottom: 7px; float: left; width: 100%; padding: 13px 23px 0;">
        </section>

        <div style="width:100%; overflow: visible;">
            <fieldset class="col-lg-12 bg-white">
                <div class="panel-body">
                    <form action="{{ url('/admin/message_histories/loadnewmessage') }}" method="POST" name="frmadmin"
                        id="newmessageform" class="form-horizontal">
                        @csrf

                        <div class="form-group">
                            <label class="col-lg-2 control-label">
                                Phone# :
                            </label>
                            <div class="col-lg-4">
                                {{ data_get($csTwilioOrder, 'csOrder.user.contact_number', 'N/A') }}
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-2 control-label">
                                Message :
                            </label>
                            <div class="col-lg-4">
                                <textarea name="details" id="details" class="form-control" rows="4"></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-lg-2 control-label">&nbsp;</label>
                            <div class="col-lg-6">
                                <button type="button" class="btn btn-primary" onclick="SendNewMessage()">
                                    Send
                                </button>
                                <button type="button" class="btn left-margin btn-cancel" data-dismiss="modal">
                                    Cancel
                                </button>
                            </div>
                        </div>

                        <input type="hidden" name="id" value="{{ data_get($csTwilioOrder, 'id', '')}}">
                        <input type="hidden" name="cs_order_id" value="{{ $orderId }}">
                    </form>
                </div>
            </fieldset>
        </div>
    </section>
</div>