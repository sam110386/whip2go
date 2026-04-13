<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">

    <form action="{{ url('admin/users/saveaddressproof') }}" method="POST" name="frmadmin" class="form-horizontal" enctype="multipart/form-data">
        @csrf
        <div class="panel-body">
            <legend class="text-size-large text-bold">Address Proof Upload:</legend>
            <div class="form-group">
                <label class="col-lg-2 control-label">Upload :</label>
                <div class="col-lg-8">
                    <input type="file" name="proofimage" class="file-input" id="AddressProofDoc" data-show-preview="false" data-id="{{ $userid }}" />
                </div>
            </div>
            
        </div>
        <input type="hidden" name="userid" value="{{ $userid }}">
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>
