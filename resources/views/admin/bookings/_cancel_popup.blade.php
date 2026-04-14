<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <form action="#" method="post" id="cancelForm" class="form-horizontal">
        <fieldset>
            <legend class="text-semibold">Enter All Information</legend>
            <div class="form-group">
                <label class="col-lg-4 control-label">Cancellation Fee :</label>
                <div class="col-lg-8">
                    <input type="text" name="Text[cancellation_fee]" class="number form-control" placeholder="Cancellation Fee" value="{{ (float) $cancellation_fee }}">
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-4 control-label">Cancel Note :</label>
                <div class="col-lg-8">
                    <input type="text" name="Text[cancel_note]" class="form-control" placeholder="Cancel Note if any" value="">
                </div>
            </div>
        </fieldset>
        <input type="hidden" id="TextOrderid" name="Text[orderid]" value="{{ $orderid }}">
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" onclick="processCancel(this)">Process</button>
</div>

