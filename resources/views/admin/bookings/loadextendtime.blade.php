<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>

<div class="modal-body">
    <form action="" method="POST" id="loadextendtime" class="form-horizontal">
        @csrf

        <fieldset>
            <legend class="text-semibold">Select Date Time</legend>
            <div class="form-group">
                <label class="col-lg-4 control-label">Choose :</label>
                <div class="col-lg-8">
                    <input id="TextExtend" type="text" name="Text[extend]" class="form-control"
                        value="{{ data_get($order, 'scheduled_till', '') }}">
                </div>
            </div>
        </fieldset>
        <input type="hidden" name="Text[booking]" value="{{ data_get($order, 'id', '') }}">
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">
        Cancel
    </button>
    <button type="button" class="btn btn-primary" onclick="changeExtendTime()">
        Update
    </button>
</div>

@push('scripts')
    <script src="{{ asset('js/assets/js/plugins/pickers/datetimepicker.js') }}"></script>
@endpush