<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <form action="" method="post" class="form-horizontal">
        <fieldset>
            <div class="form-group">
                <label class="col-lg-4 control-label">Account</label>
                <div class="col-lg-8 control-label">Account ID</div>
            </div>
            @foreach ($argyleRecords as $record)
                <div class="form-group">
                    <label class="col-lg-4 control-label">{{ ucfirst((string)$record->account) }}:</label>
                    <div class="col-lg-8 control-label">{{ $record->account_id }}</div>
                </div>
            @endforeach
            @if ($argyleRecords->isEmpty())
                <div class="form-group">
                    <div class="col-lg-12 control-label">No Argyle records found.</div>
                </div>
            @endif
        </fieldset>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
</div>
