<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <form action="{{ url('/admin/intercom/intercom_popups/loadpopup') }}" method="POST" name="frmadmin" class="form-horizontal">
        @csrf
        <div class="panel-body">
            <legend class="text-size-large text-bold">Available Actions:</legend>
            <div class="form-group">
                <div class="col-lg-12">
                    <div class="row glyphs">
                        <div class="col-md-3 col-sm-4" onclick="callIntercomApiAction('intercom/mapi/getMyTransactions/{{ $userid }}')"><i class="icon-microscope"></i> Latest Transactions</div>
                        <div class="col-md-3 col-sm-4" onclick="callIntercomApiAction('intercom/mapi/checkbalance/{{ $userid }}')"><i class="icon-microscope"></i> Check Dues</div>
                        <div class="col-md-3 col-sm-4" onclick="callIntercomApiAction('intercom/mapi/checkBookingExtension/{{ $userid }}')"><i class="icon-microscope"></i> Check Booking Extension</div>
                    </div>
                </div>
            </div>
            <legend class="text-size-large text-bold">Result :</legend>
            <div class="form-group">
                <pre class="content-group language-javascript">
                    <code class="language-javascript" data-language="javascript">
                    <div class="col-lg-12" id="intercomapiresult"></div>
                    </code>
                </pre>
            </div>
        </div>
        <input type="hidden" name="xtoken" value="{{ $xtoken }}" />
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>
