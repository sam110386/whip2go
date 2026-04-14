<div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">

    <div class="panel-body">
        <div class="row">
            @if(empty($docusign_envelope_id) || empty($envelopObj))
                <div class="col-lg-12">
                    Sorry, either selected insurance is not found or User didnt signed document yet.
                </div>
            @else
            <div class="col-lg-12">
                <form action="#" method="GET" name="frmadmin" class="form-horizontal">

                <legend class="text-size-large text-bold">Signed Documents:</legend>
                @foreach($envelopObj['envelope_documents'] as $document)
                    <div class="form-group">
                        <label class="col-lg-5 control-label"><a href="javascript:;" onclick="PullDocusignSignedDocument('{{ $docusign_envelope_id }}','{{ $document['document_id'] }}','{{ $OrderDepositRuleId }}','{{ $document['name'] }}')" >{{ $document['name'] }}</a></label>
                    </div>
                @endforeach
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-danger mt-10" data-dismiss="modal">Close</button>
</div>
