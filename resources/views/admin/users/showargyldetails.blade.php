@php
    $argyleUserRecords ??= collect();
@endphp

<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <form action="#" method="POST" class="form-horizontal">
        @csrf

        <fieldset>
            <div class="form-group">
                <label class="col-lg-4 control-label">Account</label>
                <div class="col-lg-8 control-label">Account ID</div>
            </div>
            @foreach($argyleUserRecords as $argyleUserRecord)
                <div class="form-group">
                    <label class="col-lg-4 control-label">
                        {{ ucfirst(data_get($argyleUserRecord, 'account', 'Unknown')) }}:
                    </label>
                    <div class="col-lg-8 control-label">
                        {{ data_get($argyleUserRecord, 'account_id', '') }}
                    </div>
                </div>
            @endforeach
        </fieldset>
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
</div>