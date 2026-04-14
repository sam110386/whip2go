<div class="page-header">
    <div class="page-header-content">
        <div class="page-title">
            <h4><i class="icon-arrow-left52 position-left"></i> <span class="text-semibold">Booking</span> - New Notes</h4>
        </div>
    </div>
</div>
<div class="panel">
    <div class="panel-body">
        <form method="POST" name="frmadmin" id="newbookingnotes" class="form-horizontal">
            @csrf
            <div class="form-group">
                <label class="col-lg-2 control-label">Note :</label>
                <div class="col-lg-4">
                    <textarea name="OrderNote[msg]" class="form-control"></textarea>
                </div>
            </div>
            <div class="form-group">
                <label class="col-lg-2 control-label">&nbsp;</label>
                <div class="col-lg-4">
                    <button type="button" class="btn btn-primary" onclick="saveBookingNote()">Save</button>
                </div>
            </div>
            <input type="hidden" name="OrderNote[id]" value="">
            <input type="hidden" name="OrderNote[order_id]" value="{{ $orderid }}">
            <input type="hidden" name="OrderNote[parent_order_id]" value="{{ $parentid }}">
        </form>
    </div>
</div>
