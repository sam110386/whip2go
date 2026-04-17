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
            @if(isset($ArgyleUser['ArgyleUserRecord']) && is_array($ArgyleUser['ArgyleUserRecord']))
                @foreach($ArgyleUser['ArgyleUserRecord'] as $record)
                    <div class="form-group"> 
                        <label class="col-lg-4 control-label">
                            {{ ucfirst($record['account'] ?? 'Unknown') }}:
                        </label>
                        <div class="col-lg-8 control-label">
                            {{ $record['account_id'] ?? '' }}
                        </div>  
                    </div>
                @endforeach
            @else
                <div class="form-group">
                    <div class="col-lg-12">
                        <p>No Argyle records found or Argyle Plugin is pending integration.</p>
                    </div>
                </div>
            @endif
        </fieldset>
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
</div>
