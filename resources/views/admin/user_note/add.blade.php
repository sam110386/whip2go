<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
    <form action="" method="POST" id="addNoteForm" class="form-horizontal">
        @csrf
        <fieldset>
            <legend class="text-semibold">Enter All Information</legend>
            <div class="form-group">
                <label class="col-lg-2 control-label">Note :</label>
                <div class="col-lg-8">
                    <textarea name="UserNote[note]" class="number form-control" placeholder="Comment..."></textarea>
                </div>
            </div>
        </fieldset>
        <input type="hidden" name="UserNote[user_id]" value="{{ $userid }}">
    </form>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
    <button type="button" class="btn btn-primary" onclick="saveNote()">Save</button>
</div>
