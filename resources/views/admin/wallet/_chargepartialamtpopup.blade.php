<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <div class="col-sm-12">
        <div class="panel panel-flat">
            <div class="panel-body">
                <form method="post" action="#" class="form-horizontal" id="chargepartialamtpopup">
                    @csrf
                    <fieldset>
                        <legend class="text-semibold">Details</legend>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Amount :</label>
                            <div class="col-lg-8">
                                <input type="text" name="Wallet[amount]" class="form-control" value="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-lg-2 control-label">Note:</label>
                            <div class="col-lg-8">
                                <input type="text" name="Wallet[note]" class="form-control" maxlength="35" value="Partial Payment" placeholder="Partial Payment">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-lg-6">
                                <button type="button" class="btn btn-primary pull-right" disabled>Charge (legacy JS)</button>
                            </div>
                        </div>
                    </fieldset>
                    <input type="hidden" name="Wallet[user_id]" value="{{ $userid }}">
                    <input type="hidden" name="Wallet[bookingid]" value="{{ $bookingid }}">
                    <input type="hidden" name="Wallet[currency]" value="{{ $currency }}">
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
</div>
